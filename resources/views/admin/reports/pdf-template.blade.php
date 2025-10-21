<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #f08223;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #f08223;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        
        .header .institution {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header .system-name {
            font-size: 14px;
            color: #666;
        }
        
        .report-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .report-info h2 {
            color: #f08223;
            font-size: 16px;
            margin: 0 0 10px 0;
        }
        
        .report-info .description {
            font-style: italic;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .info-item {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border-left: 3px solid #f08223;
        }
        
        .info-item strong {
            color: #f08223;
        }
        
        .statistics {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .statistics h3 {
            color: #0c7489;
            margin: 0 0 15px 0;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background: white;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #d1ecf1;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #0c7489;
            display: block;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .attendance-table th {
            background-color: #f08223;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        
        .attendance-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .attendance-table td {
            font-size: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="institution">UNIFIL - Centro Universitário Filadélfia</div>
        <div class="system-name">Sistema de Controle de Ponto</div>
        <h1>{{ $title }}</h1>
    </div>
    
    <div class="report-info">
        <h2>Descrição do Relatório</h2>
        <div class="description">{{ $description }}</div>
        
        <div class="info-grid">
            <div class="info-item">
                <strong>Período:</strong> {{ $start_date }} até {{ $end_date }}
            </div>
            <div class="info-item">
                <strong>Curso:</strong> {{ $course }}
            </div>
            <div class="info-item">
                <strong>Gerado em:</strong> {{ $generated_at }}
            </div>
            <div class="info-item">
                <strong>Tipo:</strong> {{ ucfirst($period) }}
            </div>
        </div>
    </div>
    
    <div class="statistics">
        <h3>Estatísticas do Período</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $data['total_students'] }}</span>
                <div class="stat-label">Total de Estudantes</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $data['unique_students'] }}</span>
                <div class="stat-label">Estudantes Presentes</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $data['total_records'] }}</span>
                <div class="stat-label">Total de Registros</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $data['attendance_rate'] }}%</span>
                <div class="stat-label">Taxa de Presença</div>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: #dc3545;">{{ $data['late_count'] }}</span>
                <div class="stat-label">Registros com Atraso</div>
            </div>
        </div>
    </div>
    
    @if($data['attendances']->count() > 0)
        <h3 style="color: #333; margin-bottom: 15px;">Registros de Presença</h3>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                    <th>Horas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['attendances'] as $attendance)
                    @php
                        $horasTrabalhadas = '';
                        $status = '';
                        $statusColor = '#666';

                        if ($attendance->entry_time && $attendance->exit_time) {
                            $entrada = \Carbon\Carbon::parse($attendance->entry_time);
                            $saida = \Carbon\Carbon::parse($attendance->exit_time);
                            $diff = $entrada->diff($saida);
                            $horasTrabalhadas = sprintf('%02dh%02dm', $diff->h, $diff->i);

                            // Status baseado em is_late e is_early
                            if ($attendance->is_late) {
                                $status = 'Atrasado';
                                $statusColor = '#dc3545';
                            } elseif ($attendance->is_early) {
                                $status = 'Adiantado';
                                $statusColor = '#28a745';
                            } else {
                                $status = 'No Horário';
                                $statusColor = '#28a745';
                            }
                        } elseif ($attendance->entry_time) {
                            $status = 'Sem Saída';
                            $statusColor = '#ffc107';

                            if ($attendance->is_late) {
                                $status = 'Atrasado (sem saída)';
                                $statusColor = '#dc3545';
                            }
                        } else {
                            $status = 'Incompleto';
                            $statusColor = '#6c757d';
                        }
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($attendance->created_at)->format('d/m/Y') }}</td>
                        <td>{{ $attendance->user->name }}</td>
                        <td>{{ $attendance->user->matricula }}</td>
                        <td>
                            @if($attendance->entry_time)
                                {{ \Carbon\Carbon::parse($attendance->entry_time)->format('H:i') }}
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($attendance->exit_time)
                                {{ \Carbon\Carbon::parse($attendance->exit_time)->format('H:i') }}
                            @else
                                <span style="color: #999;">-</span>
                            @endif
                        </td>
                        <td>{{ $horasTrabalhadas ?: '-' }}</td>
                        <td style="color: {{ $statusColor }}; font-weight: bold;">{{ $status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($data['total_hours'])
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #f08223;">
            <strong style="color: #f08223;">Total de Horas Trabalhadas no Período:</strong>
            <span style="font-size: 16px; font-weight: bold;">{{ $data['total_hours'] }}</span>
        </div>
        @endif
    @else
        <div class="no-data">
            <p>Nenhum registro de presença encontrado para o período selecionado.</p>
        </div>
    @endif
    
    <div class="footer">
        <p>
            <strong>{{ $title }}</strong><br>
            Documento gerado automaticamente pelo Sistema de Controle de Ponto UNIFIL<br>
            Este relatório contém informações confidenciais e deve ser tratado de acordo com as políticas de privacidade da instituição.
        </p>
    </div>
</body>
</html>