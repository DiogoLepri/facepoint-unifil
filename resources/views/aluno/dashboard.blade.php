@extends('layouts.app')

@section('title', 'Dashboard do Aluno')

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
        padding: 25px 20px;
        background: linear-gradient(135deg, #f08223 0%, #e6751e 100%);
        color: white;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(240, 130, 35, 0.2);
        transition: transform 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(240, 130, 35, 0.3);
    }
    .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 1px;
    }
    .stat-label {
        font-size: 0.95rem;
        opacity: 0.95;
        font-weight: 500;
    }
    .punch-clock-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 35px 30px;
        text-align: center;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border: 1px solid #e8ecef;
    }
    .punch-btn {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        font-size: 1.2rem;
        font-weight: bold;
        border: none;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .punch-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    .punch-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    .work-hours {
        background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
        border: 1px solid #2196f3;
        border-radius: 10px;
        padding: 18px;
        margin: 25px 0;
        position: relative;
    }
    .current-time {
        font-size: 1.5rem;
        font-weight: bold;
        color: #f08223;
        margin-bottom: 10px;
    }
    .next-punch {
        color: #666;
        font-size: 0.9rem;
    }
    
    /* Modal styles */
    .modal-content {
        border-radius: 15px;
    }
    .modal-header {
        background: linear-gradient(135deg, #f08223 0%, #e6751e 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    .time-difference {
        font-size: 1.1rem;
        font-weight: bold;
        margin: 10px 0;
    }
    .time-difference.early {
        color: #ffc107;
    }
    .time-difference.late {
        color: #dc3545;
    }
    .time-difference.ontime {
        color: #28a745;
    }
    
    /* Recent activity styles */
    .activity-table {
        background: white;
        border-radius: 10px;
        overflow: hidden;
    }
    .activity-table thead {
        background: #f8f9fa;
    }
    .activity-table th,
    .activity-table td {
        border: none;
        padding: 12px 15px;
    }
</style>
@endsection

@section('content')
<div class="row fade-in-down">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('dashboard') }}">
                    <i class="fas fa-home me-2"></i>Início
                </a>
            </li>
            @if(Auth::user()->last_login_type === 'email')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.history') }}">
                    <i class="fas fa-history me-2"></i>Histórico
                </a>
            </li>
            @endif
        </ul>
        
        @if(Auth::user()->last_login_type === 'facial')
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                <strong>Login via Reconhecimento Facial</strong> - Algumas funcionalidades como o histórico estão disponíveis apenas para login com email e senha.
            </div>
        </div>
        @endif
    </div>
</div>

<div class="row fade-in-up">
    <!-- Estatísticas -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><i class="fas fa-clock me-2"></i>{{ $hoursRegistered ?? '0h'}}</div>
            <div class="stat-label">Horas (Este Mês)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number"><i class="fas fa-percentage me-2"></i>{{ $attendance ?? '0%' }}</div>
            <div class="stat-label">Frequência (Este Mês)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number" id="current-time"><i class="fas fa-clock me-2"></i>--:--</div>
            <div class="stat-label">Horário Atual</div>
        </div>
    </div>
</div>

