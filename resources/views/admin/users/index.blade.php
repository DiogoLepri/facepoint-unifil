@extends('layouts.app')

@section('title', 'Gerenciar Usuários')

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
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Gerenciar Usuários</h5>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row mb-4">
                    <div class="col-md-5">
                        <form method="GET" action="{{ route('users.index') }}" class="d-flex">
                            <input type="text" class="form-control me-2" placeholder="Buscar por nome ou matrícula" name="search" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('users.index') }}" class="d-flex">
                            <select name="status" class="form-select me-2" onchange="this.form.submit()">
                                <option value="">Todos os usuários</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Apenas ativos</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Apenas inativos</option>
                            </select>
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        </form>
                    </div>
                    <div class="col-md-3 text-end">
                        <small class="text-muted">Total: {{ $users->total() }} usuários</small>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
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
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->matricula }}</td>
                                <td>{{ $user->curso }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->trashed())
                                        <span class="badge bg-danger">Inativo</span>
                                    @else
                                        <span class="badge bg-success">Ativo</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->trashed())
                                        <!-- User is inactive - show restore and permanent delete buttons -->
                                        <div class="btn-group btn-group-sm" role="group">
                                            <form action="{{ route('users.restore', $user->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Reativar">
                                                    <i class="fas fa-undo"></i> Reativar
                                                </button>
                                            </form>
                                            <form action="{{ route('users.force-destroy', $user->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('ATENÇÃO: Isto irá excluir permanentemente o usuário e todos os seus dados. Esta ação não pode ser desfeita. Tem certeza?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir permanentemente">
                                                    <i class="fas fa-trash"></i> Excluir
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <!-- User is active - show edit and inactivate buttons -->
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja inativar este usuário?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Inativar">
                                                    <i class="fas fa-ban"></i> Inativar
                                                </button>
                                            </form>
                                        </div>
                                    @endif
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