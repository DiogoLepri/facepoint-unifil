@extends('layouts.app')

@section('title', 'Dashboard do Aluno')

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
    .stat-card {
        text-align: center;
        padding: 15px;
    }
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #003366;
    }
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    table.attendance-table th,
    table.attendance-table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    .attendance-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .status-confirmed {
        background-color: #d4edda;
        color: #155724;
    }
    .status-registered {
        background-color: #d1ecf1;
        color: #0c5460;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.create') }}">Registrar Ponto</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.history') }}">Histórico</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile') }}">Perfil</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Bem-vindo, {{ Auth::user()->name }}!</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">24h</div>
                            <div class="stat-label">Horas Registradas</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">95%</div>
                            <div class="stat-label">Frequência</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">14:00</div>
                            <div class="stat-label">Próximo Registro</div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Meus Últimos Registros</h5>
                <div class="table-responsive">
                    <table class="table attendance-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendances as $attendance)
                            <tr>
                                <td>{{ date('d/m/Y', strtotime($attendance->created_at)) }}</td>
                                <td>{{ $attendance->entry_time ? date('H:i', strtotime($attendance->entry_time)) : '-' }}</td>
                                <td>{{ $attendance->exit_time ? date('H:i', strtotime($attendance->exit_time)) : '-' }}</td>
                                <td>
                                    <span class="attendance-status {{ $attendance->status == 'confirmed' ? 'status-confirmed' : 'status-registered' }}">
                                        {{ $attendance->status == 'confirmed' ? 'Confirmado' : 'Registrado' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="{{ route('attendance.create') }}" class="btn btn-primary">REGISTRAR PONTO</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection