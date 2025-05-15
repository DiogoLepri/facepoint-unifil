@extends('layouts.app')

@section('title', 'Painel Administrativo')

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
    .activity-table th,
    .activity-table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    .btn-outline-primary {
        color: #003366;
        border-color: #003366;
    }
    .btn-outline-primary:hover {
        background-color: #003366;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users.index') }}">Alunos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.config') }}">Configurações</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Painel de Controle</h5>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">42</div>
                            <div class="stat-label">Alunos Presentes</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">5</div>
                            <div class="stat-label">Instâncias Pendentes</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="stat-number">93%</div>
                            <div class="stat-label">Frequência Geral</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5>Atividade Recente</h5>
                        <table class="table activity-table">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Ação</th>
                                    <th>Horário</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Carlos Silva</td>
                                    <td>Entrada</td>
                                    <td>08:30</td>
                                    <td>09/04/2015</td>
                                    <td><span class="badge bg-success">Confirmado</span></td>
                                </tr>
                                <tr>
                                    <td>Ana Oliveira</td>
                                    <td>Entrada</td>
                                    <td>08:20</td>
                                    <td>09/04/2015</td>
                                    <td><span class="badge bg-success">Confirmado</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Ações Rápidas</h5>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.reports') }}" class="btn btn-primary">GERAR RELATÓRIO</a>
                            <a href="{{ route('users.create') }}" class="btn btn-outline-primary">REGISTRAR ALUNO</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection