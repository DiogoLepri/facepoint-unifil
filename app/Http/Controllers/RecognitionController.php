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
        ]);
        
        $user = Auth::user();
        $imageData = $request->face_data;
        
        // Initialize DeepFace service
        $deepFaceService = new \App\Services\DeepFaceService();
        
        // Validate image data
        if (!$deepFaceService->validateImageData($imageData)) {
            return back()->with('error', 'Dados de imagem inválidos.');
        }
        
        // Check if DeepFace API is healthy
        $healthCheck = $deepFaceService->healthCheck();
        if (!$healthCheck['success']) {
            \Log::error('DeepFace API health check failed during registration', $healthCheck);
            return back()->with('error', 'Serviço de reconhecimento facial temporariamente indisponível.');
        }
        
        // Register face with DeepFace
        $registrationResult = $deepFaceService->registerFace($user->id, $imageData);
        
        if (!$registrationResult['success']) {
            \Log::warning('Face registration failed', [
                'user_id' => $user->id,
                'error' => $registrationResult['error']
            ]);
            
            return back()->with('error', 'Falha ao registrar face: ' . $registrationResult['error']);
        }
        
        $registrationData = $registrationResult['data'];
        
        // Save local recognition record for backup/reference
        try {
            // Processar imagem do canvas e salvar localmente também
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $base64Data = substr($imageData, strpos($imageData, ',') + 1);
                $decodedImageData = base64_decode($base64Data);
                $imageType = $matches[1];
                $imageName = $user->id . '_' . time() . '.' . $imageType;
                $imagePath = 'users/' . $imageName;
                
                Storage::disk('public')->put($imagePath, $decodedImageData);
                
                // Salvar registro de reconhecimento local
                RecognitionRecord::create([
                    'user_id' => $user->id,
                    'image_path' => $imagePath,
                    'face_descriptor' => json_encode([
                        'deepface_registered' => true,
                        'total_images' => $registrationData['total_images'] ?? 1,
                        'deepface_path' => $registrationData['image_path'] ?? null
                    ]),
                ]);
                
                \Log::info('Face registration successful', [
                    'user_id' => $user->id,
                    'total_images' => $registrationData['total_images'] ?? 1,
                    'local_path' => $imagePath,
                    'deepface_path' => $registrationData['image_path'] ?? null
                ]);
                
                return redirect()->route('dashboard')->with('success', 'Registro facial realizado com sucesso!');
            }
        } catch (\Exception $e) {
            \Log::error('Error saving local recognition record', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // Still return success since DeepFace registration worked
            return redirect()->route('dashboard')->with('success', 'Registro facial realizado com sucesso!');
        }
        
        return back()->with('error', 'Erro ao processar imagem facial.');
    }
}