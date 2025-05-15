<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Contar usuários ativos
        $activeUsers = User::where('role', 'aluno')->count();
        
        // Contar registros de hoje
        $today = now()->format('Y-m-d');
        $todayAttendances = AttendanceRecord::whereDate('created_at', $today)->count();
        
        // Calcular taxa de presença
        $totalExpected = $activeUsers * 2; // 2 registros por dia (entrada e saída)
        $attendanceRate = $totalExpected > 0 ? round(($todayAttendances / $totalExpected) * 100) : 0;
        
        // Obter registros recentes
        $recentActivity = AttendanceRecord::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.dashboard', compact('activeUsers', 'todayAttendances', 'attendanceRate', 'recentActivity'));
    }
    
    public function reports()
    {
        return view('admin.relatorios.index');
    }
    
    public function generateReport(Request $request)
    {
        // Implementar geração de relatórios
        $reportType = $request->report_type;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $filterBy = $request->filter_by;
        $format = $request->format;
        
        // Aqui você implementaria a lógica de geração de relatórios
        // ...
        
        return back()->with('success', 'Relatório gerado com sucesso!');
    }
    
    public function exportReport(Request $request)
    {
        // Implementar exportação de relatórios
        // ...
        
        return back()->with('success', 'Dados exportados com sucesso!');
    }
    
    public function config()
    {
        return view('admin.config');
    }
    
    public function updateConfig(Request $request)
    {
        // Implementar atualização de configurações
        // ...
        
        return back()->with('success', 'Configurações atualizadas com sucesso!');
    }
}