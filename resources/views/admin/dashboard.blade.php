@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('styles')
<style>
    .nav-pills .nav-link.active {
        background-color: transparent;
        color: #f08223;
        border-bottom: 3px solid #f08223;
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
        color: #ffffff;
    }
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .btn-outline-primary {
        color: #f08223;
        border-color: #f08223;
    }
    .btn-outline-primary:hover {
        background-color: #f08223;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="row fade-in-down">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-home me-2"></i>Início
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports') }}">
                    <i class="fas fa-chart-bar me-2"></i>Relatórios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users.index') }}">
                    <i class="fas fa-users me-2"></i>Aluno
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="row fade-in-up">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-tachometer-alt me-2 text-primary-custom"></i>Painel de Controle</h5>
            </div>
            <div class="card-body">
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card stat-card">
                            <div class="stat-number">{{ $activeUsers }}</div>
                            <div class="stat-label">Alunos Cadastrados</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5><i class="fas fa-bolt me-2 text-primary-custom"></i>Ações Rápidas</h5>
                        </div>
                        <div class="d-grid gap-3">
                            <a href="{{ route('admin.reports') }}" class="btn btn-primary">
                                <i class="fas fa-file-chart-line me-2"></i>GERAR RELATÓRIO
                            </a>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-users-cog me-2"></i>GERENCIAR ALUNOS
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

