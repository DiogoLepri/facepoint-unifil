@extends('layouts.app')

@section('title', 'Relatórios')

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
                <a class="nav-link" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users.index') }}">Alunos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin.reports') }}">Relatórios</a>
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
                                    <option value="si">Sistemas de Informação</option>
                                    <option value="cc">Ciência da Computação</option>
                                    <option value="eng">Engenharia de Software</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="format" class="form-label">Formato de Saída</label>
                                <div class="d-flex">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="format" id="format_pdf" value="pdf" checked>
                                        <label class="form-check-label" for="format_pdf">PDF</label>
                                    </div>
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="radio" name="format" id="format_excel" value="excel">
                                        <label class="form-check-label" for="format_excel">Excel</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="format_csv" value="csv">
                                        <label class="form-check-label" for="format_csv">CSV</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end btn-group-reports">
                        <button type="submit" class="btn btn-primary">RELATÓRIO DIÁRIO</button>
                        <button type="submit" class="btn btn-warning">RELATÓRIO SEMANAL</button>
                        <button type="submit" class="btn btn-info">RELATÓRIO MENSAL</button>
                        <a href="{{ route('admin.reports.export') }}" class="btn btn-success">EXPORTAR DADOS</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection