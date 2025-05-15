<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        
        // Calcular estatísticas
        $totalHours = $this->calculateTotalHours($user->id);
        $attendanceRate = $this->calculateAttendanceRate($user->id);
        $nextSchedule = $this->getNextSchedule($user->id);
        
        return view('aluno.dashboard', compact('attendances', 'totalHours', 'attendanceRate', 'nextSchedule'));
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
    
    private function calculateTotalHours($userId)
    {
        // Implementar cálculo de horas totais
        return '24h';
    }
    
    private function calculateAttendanceRate($userId)
    {
        // Implementar cálculo de taxa de frequência
        return '95%';
    }
    
    private function getNextSchedule($userId)
    {
        // Implementar lógica para obter próximo horário programado
        return '14:00';
    }
}