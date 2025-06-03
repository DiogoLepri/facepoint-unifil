<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    // Definir horários de trabalho (14:00 às 18:00, segunda a sexta)
    const ENTRY_TIME = '14:00';
    const EXIT_TIME = '18:00';
    const TOLERANCE_MINUTES = 15; // Tolerância de 15 minutos
    
    public function index()
    {
        $user = Auth::user();
        
        // Obter os últimos registros do usuário
        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Calcular horas registradas
        $hoursRegistered = $this->calculateHoursRegistered($user->id);
        
        // Calcular frequência
        $attendance = $this->calculateAttendancePercentage($user->id);
        
        // Determinar próximo registro
        $nextRegister = $this->getNextRegisterTime($user->id);
        
        return view('aluno.dashboard', compact('attendances', 'hoursRegistered', 'attendance', 'nextRegister'));
    }
    
    public function create()
    {
        return view('aluno.registro-ponto');
    }
    
    public function history(Request $request)
    {
        $user = Auth::user();
        
        $query = AttendanceRecord::where('user_id', $user->id);
        
        // Aplicar filtros
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $attendances = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return view('aluno.historico', compact('attendances'));
    }
    
    public function registerAttendance(Request $request)
    {
        try {
            \Log::info('Iniciando registro de ponto');
            
            $user = Auth::user();
            $now = Carbon::now();
            
            // Verificar se é um dia útil (segunda a sexta)
            if ($now->isWeekend()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registros de ponto só são permitidos em dias úteis (segunda a sexta).'
                ]);
            }
            
            // Verificar se já existe um registro para hoje
            $today = $now->format('Y-m-d');
            $existingRecord = AttendanceRecord::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->first();
            
            $punchType = $existingRecord && $existingRecord->entry_time ? 'exit' : 'entry';
            $expectedTime = $punchType === 'entry' ? self::ENTRY_TIME : self::EXIT_TIME;
            $expectedDateTime = Carbon::parse($today . ' ' . $expectedTime);
            
            // Calcular diferença em minutos
            $minutesDifference = $now->diffInMinutes($expectedDateTime, false);
            $isEarly = $minutesDifference > 0;
            $isLate = $minutesDifference < -self::TOLERANCE_MINUTES;
            
            // Validar justificativa se necessário
            $justification = $request->input('justification');
            if (($isEarly || $isLate) && empty($justification)) {
                return response()->json([
                    'success' => false,
                    'requires_justification' => true,
                    'message' => $isEarly ? 
                        'Você está batendo o ponto antes do horário. Deseja continuar e fornecer uma justificativa?' :
                        'Você está batendo o ponto após o horário permitido. Deseja continuar e fornecer uma justificativa?',
                    'punch_type' => $punchType,
                    'expected_time' => $expectedTime,
                    'current_time' => $now->format('H:i'),
                    'minutes_difference' => abs($minutesDifference)
                ]);
            }
            
            if ($existingRecord) {
                // Registrar saída
                if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                    $existingRecord->update([
                        'exit_time' => $now,
                        'punch_type' => 'exit',
                        'expected_time' => $expectedDateTime,
                        'minutes_difference' => $minutesDifference,
                        'is_early' => $isEarly,
                        'is_late' => $isLate,
                        'justification' => $justification
                    ]);
                    
                    \Log::info('Saída registrada', ['user_id' => $user->id, 'record_id' => $existingRecord->id]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Saída registrada com sucesso!',
                        'punch_type' => 'exit',
                        'time' => $now->format('H:i'),
                        'is_early' => $isEarly,
                        'is_late' => $isLate
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você já registrou entrada e saída hoje.'
                    ]);
                }
            } else {
                // Criar novo registro de entrada
                $newRecord = AttendanceRecord::create([
                    'user_id' => $user->id,
                    'entry_time' => $now,
                    'status' => 'registered',
                    'punch_type' => 'entry',
                    'expected_time' => $expectedDateTime,
                    'minutes_difference' => $minutesDifference,
                    'is_early' => $isEarly,
                    'is_late' => $isLate,
                    'justification' => $justification
                ]);
                
                \Log::info('Entrada registrada', ['user_id' => $user->id, 'record_id' => $newRecord->id]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Entrada registrada com sucesso!',
                    'punch_type' => 'entry',
                    'time' => $now->format('H:i'),
                    'is_early' => $isEarly,
                    'is_late' => $isLate
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao registrar ponto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar ponto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function status(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado'
            ]);
        }
        
        $user = Auth::user();
        $now = Carbon::now();
        
        // Verificar se é dia útil
        if ($now->isWeekend()) {
            return response()->json([
                'success' => true,
                'is_weekend' => true,
                'message' => 'Hoje é fim de semana. Registros não são necessários.'
            ]);
        }
        
        // Obter registro de hoje
        $today = $now->format('Y-m-d');
        $todayRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();
        
        $nextPunchType = $todayRecord && $todayRecord->entry_time ? 'exit' : 'entry';
        $expectedTime = $nextPunchType === 'entry' ? self::ENTRY_TIME : self::EXIT_TIME;
        
        return response()->json([
            'success' => true,
            'today_record' => $todayRecord ? [
                'entry_time' => $todayRecord->entry_time?->format('H:i'),
                'exit_time' => $todayRecord->exit_time?->format('H:i'),
                'has_justification' => !empty($todayRecord->justification)
            ] : null,
            'next_punch_type' => $nextPunchType,
            'expected_time' => $expectedTime,
            'current_time' => $now->format('H:i'),
            'work_hours' => [
                'entry' => self::ENTRY_TIME,
                'exit' => self::EXIT_TIME
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'image_data' => 'required|string'
        ]);
        
        $imageData = $request->image_data;
        
        // Initialize DeepFace service
        $deepFaceService = new \App\Services\DeepFaceService();
        
        // Validate image data
        if (!$deepFaceService->validateImageData($imageData)) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de imagem inválidos'
            ], 400);
        }
        
        // Check if DeepFace API is healthy
        $healthCheck = $deepFaceService->healthCheck();
        if (!$healthCheck['success']) {
            \Log::error('DeepFace API health check failed', $healthCheck);
            return response()->json([
                'success' => false,
                'message' => 'Serviço de reconhecimento facial temporariamente indisponível'
            ], 503);
        }
        
        // Perform face recognition
        $recognitionResult = $deepFaceService->recognizeFace($imageData);
        
        if (!$recognitionResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $recognitionResult['data']['message'] ?? 'Falha no reconhecimento facial',
                'confidence' => $recognitionResult['data']['confidence'] ?? null
            ]);
        }
        
        $recognitionData = $recognitionResult['data'];
        
        if (!$recognitionData['success']) {
            return response()->json([
                'success' => false,
                'message' => $recognitionData['message'] ?? 'Usuário não reconhecido',
                'confidence' => $recognitionData['confidence'] ?? null
            ]);
        }
        
        // Get user from recognition result
        $userId = $recognitionData['user_id'];
        $confidence = $recognitionData['confidence'] ?? 0;
        
        // Find Laravel user
        $user = User::find($userId);
        if (!$user) {
            \Log::warning('DeepFace recognized user not found in Laravel', [
                'deepface_user_id' => $userId
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Usuário não encontrado no sistema'
            ]);
        }
        
        // Check confidence threshold
        if (!$deepFaceService->meetsConfidenceThreshold($confidence)) {
            return response()->json([
                'success' => false,
                'message' => "Confiança insuficiente: {$confidence}%",
                'confidence' => $confidence
            ]);
        }
        
        // Usar a mesma lógica do registerAttendance
        $now = Carbon::now();
        
        if ($now->isWeekend()) {
            return response()->json([
                'success' => false,
                'message' => 'Registros de ponto só são permitidos em dias úteis (segunda a sexta).'
            ]);
        }
        
        $today = $now->format('Y-m-d');
        $existingRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();
        
        $punchType = $existingRecord && $existingRecord->entry_time ? 'exit' : 'entry';
        $expectedTime = $punchType === 'entry' ? self::ENTRY_TIME : self::EXIT_TIME;
        $expectedDateTime = Carbon::parse($today . ' ' . $expectedTime);
        
        $minutesDifference = $now->diffInMinutes($expectedDateTime, false);
        $isEarly = $minutesDifference > 0;
        $isLate = $minutesDifference < -self::TOLERANCE_MINUTES;
        
        // Para reconhecimento facial, vamos assumir que é sempre permitido
        // mas vamos marcar como early/late conforme necessário
        if ($existingRecord) {
            if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                $existingRecord->update([
                    'exit_time' => $now,
                    'punch_type' => 'exit',
                    'expected_time' => $expectedDateTime,
                    'minutes_difference' => $minutesDifference,
                    'is_early' => $isEarly,
                    'is_late' => $isLate
                ]);
                
                $type = 'Saída';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já registrou entrada e saída hoje.',
                    'confidence' => $confidence
                ]);
            }
        } else {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'entry_time' => $now,
                'status' => 'registered',
                'punch_type' => 'entry',
                'expected_time' => $expectedDateTime,
                'minutes_difference' => $minutesDifference,
                'is_early' => $isEarly,
                'is_late' => $isLate
            ]);
            
            $type = 'Entrada';
        }
        
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'type' => $type,
            'confidence' => $confidence,
            'time' => $now->format('H:i'),
            'is_early' => $isEarly,
            'is_late' => $isLate,
            'recognition_data' => [
                'distance' => $recognitionData['distance'] ?? null,
                'identity_path' => $recognitionData['identity_path'] ?? null
            ]
        ]);
    }
    
    // Método para calcular o próximo horário de registro esperado
    private function getNextRegisterTime($userId)
    {
        try {
            $now = Carbon::now();
            
            if ($now->isWeekend()) {
                return 'Próxima segunda-feira às ' . self::ENTRY_TIME;
            }
            
            $today = $now->format('Y-m-d');
            $lastRecord = AttendanceRecord::where('user_id', $userId)
                ->whereDate('created_at', $today)
                ->first();
            
            if (!$lastRecord || !$lastRecord->entry_time) {
                return self::ENTRY_TIME . ' (Entrada)';
            }
            
            if (!$lastRecord->exit_time) {
                return self::EXIT_TIME . ' (Saída)';
            }
            
            // Se já registrou entrada e saída hoje
            $tomorrow = $now->addDay();
            if ($tomorrow->isWeekend()) {
                return 'Próxima segunda-feira às ' . self::ENTRY_TIME;
            }
            
            return 'Amanhã às ' . self::ENTRY_TIME;
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular próximo registro: ' . $e->getMessage());
            return self::ENTRY_TIME;
        }
    }
    
    // Método para calcular horas registradas
    private function calculateHoursRegistered($userId)
    {
        try {
            $registros = AttendanceRecord::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->whereNotNull('entry_time')
                ->whereNotNull('exit_time')
                ->get();
            
            $totalMinutos = 0;
            
            foreach ($registros as $registro) {
                $entrada = Carbon::parse($registro->entry_time);
                $saida = Carbon::parse($registro->exit_time);
                $diffMinutos = $entrada->diffInMinutes($saida);
                $totalMinutos += $diffMinutos;
            }
            
            $horas = floor($totalMinutos / 60);
            $minutos = $totalMinutos % 60;
            
            return $horas . 'h' . ($minutos > 0 ? sprintf('%02d', $minutos) : '');
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular horas: ' . $e->getMessage());
            return '0h';
        }
    }
    
    // Método para calcular a frequência
    private function calculateAttendancePercentage($userId)
    {
        try {
            $diasUteis = $this->getBusinessDaysInMonth();
            
            $diasComRegistro = AttendanceRecord::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->whereNotNull('entry_time')
                ->distinct()
                ->count(DB::raw('DATE(created_at)'));
            
            $percentual = ($diasUteis > 0) ? round(($diasComRegistro / $diasUteis) * 100) : 0;
            
            return $percentual . '%';
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular frequência: ' . $e->getMessage());
            return '0%';
        }
    }
    
    // Método auxiliar para contar dias úteis no mês
    private function getBusinessDaysInMonth()
    {
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        if ($now->day < $endOfMonth->day) {
            $endOfMonth = $now;
        }
        
        $diasUteis = 0;
        $currentDay = $startOfMonth->copy();
        
        while ($currentDay->lte($endOfMonth)) {
            if (!$currentDay->isWeekend()) {
                $diasUteis++;
            }
            $currentDay->addDay();
        }
        
        return $diasUteis;
    }
}