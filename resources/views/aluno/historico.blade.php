@extends('layouts.app')

@section('title', 'Histórico de Registros')

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
                <a class="nav-link" href="{{ route('dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.create') }}">Registrar Ponto</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('attendance.history') }}">Histórico</a>
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
                <h5 class="card-title mb-4">Histórico de Registros</h5>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('attendance.history') }}" class="row g-3">
                            <div class="col-md-5">
                                <label for="start_date" class="form-label">Data Inicial</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-5">
                                <label for="end_date" class="form-label">Data Final</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table attendance-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Total Horas</th>
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
                                    @if($attendance->entry_time && $attendance->exit_time)
                                        @php
                                            $entry = new DateTime($attendance->entry_time);
                                            $exit = new DateTime($attendance->exit_time);
                                            $interval = $entry->diff($exit);
                                            echo $interval->format('%H:%I');
                                        @endphp
                                    @else
                                        -
                                    @endif
                                </td>
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
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $attendances->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection