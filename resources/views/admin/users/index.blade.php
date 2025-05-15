@extends('layouts.app')

@section('title', 'Gerenciar Usuários')

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
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .table td {
        vertical-align: middle;
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

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Gerenciar Usuários</h5>
                    <a href="{{ route('users.create') }}" class="btn btn-primary">Novo Usuário</a>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" action="{{ route('users.index') }}" class="d-flex">
                            <input type="text" class="form-control me-2" placeholder="Buscar por nome ou matrícula" name="search" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary">Filtrar</button>
                            <button type="button" class="btn btn-outline-secondary">Exportar</button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Foto</th>
                                <th>Nome</th>
                                <th>Matrícula</th>
                                <th>Curso</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    @if($user->profile_image)
                                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}" class="user-avatar">
                                    @else
                                        <img src="{{ asset('images/default-avatar.png') }}" alt="{{ $user->name }}" class="user-avatar">
                                    @endif
                                </td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->matricula }}</td>
                                <td>{{ $user->curso }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->active)
                                        <span class="badge bg-success">Ativo@else
                                        <span class="badge bg-danger">Inativo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection