<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Horários padrão (usados como fallback se o usuário não tiver horários definidos)
    const DEFAULT_ENTRY_TIME = '14:00';
    const DEFAULT_EXIT_TIME = '18:00';
    const TOLERANCE_MINUTES = 15; // Tolerância de 15 minutos

    /**
     * Check if the user is allowed to register attendance on the given day
     */
    private function isAllowedDay($user, Carbon $date)
    {
        $dayName = strtolower($date->englishDayOfWeek);

        // Check new schedule format first
        if (!empty($user->schedule) && is_array($user->schedule)) {
            return isset($user->schedule[$dayName]);
        }

        // Fallback to old format
        if (empty($user->days_of_week)) {
            return !$date->isWeekend();
        }

        return in_array($dayName, $user->days_of_week);
    }

    /**
     * Get entry and exit times for a specific day
     */
    private function getTimesForDay($user, Carbon $date)
    {
        $dayName = strtolower($date->englishDayOfWeek);

        // Check new schedule format first
        if (!empty($user->schedule) && is_array($user->schedule) && isset($user->schedule[$dayName])) {
            return [
                'entry' => $user->schedule[$dayName]['entry'] ?? self::DEFAULT_ENTRY_TIME,
                'exit' => $user->schedule[$dayName]['exit'] ?? self::DEFAULT_EXIT_TIME
            ];
        }

        // Fallback to old format
        return [
            'entry' => $user->entry_time ?? self::DEFAULT_ENTRY_TIME,
            'exit' => $user->exit_time ?? self::DEFAULT_EXIT_TIME
        ];
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

            // Verificar se o usuário pode registrar ponto neste dia
            if (!$this->isAllowedDay($user, $now)) {
                $allowedDaysText = empty($user->days_of_week)
                    ? 'dias úteis (segunda a sexta)'
                    : implode(', ', array_map('ucfirst', $user->days_of_week));

                return response()->json([
                    'success' => false,
                    'message' => "Registros de ponto só são permitidos em: {$allowedDaysText}."
                ]);
            }
            
            // Verificar se já existe um registro para hoje
            $today = $now->format('Y-m-d');
            $existingRecord = AttendanceRecord::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->first();
            
            $punchType = $existingRecord && $existingRecord->entry_time ? 'exit' : 'entry';
            // Get day-specific times
            $times = $this->getTimesForDay($user, $now);
            $expectedTime = $punchType === 'entry' ? $times['entry'] : $times['exit'];
            $expectedDateTime = Carbon::parse($today . ' ' . $expectedTime);
            
            // Calcular diferença em minutos
            $minutesDifference = $now->diffInMinutes($expectedDateTime, false);
            $isEarly = $minutesDifference > 0;
            $isLate = $minutesDifference < -self::TOLERANCE_MINUTES;

            // Justificativa é opcional
            $justification = $request->input('justification');
            
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
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            $user = Auth::user();
            $now = Carbon::now();

            // Verificar se o usuário pode registrar ponto neste dia
            if (!$this->isAllowedDay($user, $now)) {
                $allowedDaysText = empty($user->days_of_week)
                    ? 'dias úteis (segunda a sexta)'
                    : implode(', ', array_map('ucfirst', $user->days_of_week));

                return response()->json([
                    'success' => true,
                    'is_not_allowed_day' => true,
                    'message' => "Hoje não é um dia de registro. Seus dias são: {$allowedDaysText}."
                ]);
            }
        
        // Obter registro de hoje
        $today = $now->format('Y-m-d');
        $todayRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();
        
        $nextPunchType = $todayRecord && $todayRecord->entry_time ? 'exit' : 'entry';
        // Get day-specific times
        $times = $this->getTimesForDay($user, $now);
        $expectedTime = $nextPunchType === 'entry' ? $times['entry'] : $times['exit'];

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
                    'entry' => $times['entry'],
                    'exit' => $times['exit']
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao verificar status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
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

        // Verificar se o usuário pode registrar ponto neste dia
        if (!$this->isAllowedDay($user, $now)) {
            $allowedDaysText = empty($user->days_of_week)
                ? 'dias úteis (segunda a sexta)'
                : implode(', ', array_map('ucfirst', $user->days_of_week));

            return response()->json([
                'success' => false,
                'message' => "Registros de ponto só são permitidos em: {$allowedDaysText}."
            ]);
        }
        
        $today = $now->format('Y-m-d');
        $existingRecord = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->first();
        
        $punchType = $existingRecord && $existingRecord->entry_time ? 'exit' : 'entry';
        // Get day-specific times
        $times = $this->getTimesForDay($user, $now);
        $expectedTime = $punchType === 'entry' ? $times['entry'] : $times['exit'];
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
            $user = User::find($userId);

            // Check if today is an allowed day
            if (!$this->isAllowedDay($user, $now)) {
                // Find next allowed day
                $nextDay = $now->copy()->addDay();
                $daysChecked = 0;

                while (!$this->isAllowedDay($user, $nextDay) && $daysChecked < 7) {
                    $nextDay->addDay();
                    $daysChecked++;
                }

                if ($daysChecked < 7) {
                    $nextDayTimes = $this->getTimesForDay($user, $nextDay);
                    $dayName = $nextDay->locale('pt_BR')->dayName;
                    return ucfirst($dayName) . ' às ' . $nextDayTimes['entry'];
                }

                return 'Nenhum dia disponível';
            }

            // Get today's times
            $times = $this->getTimesForDay($user, $now);

            $today = $now->format('Y-m-d');
            $lastRecord = AttendanceRecord::where('user_id', $userId)
                ->whereDate('created_at', $today)
                ->first();

            if (!$lastRecord || !$lastRecord->entry_time) {
                return $times['entry'] . ' (Entrada)';
            }

            if (!$lastRecord->exit_time) {
                return $times['exit'] . ' (Saída)';
            }

            // Se já registrou entrada e saída hoje, buscar próximo dia permitido
            $nextDay = $now->copy()->addDay();
            $daysChecked = 0;

            while (!$this->isAllowedDay($user, $nextDay) && $daysChecked < 7) {
                $nextDay->addDay();
                $daysChecked++;
            }

            if ($daysChecked < 7) {
                $nextDayTimes = $this->getTimesForDay($user, $nextDay);
                $dayName = $nextDay->locale('pt_BR')->dayName;
                $prefix = $daysChecked === 0 ? 'Amanhã' : ucfirst($dayName);
                return $prefix . ' às ' . $nextDayTimes['entry'];
            }

            return 'Nenhum dia disponível';
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular próximo registro: ' . $e->getMessage());
            return self::DEFAULT_ENTRY_TIME;
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
            
            if ($horas > 0 && $minutos > 0) {
                return $horas . 'h ' . sprintf('%02d', $minutos) . 'min';
            } elseif ($horas > 0) {
                return $horas . 'h';
            } else {
                return $minutos . 'min';
            }
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
                ->selectRaw('DATE(created_at) as attendance_date')
                ->groupBy('attendance_date')
                ->get()
                ->count();
            
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