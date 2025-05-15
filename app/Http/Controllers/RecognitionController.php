<?php

namespace App\Http\Controllers;

use App\Models\RecognitionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RecognitionController extends Controller
{
    public function create()
    {
        return view('aluno.facial-registration');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'face_data' => 'required|string',
            'face_descriptor' => 'required|string',
        ]);
        
        $user = Auth::user();
        
        // Processar imagem do canvas e salvar
        if (preg_match('/^data:image\/(\w+);base64,/', $request->face_data, $matches)) {
            $imageData = substr($request->face_data, strpos($request->face_data, ',') + 1);
            $imageData = base64_decode($imageData);
            $imageType = $matches[1];
            $imageName = $user->id . '_' . time() . '.' . $imageType;
            $imagePath = 'users/' . $imageName;
            
            Storage::disk('public')->put($imagePath, $imageData);
            
            // Salvar registro de reconhecimento
            RecognitionRecord::create([
                'user_id' => $user->id,
                'image_path' => $imagePath,
                'face_descriptor' => $request->face_descriptor,
            ]);
            
            return redirect()->route('dashboard')->with('success', 'Registro facial realizado com sucesso!');
        }
        
        return back()->with('error', 'Erro ao processar imagem facial.');
    }
}