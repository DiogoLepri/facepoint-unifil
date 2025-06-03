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
                return redirect()->route('admin.dashboard');
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
            'name' => 'required|string|max:255|regex:/^[A-Za-zÀ-ÿ\s]+$/',
            'email' => 'required|string|email|max:255|unique:users|regex:/^[a-zA-Z0-9._%+-]+@edu\.unifil\.br$/',
            'matricula' => 'required|string|size:9|regex:/^[0-9]{9}$/|unique:users',
            'curso' => 'required|string|in:Ciencia da Computacao,Engenharia de Software',
            'password' => 'required|string|min:8|confirmed',
            'face_data' => 'required|string',
            'face_data_2' => 'required|string',
            'face_data_3' => 'required|string',
        ], [
            'name.regex' => 'O nome deve conter apenas letras e espaços.',
            'email.regex' => 'O email deve terminar com @edu.unifil.br',
            'matricula.size' => 'A matrícula deve ter exatamente 9 números.',
            'matricula.regex' => 'A matrícula deve conter apenas números.',
            'curso.in' => 'Selecione um curso válido.',
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
            $recognitionThreshold = config('deepface.recognition_threshold', 0.4); // Configurable threshold
            $bestMatchDistance = PHP_FLOAT_MAX; // Initialize to maximum value
            $matchedRecord = null;
            
            \Log::info('Starting facial recognition with threshold: ' . $recognitionThreshold);
            
            $totalComparisons = 0;
            $validComparisons = 0;
            
            foreach ($users as $user) {
                \Log::debug("Checking user ID: " . $user->id);
                foreach ($user->recognitionRecords as $record) {
                    $totalComparisons++;
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
                        
                        // Validate descriptor values are numeric
                        if (!$this->validateDescriptorValues($inputDescriptor) || !$this->validateDescriptorValues($storedDescriptor)) {
                            \Log::warning('Invalid descriptor values for record ID: ' . $record->id);
                            continue;
                        }
                        
                        $validComparisons++;
                        $distance = $this->calculateEuclideanDistance($inputDescriptor, $storedDescriptor);
                        
                        \Log::info('User ID: ' . $user->id . ', Record ID: ' . $record->id . ', Distance: ' . $distance . ', Threshold: ' . $recognitionThreshold);
                        
                        // Only consider matches below the threshold AND better than current best
                        if ($distance < $recognitionThreshold && $distance < $bestMatchDistance) {
                            $bestMatchDistance = $distance;
                            $bestMatch = $user;
                            $matchedRecord = $record;
                            \Log::info('New best match found: User ID ' . $user->id . ' with distance ' . $distance);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error processing record ID ' . $record->id . ': ' . $e->getMessage());
                        continue;
                    }
                }
            }
            
            \Log::info('Recognition completed', [
                'total_comparisons' => $totalComparisons,
                'valid_comparisons' => $validComparisons,
                'best_distance' => $bestMatchDistance,
                'threshold' => $recognitionThreshold,
                'match_found' => $bestMatch !== null
            ]);
            
            if ($bestMatch && $bestMatchDistance < $recognitionThreshold) {
                \Log::info('Valid facial match found', [
                    'user_id' => $bestMatch->id,
                    'user_name' => $bestMatch->name,
                    'distance' => $bestMatchDistance,
                    'threshold' => $recognitionThreshold
                ]);
                
                // Store match data in session for confirmation
                session([
                    'facial_match_user_id' => $bestMatch->id,
                    'facial_match_distance' => $bestMatchDistance,
                    'facial_match_descriptor' => $inputDescriptor,
                    'facial_match_timestamp' => time()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Usuário reconhecido com sucesso',
                    'user_name' => $bestMatch->name,
                    'user_id' => $bestMatch->id,
                    'requires_confirmation' => true
                ]);
            } else {
                // No match found
                $logMessage = 'No facial match found for login attempt';
                if ($bestMatch) {
                    $logMessage .= ' - Best match was ' . $bestMatch->name . ' with distance ' . $bestMatchDistance . ' (threshold: ' . $recognitionThreshold . ')';
                }
                \Log::warning($logMessage);
                
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

    public function confirmFacialLogin(Request $request)
    {
        try {
            // Check if we have a pending facial match
            $userId = session('facial_match_user_id');
            $matchTimestamp = session('facial_match_timestamp');
            
            if (!$userId || !$matchTimestamp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma correspondência facial pendente encontrada'
                ], 400);
            }
            
            // Check if match is not too old (5 minutes timeout)
            if (time() - $matchTimestamp > 300) {
                session()->forget(['facial_match_user_id', 'facial_match_distance', 'facial_match_descriptor', 'facial_match_timestamp']);
                return response()->json([
                    'success' => false,
                    'message' => 'Tempo limite excedido. Tente novamente.'
                ], 400);
            }
            
            $user = User::find($userId);
            if (!$user) {
                session()->forget(['facial_match_user_id', 'facial_match_distance', 'facial_match_descriptor', 'facial_match_timestamp']);
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não encontrado'
                ], 404);
            }
            
            // Log the user in
            Auth::login($user);
            
            // Record successful recognition for analytics
            try {
                RecognitionRecord::create([
                    'user_id' => $user->id,
                    'face_descriptor' => json_encode(session('facial_match_descriptor')),
                    'capture_type' => 'confirmed_login',
                ]);
                
                \Log::info('Successfully authenticated user after confirmation: ' . $user->id . ' with distance: ' . session('facial_match_distance'));
            } catch (\Exception $e) {
                \Log::warning('Failed to record successful recognition: ' . $e->getMessage());
            }
            
            // Clear session data
            session()->forget(['facial_match_user_id', 'facial_match_distance', 'facial_match_descriptor', 'facial_match_timestamp']);
            
            // Determine redirect URL - students go to /dashboard (which should redirect to aluno/dashboard)
            $redirectUrl = ($user->role === 'admin') ? route('admin.dashboard') : route('dashboard');
            
            return response()->json([
                'success' => true,
                'message' => 'Login confirmado com sucesso',
                'redirect' => $redirectUrl
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Facial login confirmation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro durante confirmação de login'
            ], 500);
        }
    }
    
    public function rejectFacialLogin(Request $request)
    {
        try {
            // Log the rejection for security purposes
            $userId = session('facial_match_user_id');
            if ($userId) {
                \Log::warning('Facial login rejected by user', [
                    'matched_user_id' => $userId,
                    'distance' => session('facial_match_distance'),
                    'timestamp' => session('facial_match_timestamp')
                ]);
            }
            
            // Clear session data
            session()->forget(['facial_match_user_id', 'facial_match_distance', 'facial_match_descriptor', 'facial_match_timestamp']);
            
            return response()->json([
                'success' => true,
                'message' => 'Correspondência rejeitada'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Facial login rejection error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro durante rejeição'
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

    private function validateDescriptorValues($descriptor)
    {
        if (!is_array($descriptor)) {
            return false;
        }
        
        foreach ($descriptor as $value) {
            if (!is_numeric($value) || !is_finite($value)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function calculateEuclideanDistance($descriptor1, $descriptor2)
    {
        try {
            if (!is_array($descriptor1) || !is_array($descriptor2)) {
                \Log::warning('Non-array descriptor received', [
                    'descriptor1_type' => gettype($descriptor1), 
                    'descriptor2_type' => gettype($descriptor2)
                ]);
                return PHP_FLOAT_MAX; // Return max distance on error
            }
            
            if (count($descriptor1) !== count($descriptor2)) {
                \Log::warning('Descriptor dimensions do not match', [
                    'descriptor1_length' => count($descriptor1),
                    'descriptor2_length' => count($descriptor2)
                ]);
                return PHP_FLOAT_MAX; // Return max distance on error
            }
            
            $sum = 0;
            for ($i = 0; $i < count($descriptor1); $i++) {
                if (!is_numeric($descriptor1[$i]) || !is_numeric($descriptor2[$i])) {
                    \Log::warning('Non-numeric values in descriptors');
                    return PHP_FLOAT_MAX;
                }
                
                $diff = $descriptor1[$i] - $descriptor2[$i];
                $sum += $diff * $diff;
            }
            
            return sqrt($sum);
        } catch (\Exception $e) {
            \Log::error('Error calculating distance: ' . $e->getMessage());
            return PHP_FLOAT_MAX; // Return max distance on error
        }
    }
}