<div class="row fade-in-up">
    <!-- Registro de Ponto -->
    <div class="col-md-6">
        <div class="punch-clock-card">
            <h5 class="mb-3"><i class="fas fa-user-clock me-2 text-primary-custom"></i>Registro de Ponto</h5>
            
            <div class="work-hours">
                <div style="margin-top: 5px;">
                    <strong style="color: #1976d2; font-size: 1.1rem;">Horário de Estudo</strong><br>

                    @if(count($scheduleFormatted) > 0)
                        <div style="margin-top: 10px;">
                            @foreach($scheduleFormatted as $schedule)
                                <div style="font-size: 0.95rem; margin: 5px 0; padding: 5px 0; border-bottom: 1px dashed #e0e0e0;">
                                    <strong style="color: #1976d2;">{{ $schedule['day'] }}:</strong>
                                    <span style="color: #333; font-weight: 600;">{{ $schedule['entry'] }} às {{ $schedule['exit'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-primary" style="font-size: 1.15rem; font-weight: 600;">{{ $entryTime }} às {{ $exitTime }}</span>
                        <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">(Segunda a Sexta)</div>
                    @endif
                </div>
            </div>
            
            <div class="mt-4">
                <button type="button" class="punch-btn" id="punch-clock-btn" {{ !$isAllowedToday ? 'disabled' : '' }}>
                    BATER<br>PONTO
                </button>
            </div>

            <div class="mt-3" style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #f08223;">
                <div style="font-size: 0.95rem; color: #495057;">
                    <strong style="color: #f08223;">Status:</strong> 
                    <span id="punch-status" style="font-weight: 500;">Verificando...</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Atividade Recente -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-history me-2 text-primary-custom"></i>
                    Atividade Recente
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table activity-table mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances ?? [] as $record)
                            <tr>
                                <td>{{ $record->created_at->format('d/m') }}</td>
                                <td>{{ $record->entry_time ? $record->entry_time->format('H:i') : '--' }}</td>
                                <td>{{ $record->exit_time ? $record->exit_time->format('H:i') : '--' }}</td>
                                <td>
                                    @if($record->justification)
                                        <span class="badge bg-warning">Justificado</span>
                                    @elseif($record->is_early || $record->is_late)
                                        <span class="badge bg-warning">Irregular</span>
                                    @else
                                        <span class="badge bg-success">Normal</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Nenhum registro encontrado</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="punchConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Registro de Ponto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div class="current-time" id="modal-current-time">--:--</div>
                    <div id="modal-punch-type">Entrada</div>
                </div>
                
                <div class="time-difference" id="time-difference-info" style="display: none;">
                    <!-- Será preenchido dinamicamente -->
                </div>
                
                <div class="alert alert-info">
                    <strong>Horário Esperado:</strong> <span id="expected-time">--:--</span><br>
                    <strong>Tipo:</strong> <span id="punch-type-text">Entrada</span>
                </div>
                
                <div id="justification-section" style="display: none;">
                    <div class="mb-3">
                        <label for="justification" class="form-label">
                            <strong>Justificativa (obrigatória):</strong>
                        </label>
                        <textarea class="form-control" id="justification" rows="3" 
                                placeholder="Explique o motivo do horário irregular..."></textarea>
                        <div class="form-text">Esta justificativa será enviada ao administrador.</div>
                    </div>
                </div>
                
                <div class="text-center">
                    <p>Deseja confirmar o registro de ponto?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirm-punch-btn">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const punchBtn = document.getElementById('punch-clock-btn');

    let currentPunchData = null;
    
    // Atualizar relógio
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('pt-BR');
        document.getElementById('current-time').textContent = timeString.substring(0, 5);
        document.getElementById('modal-current-time').textContent = timeString.substring(0, 5);
    }
    
    // Verificar status do usuário com timeout
    function checkUserStatus() {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
        
        fetch('/api/attendance/status', {
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const statusElement = document.getElementById('punch-status');

                // Check if today is not an allowed day
                if (data.is_not_allowed_day) {
                    statusElement.innerHTML = '<span style="color: #6c757d;">Hoje não é um dia de registro</span>';
                    punchBtn.disabled = true;
                    punchBtn.style.opacity = '0.6';
                    return;
                }

                if (data.is_weekend) {
                    statusElement.innerHTML = '<span style="color: #6c757d;"> Fim de semana - Descanso</span>';
                    punchBtn.disabled = true;
                    punchBtn.style.opacity = '0.6';
                    return;
                }

                const nextType = data.next_punch_type === 'entry' ? 'Entrada' : 'Saída';
                statusElement.innerHTML = `<span style="color: #28a745;">Próximo: ${nextType} (${data.expected_time})</span>`;

                if (data.today_record) {
                    if (data.today_record.entry_time && data.today_record.exit_time) {
                        statusElement.innerHTML = '<span style="color: #28a745;">Já registrou entrada e saída hoje</span>';
                        punchBtn.disabled = true;
                        punchBtn.style.opacity = '0.6';
                    }
                }

                // Habilitar botão se não estiver desabilitado
                if (!punchBtn.disabled) {
                    punchBtn.style.opacity = '1';
                }
            } else {
                throw new Error(data.message || 'Erro desconhecido');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Erro ao verificar status:', error);
            const statusElement = document.getElementById('punch-status');
            
            if (error.name === 'AbortError') {
                statusElement.innerHTML = '<span style="color: #dc3545;">⚠️ Timeout - Tente novamente</span>';
            } else {
                statusElement.innerHTML = '<span style="color: #dc3545;">❌ Erro de conexão</span>';
            }
        });
    }
    
    // Evento do botão de bater ponto com melhor tratamento de erro
    punchBtn.addEventListener('click', function() {
        if (punchBtn.disabled) return;
        
        punchBtn.disabled = true;
        punchBtn.innerHTML = '<div style="font-size: 0.9rem;">VERIFICANDO...<br><div style="font-size: 0.7rem; margin-top: 2px;">⏳</div></div>';
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
            showErrorMessage('Timeout - Operação cancelada');
            resetPunchButton();
        }, 15000); // 15 segundos
        
        // Fazer primeira chamada para verificar se precisa de justificativa
        fetch('/register-attendance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            currentPunchData = data;

            if (data.success) {
                // Registro bem-sucedido - mostrar mensagem e recarregar
                showSuccessMessage(data);
            } else {
                // Erro - mostrar mensagem
                showErrorMessage(data.message || 'Erro ao registrar ponto');
                resetPunchButton();
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Erro:', error);
            
            if (error.name === 'AbortError') {
                showErrorMessage('Operação cancelada por timeout');
            } else {
                showErrorMessage('Erro de comunicação com o servidor');
            }
            resetPunchButton();
        });
    });
    
    // Função para mostrar erros de forma mais elegante
    function showErrorMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';
        alertDiv.innerHTML = `
            <strong>❌ Erro:</strong> ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Remover automaticamente após 5 segundos
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    function showSuccessMessage(data) {
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show';
        successDiv.style.position = 'fixed';
        successDiv.style.top = '20px';
        successDiv.style.right = '20px';
        successDiv.style.zIndex = '9999';
        successDiv.style.minWidth = '350px';
        successDiv.style.maxWidth = '500px';

        let statusMessage = '';
        if (data.is_early) {
            statusMessage = '<div class="mt-2"><small class="text-info"><i class="fas fa-info-circle"></i> Registro adiantado</small></div>';
        } else if (data.is_late) {
            statusMessage = '<div class="mt-2"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Registro atrasado</small></div>';
        }

        successDiv.innerHTML = `
            <strong><i class="fas fa-check-circle"></i> Sucesso!</strong> ${data.message || 'Ponto registrado com sucesso!'}
            ${statusMessage}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        document.body.appendChild(successDiv);

        // Recarregar a página para atualizar os dados
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    }
    
    // Note: confirmBtn listener removed as we no longer use the confirmation modal
    
    function resetPunchButton() {
        punchBtn.disabled = false;
        punchBtn.innerHTML = 'BATER<br>PONTO';
    }
    
    // Note: Modal reset removed as we no longer use the confirmation modal
    
    // Inicializar
    updateClock();
    checkUserStatus();
    
    // Atualizar relógio a cada segundo
    setInterval(updateClock, 1000);
    
    // Verificar status a cada 30 segundos
    setInterval(checkUserStatus, 30000);
});
</script>
@endsection