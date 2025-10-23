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

        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $hoursRegistered = $this->calculateHoursRegistered($user->id);
        $attendance = $this->calculateAttendancePercentage($user->id);
        $nextRegister = $this->getNextRegisterTime($user->id);

        $todayName = strtolower(Carbon::now()->englishDayOfWeek);

        if (!empty($user->schedule) && is_array($user->schedule) && isset($user->schedule[$todayName])) {
            $entryTime = $user->schedule[$todayName]['entry'] ?? '14:00';
            $exitTime = $user->schedule[$todayName]['exit'] ?? '18:00';
        } else {
            $entryTime = $user->entry_time ?? '14:00';
            $exitTime = $user->exit_time ?? '18:00';
        }

        $scheduleFormatted = $this->formatSchedule($user);

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

    private function isAllowedDay($user, Carbon $date)
    {
        $dayName = strtolower($date->englishDayOfWeek);

        if (!empty($user->schedule) && is_array($user->schedule)) {
            return isset($user->schedule[$dayName]);
        }

        if (!empty($user->days_of_week)) {
            return in_array($dayName, $user->days_of_week);
        }

        return !$date->isWeekend();
    }

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

        if (!empty($user->schedule) && is_array($user->schedule)) {
            foreach ($user->schedule as $day => $times) {
                $dayLabel = $dayNames[$day] ?? ucfirst($day);
                $formattedSchedule[] = [
                    'day' => $dayLabel,
                    'entry' => $times['entry'] ?? '14:00',
                    'exit' => $times['exit'] ?? '18:00'
                ];
            }
        } else if (!empty($user->days_of_week)) {
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
        } else {
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

    private function calculateHoursRegistered($userId)
    {
        try {
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

            $horas = floor($totalMinutos / 60);
            $minutos = $totalMinutos % 60;

            return $horas . 'h' . ($minutos > 0 ? $minutos : '');
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular horas: ' . $e->getMessage());
            return '0h';
        }
    }

    private function calculateAttendancePercentage($userId)
    {
        try {
            $diasUteis = $this->getBusinessDaysInMonth();

            $diasComRegistro = AttendanceRecord::where('user_id', $userId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
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

    private function getNextRegisterTime($userId)
    {
        try {
            $now = Carbon::now();
            $user = \App\Models\User::find($userId);

            if (!$this->isAllowedDay($user, $now)) {
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

    private function getTimesForDay($user, Carbon $date)
    {
        $dayName = strtolower($date->englishDayOfWeek);

        if (!empty($user->schedule) && is_array($user->schedule) && isset($user->schedule[$dayName])) {
            return [
                'entry' => $user->schedule[$dayName]['entry'] ?? '14:00',
                'exit' => $user->schedule[$dayName]['exit'] ?? '18:00'
            ];
        }

        return [
            'entry' => $user->entry_time ?? '14:00',
            'exit' => $user->exit_time ?? '18:00'
        ];
    }

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
            if ($currentDay->dayOfWeek !== 0 && $currentDay->dayOfWeek !== 6) {
                $diasUteis++;
            }
            $currentDay->addDay();
        }

        return $diasUteis;
    }
}