<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RecognitionRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function showAdminLoginForm()
    {
        return view('auth.admin-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->role === 'admin') {
                return redirect()->intended('admin/dashboard');
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'matricula' => 'required|string|max:20|unique:users',
            'curso' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'face_data' => 'required|string',
            'face_data_2' => 'required|string',
            'face_data_3' => 'required|string',
        ]);

        try {
            \DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'matricula' => $request->matricula,
                'curso' => $request->curso,
                'password' => Hash::make($request->password),
                'role' => 'aluno',
            ]);

            $faceDescriptors = [
                $request->face_data,
                $request->face_data_2,
                $request->face_data_3,
            ];

            if (empty($request->face_data) || empty($request->face_data_2) || empty($request->face_data_3)) {
                throw new \Exception('É necessário fornecer os três descritores faciais para o cadastro.');
            }

            foreach ($faceDescriptors as $index => $descriptor) {
                RecognitionRecord::create([
                    'user_id' => $user->id,
                    'face_descriptor' => $descriptor,
                    'capture_type' => 'registration_' . ($index + 1),
                ]);

                \Log::debug("Facial descriptor $index saved successfully for user ID: " . $user->id);
            }

            \DB::commit();

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', 'Cadastro realizado com sucesso!');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Registration error: ' . $e->getMessage());

            return back()->withInput()->withErrors([
                'error' => 'Erro ao criar usuário: ' . $e->getMessage()
            ]);
        }
    }

    public function facialLogin(Request $request)
    {
        try {
            \Log::info('Facial login attempt started');
            
            // Get data from the request body
            $data = $request->json()->all();
            
            if (empty($data)) {
                \Log::warning('Empty request data received');
                return response()->json([
                    'success' => false,
                    'message' => 'Dados não recebidos corretamente. Tente novamente.'
                ], 400);
            }
            
            \Log::info('Request data received', ['data_keys' => array_keys($data)]);
            
            // Check if face_descriptor exists
            if (!isset($data['face_descriptor'])) {
                \Log::warning('Face descriptor not found in request');
                return response()->json([
                    'success' => false,
                    'message' => 'Descritor facial não encontrado na requisição'
                ], 400);
            }
            
            $inputDescriptor = $data['face_descriptor'];
            
            // Check if descriptor is an array
            if (!is_array($inputDescriptor)) {
                \Log::warning('Face descriptor is not an array', ['type' => gettype($inputDescriptor)]);
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de descritor facial inválido'
                ], 400);
            }
            
            // Log the descriptor for debugging
            \Log::info('Received descriptor length: ' . count($inputDescriptor));
            
            // Get all users with facial recognition records
            $users = User::whereHas('recognitionRecords')->with('recognitionRecords')->get();
            
            if ($users->isEmpty()) {
                \Log::warning('No users with recognition records found');
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário cadastrado com reconhecimento facial'
                ], 404);
            }
            
            $bestMatch = null;
            $bestMatchDistance = 0.6; // Threshold (lower is better)
            $bestMatchScore = 1.0;
            $matchedRecord = null;
            
            foreach ($users as $user) {
                \Log::debug("Checking user ID: " . $user->id);
                foreach ($user->recognitionRecords as $record) {
                    try {
                        if (empty($record->face_descriptor)) {
                            \Log::warning('Empty face descriptor for record ID: ' . $record->id);
                            continue;
                        }
                        
                        // Get the stored descriptor
                        $storedDescriptor = $record->face_descriptor;
                        
                        // If it's a string (JSON), decode it
                        if (is_string($storedDescriptor)) {
                            $storedDescriptor = json_decode($storedDescriptor, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                \Log::warning('Failed to decode JSON descriptor for record ID: ' . $record->id);
                                continue;
                            }
                        }
                        
                        // Ensure we have an array
                        if (!is_array($storedDescriptor)) {
                            \Log::warning('Invalid face descriptor for record ID: ' . $record->id);
                            continue;
                        }
                        
                        // Check descriptor length match
                        if (count($storedDescriptor) !== count($inputDescriptor)) {
                            \Log::warning('Descriptor length mismatch: stored=' . count($storedDescriptor) . ', input=' . count($inputDescriptor));
                            continue;
                        }
                        
                        $distance = $this->calculateEuclideanDistance($inputDescriptor, $storedDescriptor);
                        
                        \Log::info('User ID: ' . $user->id . ', Record ID: ' . $record->id . ', Distance: ' . $distance);
                        
                        if ($distance < $bestMatchDistance && $distance < $bestMatchScore) {
                            $bestMatchScore = $distance;
                            $bestMatch = $user;
                            $matchedRecord = $record;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error processing record ID ' . $record->id . ': ' . $e->getMessage());
                        continue;
                    }
                }
            }
            
            if ($bestMatch) {
                // User found, log them in
                Auth::login($bestMatch);
                
                // Record successful recognition for analytics
                try {
                    RecognitionRecord::create([
                        'user_id' => $bestMatch->id,
                        'face_descriptor' => json_encode($inputDescriptor),
                        'capture_type' => 'successful_login',
                    ]);
                    
                    \Log::info('Successfully authenticated user: ' . $bestMatch->id . ' with match score: ' . $bestMatchScore);
                } catch (\Exception $e) {
                    \Log::warning('Failed to record successful recognition: ' . $e->getMessage());
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Usuário reconhecido com sucesso',
                    'redirect' => $bestMatch->role === 'admin' ? route('admin.dashboard') : route('dashboard')
                ]);
            } else {
                // No match found
                \Log::warning('No facial match found for login attempt');
                
                // Optional: Record failed attempt for security analysis
                try {
                    $failedAttemptDescriptor = json_encode($inputDescriptor);
                    \Log::debug('Failed login attempt descriptor: ' . substr($failedAttemptDescriptor, 0, 100) . '...');
                } catch (\Exception $e) {
                    \Log::error('Error recording failed attempt: ' . $e->getMessage());
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não reconhecido. Por favor, tente novamente.'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Facial login error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro durante a verificação: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function calculateEuclideanDistance($descriptor1, $descriptor2)
    {
        try {
            if (!is_array($descriptor1) || !is_array($descriptor2)) {
                \Log::warning('Non-array descriptor received', [
                    'descriptor1_type' => gettype($descriptor1), 
                    'descriptor2_type' => gettype($descriptor2)
                ]);
                return 1.0; // Return max distance on error
            }
            
            if (count($descriptor1) !== count($descriptor2)) {
                \Log::warning('Descriptor dimensions do not match', [
                    'descriptor1_length' => count($descriptor1),
                    'descriptor2_length' => count($descriptor2)
                ]);
                return 1.0; // Return max distance on error
            }
            
            $sum = 0;
            for ($i = 0; $i < count($descriptor1); $i++) {
                $diff = $descriptor1[$i] - $descriptor2[$i];
                $sum += $diff * $diff;
            }
            
            return sqrt($sum);
        } catch (\Exception $e) {
            \Log::error('Error calculating distance: ' . $e->getMessage());
            return 1.0; // Return max distance on error
        }
    }
}