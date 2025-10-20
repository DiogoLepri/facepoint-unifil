<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Obter os últimos 5 registros de presença do usuário
        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calcular estatísticas usando os mesmos métodos do AttendanceController
        $hoursRegistered = $this->calculateHoursRegistered($user->id);
        $attendance = $this->calculateAttendancePercentage($user->id);
        $nextRegister = $this->getNextRegisterTime($user->id);

        // Obter horários e dias personalizados do usuário
        $todayName = strtolower(Carbon::now()->englishDayOfWeek);

        // Check new schedule format first, then fall back to old format
        if (!empty($user->schedule) && is_array($user->schedule) && isset($user->schedule[$todayName])) {
            $entryTime = $user->schedule[$todayName]['entry'] ?? '14:00';
            $exitTime = $user->schedule[$todayName]['exit'] ?? '18:00';
        } else {
            $entryTime = $user->entry_time ?? '14:00';
            $exitTime = $user->exit_time ?? '18:00';
        }

        // Formatar horário completo do usuário (todos os dias)
        $scheduleFormatted = $this->formatSchedule($user);

        // Verificar se hoje é um dia permitido
        $isAllowedToday = $this->isAllowedDay($user, Carbon::now());

        return view('aluno.dashboard', compact(
            'attendances',
            'hoursRegistered',
            'attendance',
            'nextRegister',
            'entryTime',
            'exitTime',
            'scheduleFormatted',
            'isAllowedToday'
        ));
    }

    /**
     * Check if the user is allowed to register attendance on the given day
     */
    private function isAllowedDay($user, Carbon $date)
    {
        // Get the day name in lowercase (e.g., "monday", "tuesday")
        $dayName = strtolower($date->englishDayOfWeek);

        // Check new schedule format first
        if (!empty($user->schedule) && is_array($user->schedule)) {
            return isset($user->schedule[$dayName]);
        }

        // Fallback to old format
        if (!empty($user->days_of_week)) {
            return in_array($dayName, $user->days_of_week);
        }

        // Default: allow all weekdays (Monday-Friday)
        return !$date->isWeekend();
    }

    /**
     * Format user schedule for display
     */
    private function formatSchedule($user)
    {
        $dayNames = [
            'monday' => 'Segunda-feira',
            'tuesday' => 'Terça-feira',
            'wednesday' => 'Quarta-feira',
            'thursday' => 'Quinta-feira',
            'friday' => 'Sexta-feira',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo'
        ];

        $formattedSchedule = [];

        // Check new schedule format first
        if (!empty($user->schedule) && is_array($user->schedule)) {
            foreach ($user->schedule as $day => $times) {
                $dayLabel = $dayNames[$day] ?? ucfirst($day);
                $formattedSchedule[] = [
                    'day' => $dayLabel,
                    'entry' => $times['entry'] ?? '14:00',
                    'exit' => $times['exit'] ?? '18:00'
                ];
            }
        }
        // Fallback to old format
        else if (!empty($user->days_of_week)) {
            $entryTime = $user->entry_time ?? '14:00';
            $exitTime = $user->exit_time ?? '18:00';

            foreach ($user->days_of_week as $day) {
                $dayLabel = $dayNames[strtolower($day)] ?? ucfirst($day);
                $formattedSchedule[] = [
                    'day' => $dayLabel,
                    'entry' => $entryTime,
                    'exit' => $exitTime
                ];
            }
        }
        // Default: weekdays with default times
        else {
            $defaultDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($defaultDays as $day) {
                $formattedSchedule[] = [
                    'day' => $dayNames[$day],
                    'entry' => '14:00',
                    'exit' => '18:00'
                ];
            }
        }

        return $formattedSchedule;
    }
    
    public function profile()
    {
        $user = Auth::user();
        return view('aluno.profile', compact('user'));
    }
    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'matricula' => 'required|string|max:20|unique:users,matricula,' . $user->id,
            'curso' => 'required|string|max:255',
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);
        
        // Verificar senha atual
        if ($request->current_password && !Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'A senha atual está incorreta.']);
        }
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->matricula = $request->matricula;
        $user->curso = $request->curso;
        
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        return back()->with('success', 'Perfil atualizado com sucesso!');
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
            return '0h'; // Valor padrão em caso de erro
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
                ->selectRaw('DATE(created_at) as attendance_date')
                ->groupBy('attendance_date')
                ->get()
                ->count();
            
            // Calcular percentual
            $percentual = ($diasUteis > 0) ? round(($diasComRegistro / $diasUteis) * 100) : 0;
            
            return $percentual . '%';
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular frequência: ' . $e->getMessage());
            return '0%'; // Valor padrão em caso de erro
        }
    }

    // Método para calcular o próximo horário de registro esperado
    private function getNextRegisterTime($userId)
    {
        try {
            $now = Carbon::now();
            $user = \App\Models\User::find($userId);

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
            return '14:00';
        }
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
                'entry' => $user->schedule[$dayName]['entry'] ?? '14:00',
                'exit' => $user->schedule[$dayName]['exit'] ?? '18:00'
            ];
        }

        // Fallback to old format
        return [
            'entry' => $user->entry_time ?? '14:00',
            'exit' => $user->exit_time ?? '18:00'
        ];
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