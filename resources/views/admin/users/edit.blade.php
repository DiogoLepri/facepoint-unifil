@extends('layouts.app')

@section('title', 'Editar Usuário')

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
    .user-photo {
        width: 150px;
        height: 150px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 50%;
        overflow: hidden;
        background-color: #f8f9fa;
        object-fit: cover;
    }
    .camera-container {
        width: 150px;
        height: 150px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 50%;
        overflow: hidden;
        background-color: #f8f9fa;
    }
    .camera-icon {
        width: 50px;
        height: 50px;
        margin-top: 50px;
        opacity: 0.5;
    }
    .form-label {
        font-weight: 500;
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
                <a class="nav-link active" href="{{ route('users.index') }}">Alunos</a>
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

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <h4 class="text-center mb-3">Editar Usuário</h4>
                <p class="text-center text-muted small mb-4">Edite os campos abaixo para atualizar as informações do usuário</p>

                <form method="POST" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Dados Pessoais</h5>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <input type="text" class="form-control @error('matricula') is-invalid @enderror" id="matricula" name="matricula" value="{{ old('matricula', $user->matricula) }}" required>
                                @error('matricula')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail Institucional</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <select class="form-select @error('curso') is-invalid @enderror" id="curso" name="curso" required>
                                    <option value="">Selecione um curso</option>
                                    <option value="Sistemas de Informação" {{ $user->curso == 'Sistemas de Informação' ? 'selected' : '' }}>Sistemas de Informação</option>
                                    <option value="Ciência da Computação" {{ $user->curso == 'Ciência da Computação' ? 'selected' : '' }}>Ciência da Computação</option>
                                    <option value="Engenharia de Software" {{ $user->curso == 'Engenharia de Software' ? 'selected' : '' }}>Engenharia de Software</option>
                                </select>
                                @error('curso')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Tipo de Usuário</label>
                                @if($user->role == 'admin')
                                    <input type="text" class="form-control" value="Administrador" disabled>
                                    <input type="hidden" name="role" value="admin">
                                    <small class="text-muted">Administradores não podem ter seu tipo alterado via interface.</small>
                                @else
                                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                        <option value="aluno" {{ $user->role == 'aluno' ? 'selected' : '' }}>Aluno</option>
                                    </select>
                                    <small class="text-muted">Apenas alunos podem ter seu tipo alterado via interface.</small>
                                @endif
                                @error('role')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                @error('password')
                                    <span class="invalid-feedback"