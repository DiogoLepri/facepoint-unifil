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
            \Log::info('Usuário: ' . $user->id);
            
            // Verificar se já existe um registro para hoje
            $today = now()->format('Y-m-d');
            $existingRecord = AttendanceRecord::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->first();
            
            if ($existingRecord) {
                \Log::info('Registro existente encontrado: ' . $existingRecord->id);
                
                // Se já tem entrada, registra saída
                if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                    $existingRecord->exit_time = now();
                    $existingRecord->save();
                    
                    \Log::info('Saída registrada para registro: ' . $existingRecord->id);
                    return redirect()->route('dashboard')->with('success', 'Saída registrada com sucesso!');
                } else {
                    \Log::info('Usuário já registrou entrada e saída hoje');
                    return redirect()->route('dashboard')->with('error', 'Você já registrou entrada e saída hoje.');
                }
            } else {
                \Log::info('Criando novo registro para usuário: ' . $user->id);
                
                // Criar novo registro com DB::insert para evitar problemas com formatos
                DB::insert(
                    'insert into attendance_records (user_id, entry_time, status, created_at, updated_at) values (?, ?, ?, ?, ?)',
                    [$user->id, now(), 'registered', now(), now()]
                );
                
                \Log::info('Novo registro criado para o usuário: ' . $user->id);
                return redirect()->route('dashboard')->with('success', 'Entrada registrada com sucesso!');
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao registrar ponto: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->route('dashboard')->with('error', 'Erro ao registrar ponto: ' . $e->getMessage());
        }
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array'
        ]);
        
        $inputDescriptor = $request->face_descriptor;
        
        // Obter todos os usuários com registros faciais
        $users = User::whereHas('recognitionRecords')->with('recognitionRecords')->get();
        
        $bestMatch = null;
        $bestMatchDistance = 0.6; // Limiar máximo para considerar uma correspondência (menor é melhor)
        
        foreach ($users as $user) {
            foreach ($user->recognitionRecords as $record) {
                if ($record->face_descriptor) {
                    $storedDescriptor = json_decode($record->face_descriptor);
                    
                    if ($storedDescriptor) {
                        $distance = $this->calculateEuclideanDistance($inputDescriptor, $storedDescriptor);
                        
                        if ($distance < $bestMatchDistance) {
                            $bestMatchDistance = $distance;
                            $bestMatch = $user;
                        }
                    }
                }
            }
        }
        
        if ($bestMatch) {
            // Verificar se já existe um registro para hoje
            $today = now()->format('Y-m-d');
            $existingRecord = AttendanceRecord::where('user_id', $bestMatch->id)
                ->whereDate('created_at', $today)
                ->first();
            
            $type = 'Entrada';
            
            if ($existingRecord) {
                // Se já tem entrada, registra saída
                if ($existingRecord->entry_time && !$existingRecord->exit_time) {
                    $existingRecord->exit_time = now();
                    $existingRecord->save();
                    $type = 'Saída';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você já registrou entrada e saída hoje.'
                    ]);
                }
            } else {
                // Criar novo registro com DB::insert para evitar problemas com formatos
                DB::insert(
                    'insert into attendance_records (user_id, entry_time, status, created_at, updated_at) values (?, ?, ?, ?, ?)',
                    [$bestMatch->id, now(), 'registered', now(), now()]
                );
            }
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $bestMatch->id,
                    'name' => $bestMatch->name,
                ],
                'type' => $type
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Usuário não reconhecido'
        ]);
    }
    
    private function calculateEuclideanDistance($vec1, $vec2)
    {
        if (count($vec1) !== count($vec2)) {
            return 999; // Retorna um valor grande para indicar incompatibilidade
        }
        
        $sum = 0;
        for ($i = 0; $i < count($vec1); $i++) {
            $sum += pow($vec1[$i] - $vec2[$i], 2);
        }
        
        return sqrt($sum);
    }
    
    // Método para calcular o próximo horário de registro esperado
    private function getNextRegisterTime($userId)
    {
        try {
            // Verificar o último registro do usuário
            $lastRecord = AttendanceRecord::where('user_id', $userId)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->orderBy('created_at', 'desc')
                ->first();
            
            // Horários padrão de trabalho
            $morningStart = '08:00';
            $lunchStart = '12:00';
            $lunchEnd = '13:00';
            $eveningEnd = '17:00';
            
            // Horário atual
            $now = Carbon::now();
            $hour = (int)$now->format('H');
            
            if (!$lastRecord) {
                // Se não houver registro hoje, próximo é a entrada
                return $morningStart;
            }
            
            // Lógica para determinar o próximo registro esperado
            if (!$lastRecord->exit_time) {
                // Se registrou entrada pela manhã, próximo registro é a saída para almoço
                if ($hour < 12) {
                    return $lunchStart;
                }
                // Se registrou entrada após o almoço, próximo registro é a saída do dia
                return $eveningEnd;
            } else {
                // Se registrou saída antes das 13h, é provável que seja para almoço, então próximo é retorno do almoço
                if ($hour < 13) {
                    return $lunchEnd;
                }
                // Se registrou saída após as 17h, provavelmente é a saída do dia, então próximo é entrada do dia seguinte
                return $morningStart;
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular próximo registro: ' . $e->getMessage());
            return '14:00'; // Valor padrão em caso de erro
        }
    }
    
    // Método para calcular horas registradas
    private function calculateHoursRegistered($userId)
    {
        try {
            // Buscar os registros do mês atual
            $registros = AttendanceRecord::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->get();
            
            $totalMinutos = 0;
            
            foreach ($registros as $registro) {
                if ($registro->entry_time && $registro->exit_time) {
                    $entrada = Carbon::parse($registro->entry_time);
                    $saida = Carbon::parse($registro->exit_time);
                    
                    $diffMinutos = $entrada->diffInMinutes($saida);
                    $totalMinutos += $diffMinutos;
                }
            }
            
            // Converter para horas:minutos
            $horas = floor($totalMinutos / 60);
            $minutos = $totalMinutos % 60;
            
            return $horas . 'h' . ($minutos > 0 ? $minutos : '');
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular horas: ' . $e->getMessage());
            return '24h'; // Valor padrão em caso de erro
        }
    }
    
    // Método para calcular a frequência
    private function calculateAttendancePercentage($userId)
    {
        try {
            // Número de dias úteis no mês atual
            $diasUteis = $this->getBusinessDaysInMonth();
            
            // Número de dias com registro
            $diasComRegistro = AttendanceRecord::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->distinct('date')
                ->count(DB::raw('DATE(created_at)'));
            
            // Calcular percentual
            $percentual = ($diasUteis > 0) ? round(($diasComRegistro / $diasUteis) * 100) : 0;
            
            return $percentual . '%';
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular frequência: ' . $e->getMessage());
            return '95%'; // Valor padrão em caso de erro
        }
    }
    
    // Método auxiliar para contar dias úteis no mês
    private function getBusinessDaysInMonth()
    {
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Se estamos no meio do mês, considerar apenas os dias até hoje
        if ($now->day < $endOfMonth->day) {
            $endOfMonth = $now;
        }
        
        $diasUteis = 0;
        $currentDay = $startOfMonth->copy();
        
        while ($currentDay->lte($endOfMonth)) {
            // 0 = domingo, 6 = sábado
            if ($currentDay->dayOfWeek !== 0 && $currentDay->dayOfWeek !== 6) {
                $diasUteis++;
            }
            $currentDay->addDay();
        }
        
        return $diasUteis;
    }
}