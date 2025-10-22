<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Contar usuários ativos
        $activeUsers = User::where('role', 'aluno')->count();
        
        // Obter registros recentes
        $recentActivity = AttendanceRecord::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.dashboard', compact('activeUsers', 'recentActivity'));
    }
    
    public function reports()
    {
        // Buscar todos os usuários alunos para o dropdown
        $users = User::where('role', 'aluno')->orderBy('name')->get();
        
        return view('admin.relatorios.index', compact('users'));
    }
    
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:attendance,user',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'filter_by' => 'nullable|string',
            'report_period' => 'required|in:daily,weekly,monthly',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $reportType = $request->report_type;
        $reportPeriod = $request->report_period;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $filterBy = $request->filter_by;
        $userId = $request->user_id;
        
        try {
            // Definir datas baseadas no período se não fornecidas
            if (!$startDate || !$endDate) {
                $dates = $this->getDateRange($reportPeriod);
                $startDate = $dates['start'];
                $endDate = $dates['end'];
            }
            
            // Buscar dados para o relatório
            $data = $this->getReportData($reportType, $startDate, $endDate, $filterBy, $userId);
            
            // Gerar PDF baseado no período
            $pdf = $this->generatePdfByPeriod($reportPeriod, $reportType, $data, $startDate, $endDate, $filterBy, $userId);
            
            $filename = $this->generateFilename($reportPeriod, $reportType, $startDate, $endDate);
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar relatório: ' . $e->getMessage());
            return back()->with('error', 'Erro ao gerar relatório. Tente novamente.');
        }
    }
    
    private function getDateRange($period)
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'daily':
                return [
                    'start' => $now->format('Y-m-d'),
                    'end' => $now->format('Y-m-d')
                ];
            case 'weekly':
                return [
                    'start' => $now->startOfWeek()->format('Y-m-d'),
                    'end' => $now->endOfWeek()->format('Y-m-d')
                ];
            case 'monthly':
                return [
                    'start' => $now->startOfMonth()->format('Y-m-d'),
                    'end' => $now->endOfMonth()->format('Y-m-d')
                ];
            default:
                return [
                    'start' => $now->format('Y-m-d'),
                    'end' => $now->format('Y-m-d')
                ];
        }
    }
    
    private function getReportData($reportType, $startDate, $endDate, $filterBy, $userId = null)
    {
        $query = AttendanceRecord::with('user')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Se é relatório por usuário, filtrar pelo usuário específico
        if ($reportType === 'user' && $userId) {
            $query->where('user_id', $userId);
        } elseif ($filterBy) {
            $query->whereHas('user', function($q) use ($filterBy) {
                $q->where('curso', $filterBy);
            });
        }
        
        $attendances = $query->orderBy('created_at', 'desc')->get();
        
        // Calcular estatísticas
        if ($reportType === 'user' && $userId) {
            $totalStudents = 1; // Para relatório de usuário específico
            $uniqueStudents = $attendances->pluck('user_id')->unique()->count();
        } else {
            $totalStudents = User::where('role', 'aluno')->when($filterBy, function($q) use ($filterBy) {
                return $q->where('curso', $filterBy);
            })->count();
            $uniqueStudents = $attendances->pluck('user_id')->unique()->count();
        }
        
        $totalRecords = $attendances->count();

        // Calcular total de horas trabalhadas
        $totalMinutes = 0;
        foreach ($attendances as $attendance) {
            if ($attendance->entry_time && $attendance->exit_time) {
                $entrada = \Carbon\Carbon::parse($attendance->entry_time);
                $saida = \Carbon\Carbon::parse($attendance->exit_time);
                $totalMinutes += $entrada->diffInMinutes($saida);
            }
        }

        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        $totalHoursFormatted = sprintf('%02dh%02dm', $totalHours, $remainingMinutes);

        // Contar registros com atraso
        $lateCount = $attendances->where('is_late', true)->count();

        return [
            'attendances' => $attendances,
            'total_students' => $totalStudents,
            'unique_students' => $uniqueStudents,
            'total_records' => $totalRecords,
            'attendance_rate' => $totalStudents > 0 ? round(($uniqueStudents / $totalStudents) * 100, 2) : 0,
            'selected_user' => $userId ? User::find($userId) : null,
            'total_hours' => $totalHoursFormatted,
            'late_count' => $lateCount,
        ];
    }
    
    private function generatePdfByPeriod($period, $reportType, $data, $startDate, $endDate, $filterBy, $userId = null)
    {
        // Determinar nome do curso para exibição
        if (empty($filterBy)) {
            $courseName = 'Todos os Cursos';
        } else {
            $courseName = $filterBy; // Já vem como texto completo do formulário
        }
        
        // Se é relatório por usuário, ajustar título e descrição
        if ($reportType === 'user' && $data['selected_user']) {
            $userName = $data['selected_user']->name;
            switch ($period) {
                case 'daily':
                    $title = "Relatório Diário de Presença - {$userName}";
                    $description = "Este relatório apresenta o controle de presença individual do estudante {$userName} em um dia específico, permitindo o acompanhamento detalhado da frequência diária do aluno.";
                    break;
                case 'weekly':
                    $title = "Relatório Semanal de Presença - {$userName}";
                    $description = "Este relatório consolida os dados de presença do estudante {$userName} durante uma semana completa, oferecendo uma visão detalhada dos padrões de frequência semanal do aluno.";
                    break;
                case 'monthly':
                    $title = "Relatório Mensal de Presença - {$userName}";
                    $description = "Este relatório compila os registros de presença do estudante {$userName} ao longo de um mês inteiro, proporcionando uma análise detalhada da frequência mensal e permitindo avaliação de desempenho acadêmico baseada na assiduidade.";
                    break;
                default:
                    $title = "Relatório de Presença - {$userName}";
                    $description = "Relatório individual de controle de presença do estudante {$userName}.";
            }
            $courseName = "Usuário: {$userName}";
        } else {
            switch ($period) {
                case 'daily':
                    $title = 'Relatório Diário de Presença';
                    $description = 'Este relatório apresenta o controle de presença dos estudantes em um dia específico, permitindo o acompanhamento detalhado da frequência diária dos alunos matriculados.';
                    break;
                case 'weekly':
                    $title = 'Relatório Semanal de Presença';
                    $description = 'Este relatório consolida os dados de presença dos estudantes durante uma semana completa, oferecendo uma visão abrangente dos padrões de frequência semanal e identificação de tendências de comparecimento.';
                    break;
                case 'monthly':
                    $title = 'Relatório Mensal de Presença';
                    $description = 'Este relatório compila os registros de presença dos estudantes ao longo de um mês inteiro, proporcionando uma análise estatística detalhada da frequência mensal e permitindo avaliações de desempenho acadêmico baseadas na assiduidade.';
                    break;
                default:
                    $title = 'Relatório de Presença';
                    $description = 'Relatório de controle de presença dos estudantes.';
            }
        }
        
        $viewData = [
            'title' => $title,
            'description' => $description,
            'period' => $period,
            'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
            'end_date' => Carbon::parse($endDate)->format('d/m/Y'),
            'course' => $courseName,
            'data' => $data,
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'report_type' => $reportType,
        ];
        
        return PDF::loadView('admin.reports.pdf-template', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
    }
    
    private function generateFilename($period, $reportType, $startDate, $endDate)
    {
        $periodNames = [
            'daily' => 'diario',
            'weekly' => 'semanal',
            'monthly' => 'mensal'
        ];
        
        $periodName = $periodNames[$period] ?? 'relatorio';
        $dateStr = Carbon::parse($startDate)->format('Y-m-d');
        
        return "relatorio_{$periodName}_{$dateStr}.pdf";
    }
}