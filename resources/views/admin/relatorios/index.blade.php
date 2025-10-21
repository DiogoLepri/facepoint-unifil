@extends('layouts.app')

@section('title', 'Relatórios')

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
    .form-label {
        font-weight: 500;
    }
    .btn-group-reports .btn {
        margin-right: 5px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users.index') }}">Alunos</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">Gerar Novo Relatório</h5>
                
                <form method="POST" action="{{ route('admin.reports.generate') }}">
                    @csrf
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="report_type" class="form-label">Tipo de Relatório</label>
                                <select class="form-select" id="report_type" name="report_type">
                                    <option value="attendance">Relatório de Presença</option>
                                    <option value="summary">Relatório de Sumário</option>
                                    <option value="user">Relatório por Usuário</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="date_range" class="form-label">Período</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                    <span class="input-group-text">até</span>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="filter_by" class="form-label">Filtrar por Curso</label>
                                <select class="form-select" id="filter_by" name="filter_by">
                                    <option value="">Todos os cursos</option>
                                    <option value="Ciencia da Computacao">Ciência da Computação</option>
                                    <option value="Engenharia de Software">Engenharia de Software</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="user_selection" style="display: none;">
                                <label for="user_id" class="form-label">Selecionar Usuário</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">Selecione um usuário</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="format" class="form-label">Formato de Saída</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf" checked readonly>
                                        <label class="form-check-label" for="format_pdf">PDF</label>
                                    </div>
                                </div>
                                <small class="text-muted">Relatórios são gerados apenas em formato PDF</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end btn-group-reports">
                        <button type="submit" name="report_period" value="daily" class="btn btn-primary">RELATÓRIO DIÁRIO</button>
                        <button type="submit" name="report_period" value="weekly" class="btn btn-warning">RELATÓRIO SEMANAL</button>
                        <button type="submit" name="report_period" value="monthly" class="btn btn-info">RELATÓRIO MENSAL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reportTypeSelect = document.getElementById('report_type');
    const userSelectionDiv = document.getElementById('user_selection');
    const userSelect = document.getElementById('user_id');
    
    function toggleUserSelection() {
        if (reportTypeSelect.value === 'user') {
            userSelectionDiv.style.display = 'block';
            userSelect.required = true;
        } else {
            userSelectionDiv.style.display = 'none';
            userSelect.required = false;
            userSelect.value = '';
        }
    }
    
    // Executar na inicialização
    toggleUserSelection();
    
    // Executar quando mudar o tipo de relatório
    reportTypeSelect.addEventListener('change', toggleUserSelection);
});
</script>
@endsection