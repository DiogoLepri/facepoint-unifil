@extends('layouts.app')

@section('title', 'Histórico de Registros - NPI')

@section('styles')
<style>
    .nav-pills .nav-link.active {
        background-color: transparent;
        color: #003366;
        border-bottom: 3px solid #003366;
        border-radius: 0;
    }
    .nav-pills .nav-link {
        color: #6c757d;
    }
    
    /* Estatísticas do período */
    .period-stats {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .stat-item {
        text-align: center;
    }
    .stat-number {
        font-size: 2.2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    /* Filtros */
    .filter-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 25px;
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 25px;
    }
    .filter-card h6 {
        color: #003366;
        margin-bottom: 20px;
        font-weight: bold;
    }
    .btn-filter {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        border: none;
        border-radius: 10px;
        padding: 10px 25px;
        font-weight: bold;
    }
    .btn-clear {
        background: #6c757d;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
    }
    
    /* Tabela de registros */
    .records-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
    }
    .records-card .card-header {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        border: none;
        padding: 20px 25px;
    }
    .records-table {
        margin: 0;
    }
    .records-table thead {
        background: #f8f9fa;
    }
    .records-table th {
        border: none;
        padding: 15px 20px;
        font-weight: 600;
        color: #003366;
    }
    .records-table td {
        border: none;
        padding: 15px 20px;
        vertical-align: middle;
    }
    .records-table tbody tr {
        border-bottom: 1px solid #f1f3f4;
        transition: background-color 0.2s;
    }
    .records-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Status badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }
    .status-normal {
        background: #d4edda;
        color: #155724;
    }
    .status-justified {
        background: #fff3cd;
        color: #856404;
    }
    .status-irregular {
        background: #f8d7da;
        color: #721c24;
    }
    .status-incomplete {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    /* Indicadores de tempo */
    .time-indicator {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 8px;
    }
    .time-early {
        background: #cce5ff;
        color: #0066cc;
    }
    .time-late {
        background: #ffe6e6;
        color: #cc0000;
    }
    
    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    /* Paginação */
    .pagination {
        justify-content: center;
        margin-top: 30px;
    }
    .pagination .page-link {
        border-radius: 10px;
        margin: 0 2px;
        border: none;
        color: #003366;
    }
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        border: none;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('attendance.history') }}">Histórico</a>
            </li>
        </ul>
    </div>
</div>

<!-- Estatísticas do Período -->
<div class="period-stats">
    <div class="row">
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number">{{ $attendances->total() }}</div>
                <div class="stat-label">Total de Registros</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number">
                    @php
                        $completeRecords = $attendances->where('entry_time', '!=', null)->where('exit_time', '!=', null)->count();
                    @endphp
                    {{ $completeRecords }}
                </div>
                <div class="stat-label">Dias Completos</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number">
                    @php
                        $totalMinutes = 0;
                        foreach($attendances as $record) {
                            if($record->entry_time && $record->exit_time) {
                                $entry = new DateTime($record->entry_time);
                                $exit = new DateTime($record->exit_time);
                                $totalMinutes += $entry->diff($exit)->h * 60 + $entry->diff($exit)->i;
                            }
                        }
                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;
                        echo $hours . 'h' . ($minutes > 0 ? ' ' . sprintf('%02d', $minutes) . 'min' : '');
                    @endphp
                </div>
                <div class="stat-label">Horas no Período</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-item">
                <div class="stat-number">
                    @php
                        $irregularCount = $attendances->where(function($record) {
                            return $record->justification || $record->is_early || $record->is_late;
                        })->count();
                    @endphp
                    {{ $irregularCount }}
                </div>
                <div class="stat-label">Registros Irregulares</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="filter-card">
    <h6><i class="fas fa-filter me-2"></i>Filtros de Pesquisa</h6>
    <form method="GET" action="{{ route('attendance.history') }}">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-filter">
                    <i class="fas fa-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('attendance.history') }}" class="btn btn-secondary btn-clear">
                    <i class="fas fa-times me-1"></i>Limpar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabela de Registros -->
<div class="records-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>Histórico de Registros no NPI
        </h5>
    </div>
    <div class="card-body p-0">
        @if($attendances->count() > 0)
            <div class="table-responsive">
                <table class="table records-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendances as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->created_at->format('d/m/Y') }}</strong>
                                <br><small class="text-muted">{{ $record->created_at->format('l') }}</small>
                            </td>
                            <td>
                                @if($record->entry_time)
                                    <strong>{{ $record->entry_time->format('H:i') }}</strong>
                                    @if($record->is_early)
                                        <span class="time-indicator time-early">Adiantado</span>
                                    @elseif($record->is_late)
                                        <span class="time-indicator time-late">Atrasado</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($record->exit_time)
                                    <strong>{{ $record->exit_time->format('H:i') }}</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($record->entry_time && $record->exit_time)
                                    @php
                                        $entry = new DateTime($record->entry_time);
                                        $exit = new DateTime($record->exit_time);
                                        $interval = $entry->diff($exit);
                                        $totalMinutes = $interval->h * 60 + $interval->i;
                                        $hours = floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;
                                    @endphp
                                    <strong>{{ $hours }}h {{ sprintf('%02d', $minutes) }}min</strong>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($record->justification)
                                    <span class="status-badge status-justified">Justificado</span>
                                @elseif($record->is_early || $record->is_late)
                                    <span class="status-badge status-irregular">Irregular</span>
                                @elseif($record->entry_time && $record->exit_time)
                                    <span class="status-badge status-normal">Normal</span>
                                @else
                                    <span class="status-badge status-incomplete">Incompleto</span>
                                @endif
                            </td>
                            <td>
                                @if($record->justification)
                                    <i class="fas fa-comment-alt text-warning" 
                                       title="{{ $record->justification }}" 
                                       data-bs-toggle="tooltip"></i>
                                    <small class="text-muted">Com justificativa</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h5>Nenhum registro encontrado</h5>
                <p>Não há registros de ponto para o período selecionado.</p>
            </div>
        @endif
    </div>
</div>

<!-- Paginação -->
@if($attendances->hasPages())
    <div class="d-flex justify-content-center">
        {{ $attendances->appends(request()->query())->links() }}
    </div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-submit do formulário quando datas mudarem
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            if (endDate.value && this.value) {
                this.form.submit();
            }
        });
        
        endDate.addEventListener('change', function() {
            if (startDate.value && this.value) {
                this.form.submit();
            }
        });
    }
});
</script>
@endsection