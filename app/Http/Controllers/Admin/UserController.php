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
        $query = User::withTrashed();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('matricula', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'inactive') {
                $query->onlyTrashed();
            } elseif ($request->status === 'active') {
                $query->withoutTrashed();
            }
        }

        $users = $query->paginate(10);

        return view('admin.users.index', compact('users'));
    }
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->email === 'joao.andrade@unifil.br') {
            return redirect()->back()->withErrors(['error' => 'O administrador principal não pode ser editado por motivos de segurança.']);
        }

        if ($request->has('schedule')) {
            $cleanSchedule = [];
            foreach ($request->schedule as $day => $times) {
                if (!empty($times['entry']) && !empty($times['exit'])) {
                    $cleanSchedule[$day] = $times;
                }
            }
            $request->merge(['schedule' => $cleanSchedule]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'matricula' => 'required|string|max:20|unique:users,matricula,' . $id,
            'curso' => 'required|string|in:Ciencia da Computacao,Engenharia de Software',
            'role' => 'required|in:aluno,admin',
            'password' => 'nullable|string|min:8|confirmed',
            'schedule' => 'nullable|array',
            'schedule.*.entry' => 'required|date_format:H:i',
            'schedule.*.exit' => 'required|date_format:H:i',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->matricula = $request->matricula;
        $user->curso = $request->curso;

        if ($user->role !== 'admin' && $request->role === 'admin') {
            return redirect()->back()->withErrors(['role' => 'Não é possível promover usuários para administrador via interface.']);
        }

        if ($user->role === 'admin' || $request->role === 'aluno') {
            $user->role = $request->role;
        }

        if ($request->has('schedule')) {
            $schedule = [];
            foreach ($request->schedule as $day => $times) {
                if (!empty($times['entry']) && !empty($times['exit'])) {
                    $schedule[$day] = [
                        'entry' => $times['entry'],
                        'exit' => $times['exit']
                    ];
                }
            }
            $user->schedule = !empty($schedule) ? $schedule : null;
        } else {
            $user->schedule = null;
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->face_data) {
            $this->processFacialData($request->face_data, $request->face_descriptor, $user->id);
        } else if ($request->hasFile('profile_image')) {
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
        $user = User::withTrashed()->findOrFail($id);

        if ($user->email === 'joao.andrade@unifil.br') {
            return redirect()->back()->withErrors(['error' => 'O administrador principal não pode ser inativado por motivos de segurança.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário inativado com sucesso!');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->trashed()) {
            $user->restore();
            return redirect()->route('users.index')->with('success', 'Usuário reativado com sucesso!');
        }

        return redirect()->route('users.index')->with('info', 'Este usuário já está ativo.');
    }

    public function forceDestroy($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        foreach ($user->recognitionRecords as $record) {
            if ($record->image_path) {
                Storage::disk('public')->delete($record->image_path);
            }
            $record->delete();
        }

        $user->forceDelete();

        return redirect()->route('users.index')->with('success', 'Usuário excluído permanentemente!');
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