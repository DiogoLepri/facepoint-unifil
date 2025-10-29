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

        \Log::info('Login attempt', ['email' => $credentials['email']]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Update user's last login type and time
            Auth::user()->update([
                'last_login_type' => 'email',
                'last_login_at' => now(),
            ]);

            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->intended('dashboard');
        }

        \Log::warning('Login failed', ['email' => $credentials['email']]);

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        // Validação dos campos APENAS de dados pessoais
        // NÃO valida face_data - a biometria será cadastrada na ETAPA 2 via /admin/facial/enrol
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[A-Za-zÀ-ÿ\s]+$/',
            'email' => 'required|string|email|max:255|unique:users|regex:/^[a-zA-Z0-9._%+-]+@edu\.unifil\.br$/',
            'matricula' => 'required|string|size:9|regex:/^[0-9]{9}$/|unique:users',
            'curso' => 'required|string|in:Ciencia da Computacao,Engenharia de Software',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.regex' => 'O nome deve conter apenas letras e espaços.',
            'email.regex' => 'O email deve terminar com @edu.unifil.br',
            'matricula.size' => 'A matrícula deve ter exatamente 9 números.',
            'matricula.regex' => 'A matrícula deve conter apenas números.',
            'curso.in' => 'Selecione um curso válido.',
        ]);

        try {
            \DB::beginTransaction();

            // ETAPA 1: Criar usuário (somente dados pessoais)
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'matricula' => $request->matricula,
                'curso' => $request->curso,
                'password' => Hash::make($request->password),
                'role' => 'aluno',
            ]);

            \DB::commit();

            \Log::info("User registered successfully", [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Fazer login do usuário
            Auth::login($user);

            // Retornar JSON com user_id para o frontend continuar com enrolment facial
            return response()->json([
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso',
                'user_id' => $user->id,
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Registration error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar usuário: ' . $e->getMessage()
            ], 500);
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

            // Check if face_descriptor (image_data) exists
            if (!isset($data['face_descriptor'])) {
                \Log::warning('Face descriptor (image_data) not found in request');
                return response()->json([
                    'success' => false,
                    'message' => 'Imagem não encontrada na requisição'
                ], 400);
            }

            $imageData = $data['face_descriptor'];

            // Validate image data is a string (base64)
            if (!is_string($imageData) || empty($imageData)) {
                \Log::warning('Invalid image data format');
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de imagem inválido'
                ], 400);
            }

            \Log::info('Image data received, length: ' . strlen($imageData) . ' characters');

            // STEP 1: Get all users with facial recognition records
            $recognitionRecords = RecognitionRecord::whereNotNull('face_descriptor')->get();

            if ($recognitionRecords->isEmpty()) {
                \Log::warning('No users with recognition records found');
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário cadastrado com reconhecimento facial'
                ], 404);
            }

            // STEP 2: Prepare known embeddings for Flask
            $knownEmbeddings = [];
            foreach ($recognitionRecords as $record) {
                $faceDescriptor = $record->face_descriptor;

                // If it's a string (JSON), decode it
                if (is_string($faceDescriptor)) {
                    $faceDescriptor = json_decode($faceDescriptor, true);
                }

                // Ensure we have a valid array
                if (is_array($faceDescriptor) && !empty($faceDescriptor)) {
                    $knownEmbeddings[] = [
                        'user_id' => $record->user_id,
                        'embedding' => $faceDescriptor
                    ];
                } else {
                    \Log::warning('Invalid face descriptor for record ID: ' . $record->id);
                }
            }

            if (empty($knownEmbeddings)) {
                \Log::warning('No valid embeddings found');
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum embedding válido encontrado no banco de dados'
                ], 404);
            }

            \Log::info('Prepared ' . count($knownEmbeddings) . ' known embeddings for Flask');

            // STEP 3: Call Flask API /recognize_face to do the matching
            \Log::info('Calling Flask API /recognize_face...');

            $flaskResponse = \Http::timeout(30)->post('http://localhost:5001/recognize_face', [
                'image_data' => $imageData,
                'known_embeddings' => $knownEmbeddings,
            ]);

            if (!$flaskResponse->successful()) {
                \Log::error('Flask API error', [
                    'status' => $flaskResponse->status(),
                    'body' => $flaskResponse->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao processar reconhecimento facial'
                ], 500);
            }

            $flaskData = $flaskResponse->json();
            \Log::info('Flask response', ['data' => $flaskData]);

            // STEP 4: Handle Flask response
            if (isset($flaskData['success']) && $flaskData['success'] === true) {
                // Match found!
                $recognizedUserId = $flaskData['user_id'];
                $distance = $flaskData['distance'];

                $user = User::find($recognizedUserId);

                if (!$user) {
                    \Log::error('Recognized user ID not found: ' . $recognizedUserId);
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuário reconhecido não encontrado no sistema'
                    ], 404);
                }

                \Log::info('Valid facial match found', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'distance' => $distance
                ]);

                // Store match data in session for confirmation
                session([
                    'facial_match_user_id' => $user->id,
                    'facial_match_distance' => $distance,
                    'facial_match_timestamp' => time()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Usuário reconhecido com sucesso',
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                    'requires_confirmation' => true
                ]);
            } elseif (isset($flaskData['error'])) {
                // Error from Flask (e.g., no face detected)
                $errorMsg = $flaskData['error'];
                \Log::warning('Flask error: ' . $errorMsg);

                return response()->json([
                    'success' => false,
                    'message' => $errorMsg
                ], 400);
            } else {
                // No match found
                $bestDistance = $flaskData['best_distance'] ?? 'unknown';
                \Log::warning('No facial match found', ['best_distance' => $bestDistance]);

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
            
            // Update user's last login type and time
            $user->update([
                'last_login_type' => 'facial',
                'last_login_at' => now(),
            ]);
            
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