<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
        
        return view('aluno.dashboard', compact('attendances', 'hoursRegistered', 'attendance', 'nextRegister'));
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
                ->distinct('date')
                ->count(DB::raw('DATE(created_at)'));
            
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
            return '08:00'; // Valor padrão em caso de erro
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