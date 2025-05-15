<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RecognitionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('matricula', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->paginate(10);
        
        return view('admin.users.index', compact('users'));
    }
    
    public function create()
    {
        return view('admin.users.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'matricula' => 'required|string|max:20|unique:users',
            'curso' => 'required|string',
            'role' => 'required|in:aluno,admin',
            'password' => 'required|string|min:8|confirmed',
            'face_data' => 'nullable|string',
            'profile_image' => 'nullable|image|max:2048',
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'matricula' => $request->matricula,
            'curso' => $request->curso,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);
        
        // Processar imagem facial
        if ($request->face_data) {
            $this->processFacialData($request->face_data, $request->face_descriptor, $user->id);
        } 
        // Ou processar imagem de perfil
        else if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('users', 'public');
            $user->profile_image = $path;
            $user->save();
        }
        
        return redirect()->route('users.index')->with('success', 'Usuário criado com sucesso!');
    }
    
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }
    
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'matricula' => 'required|string|max:20|unique:users,matricula,' . $id,
            'curso' => 'required|string',
            'role' => 'required|in:aluno,admin',
            'password' => 'nullable|string|min:8|confirmed',
            'face_data' => 'nullable|string',
            'profile_image' => 'nullable|image|max:2048',
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->matricula = $request->matricula;
        $user->curso = $request->curso;
        $user->role = $request->role;
        
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        // Processar imagem facial
        if ($request->face_data) {
            $this->processFacialData($request->face_data, $request->face_descriptor, $user->id);
        } 
        // Ou processar imagem de perfil
        else if ($request->hasFile('profile_image')) {
            // Remover imagem anterior se existir
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            
            $path = $request->file('profile_image')->store('users', 'public');
            $user->profile_image = $path;
            $user->save();
        }
        
        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
    }
    
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Remover imagem de perfil se existir
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        
        // Remover registros faciais
        foreach ($user->recognitionRecords as $record) {
            if ($record->image_path) {
                Storage::disk('public')->delete($record->image_path);
            }
            $record->delete();
        }
        
        $user->delete();
        
        return redirect()->route('users.index')->with('success', 'Usuário excluído com sucesso!');
    }
    
    private function processFacialData($faceData, $faceDescriptor, $userId)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $faceData, $matches)) {
            $imageData = substr($faceData, strpos($faceData, ',') + 1);
            $imageData = base64_decode($imageData);
            $imageType = $matches[1];
            $imageName = $userId . '_' . time() . '.' . $imageType;
            $imagePath = 'users/' . $imageName;
            
            Storage::disk('public')->put($imagePath, $imageData);
            
            // Salvar registro de reconhecimento
            RecognitionRecord::create([
                'user_id' => $userId,
                'image_path' => $imagePath,
                'face_descriptor' => $faceDescriptor,
            ]);
            
            return true;
        }
        
        return false;
    }
}