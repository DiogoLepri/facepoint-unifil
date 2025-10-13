@extends('layouts.app')

@section('title', 'Editar Usuário')

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

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <h4 class="text-center mb-3">Editar Usuário</h4>
                <p class="text-center text-muted small mb-4">Edite os campos abaixo para atualizar as informações do usuário</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <h5>Dados Pessoais</h5>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="matricula" class="form-label">Matrícula</label>
                            <input type="text" class="form-control @error('matricula') is-invalid @enderror" id="matricula" name="matricula" value="{{ old('matricula', $user->matricula) }}" required>
                            @error('matricula')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-mail Institucional</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="curso" class="form-label">Curso</label>
                            <select class="form-select @error('curso') is-invalid @enderror" id="curso" name="curso" required>
                                <option value="">Selecione um curso</option>
                                <option value="Sistemas de Informação" {{ old('curso', $user->curso) == 'Sistemas de Informação' ? 'selected' : '' }}>Sistemas de Informação</option>
                                <option value="Ciência da Computação" {{ old('curso', $user->curso) == 'Ciência da Computação' ? 'selected' : '' }}>Ciência da Computação</option>
                                <option value="Engenharia de Software" {{ old('curso', $user->curso) == 'Engenharia de Software' ? 'selected' : '' }}>Engenharia de Software</option>
                            </select>
                            @error('curso')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Tipo de Usuário</label>
                            @if($user->role == 'admin')
                                <input type="text" class="form-control" value="Administrador" disabled>
                                <input type="hidden" name="role" value="admin">
                                <small class="text-muted">Administradores não podem ter seu tipo alterado via interface.</small>
                            @else
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="aluno" {{ old('role', $user->role) == 'aluno' ? 'selected' : '' }}>Aluno</option>
                                </select>
                                <small class="text-muted">Apenas alunos podem ter seu tipo alterado via interface.</small>
                            @endif
                            @error('role')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4 mt-4">
                        <h5>Horário de Trabalho por Dia</h5>
                        <p class="text-muted small">Defina os horários de entrada e saída para cada dia da semana (formato 24 horas)</p>
                    </div>

                    @php
                        $days = [
                            'monday' => 'Segunda-feira',
                            'tuesday' => 'Terça-feira',
                            'wednesday' => 'Quarta-feira',
                            'thursday' => 'Quinta-feira',
                            'friday' => 'Sexta-feira',
                            'saturday' => 'Sábado',
                            'sunday' => 'Domingo'
                        ];
                        $userSchedule = old('schedule', $user->schedule ?? []);

                        // Default schedule for weekdays if empty
                        if (empty($userSchedule)) {
                            $userSchedule = [
                                'monday' => ['entry' => '14:00', 'exit' => '18:00'],
                                'tuesday' => ['entry' => '14:00', 'exit' => '18:00'],
                                'wednesday' => ['entry' => '14:00', 'exit' => '18:00'],
                                'thursday' => ['entry' => '14:00', 'exit' => '18:00'],
                                'friday' => ['entry' => '14:00', 'exit' => '18:00'],
                            ];
                        }
                    @endphp

                    @foreach($days as $dayValue => $dayLabel)
                        <div class="card mb-3" style="border: 1px solid #dee2e6;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input day-checkbox" type="checkbox"
                                                   id="enable_{{ $dayValue }}"
                                                   data-day="{{ $dayValue }}"
                                                   {{ isset($userSchedule[$dayValue]) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="enable_{{ $dayValue }}">
                                                {{ $dayLabel }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Horário de Entrada</label>
                                        <input type="time"
                                               class="form-control form-control-sm"
                                               name="schedule[{{ $dayValue }}][entry]"
                                               id="entry_{{ $dayValue }}"
                                               value="{{ $userSchedule[$dayValue]['entry'] ?? '14:00' }}"
                                               step="60"
                                               {{ isset($userSchedule[$dayValue]) ? '' : 'disabled' }}>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Horário de Saída</label>
                                        <input type="time"
                                               class="form-control form-control-sm"
                                               name="schedule[{{ $dayValue }}][exit]"
                                               id="exit_{{ $dayValue }}"
                                               value="{{ $userSchedule[$dayValue]['exit'] ?? '18:00' }}"
                                               step="60"
                                               {{ isset($userSchedule[$dayValue]) ? '' : 'disabled' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Dica:</strong> Marque os dias em que o aluno deve trabalhar e defina os horários específicos para cada dia.
                    </div>

                    <div class="mb-4 mt-4">
                        <h5>Segurança</h5>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Salvar Alterações
                        </button>
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
    // Handle day checkbox toggle
    document.querySelectorAll('.day-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const day = this.dataset.day;
            const entryInput = document.getElementById(`entry_${day}`);
            const exitInput = document.getElementById(`exit_${day}`);

            if (this.checked) {
                entryInput.disabled = false;
                exitInput.disabled = false;
            } else {
                entryInput.disabled = true;
                exitInput.disabled = true;
            }
        });
    });
});
</script>
@endsection
