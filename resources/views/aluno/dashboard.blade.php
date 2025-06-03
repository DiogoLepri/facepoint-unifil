@extends('layouts.app')

@section('title', 'Dashboard do Aluno')

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
        padding: 20px;
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        color: white;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    .punch-clock-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        margin-bottom: 20px;
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
        background: #e3f2fd;
        border: 1px solid #2196f3;
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
    }
    .current-time {
        font-size: 1.5rem;
        font-weight: bold;
        color: #003366;
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
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
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
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.create') }}">Registrar Ponto</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.history') }}">Histórico</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile') }}">Perfil</a>
            </li>
        </ul>
    </div>
</div>

<div class="row">
    <!-- Estatísticas -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number">{{ $hoursRegistered ?? '0h' }}</div>
            <div class="stat-label">Horas Trabalhadas (Este Mês)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number">{{ $attendance ?? '0%' }}</div>
            <div class="stat-label">Frequência (Este Mês)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-number" id="current-time">--:--</div>
            <div class="stat-label">Horário Atual</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Registro de Ponto -->
    <div class="col-md-6">
        <div class="punch-clock-card">
            <h5 class="mb-3">Registro de Ponto</h5>
            
            <div class="work-hours">
                <strong>Horário de Trabalho:</strong><br>
                <span class="text-primary">14:00 às 18:00</span> (Segunda a Sexta)
            </div>
            
            <div class="current-time" id="display-time">--:--:--</div>
            <div class="next-punch" id="next-punch-info">{{ $nextRegister ?? 'Carregando...' }}</div>
            
            <div class="mt-4">
                <button type="button" class="punch-btn" id="punch-clock-btn">
                    BATER<br>PONTO
                </button>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    Status: <span id="punch-status">Verificando...</span>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Atividade Recente -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Atividade Recente</h5>
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
    const confirmModal = new bootstrap.Modal(document.getElementById('punchConfirmModal'));
    const confirmBtn = document.getElementById('confirm-punch-btn');
    const justificationSection = document.getElementById('justification-section');
    const justificationInput = document.getElementById('justification');
    
    let currentPunchData = null;
    
    // Atualizar relógio
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('pt-BR');
        document.getElementById('current-time').textContent = timeString.substring(0, 5);
        document.getElementById('display-time').textContent = timeString;
        document.getElementById('modal-current-time').textContent = timeString.substring(0, 5);
    }
    
    // Verificar status do usuário
    function checkUserStatus() {
        fetch('/api/attendance/status')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const statusElement = document.getElementById('punch-status');
                    
                    if (data.is_weekend) {
                        statusElement.textContent = 'Fim de semana - Descanso';
                        punchBtn.disabled = true;
                        return;
                    }
                    
                    const nextType = data.next_punch_type === 'entry' ? 'Entrada' : 'Saída';
                    statusElement.textContent = `Próximo: ${nextType} (${data.expected_time})`;
                    
                    if (data.today_record) {
                        if (data.today_record.entry_time && data.today_record.exit_time) {
                            statusElement.textContent = 'Já registrou entrada e saída hoje';
                            punchBtn.disabled = true;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao verificar status:', error);
                document.getElementById('punch-status').textContent = 'Erro ao carregar status';
            });
    }
    
    // Evento do botão de bater ponto
    punchBtn.addEventListener('click', function() {
        if (punchBtn.disabled) return;
        
        punchBtn.disabled = true;
        punchBtn.textContent = 'VERIFICANDO...';
        
        // Fazer primeira chamada para verificar se precisa de justificativa
        fetch('/register-attendance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            currentPunchData = data;
            
            if (data.success) {
                // Registro bem-sucedido - mostrar confirmação simples
                showSimpleConfirmation(data);
            } else if (data.requires_justification) {
                // Precisa de justificativa - mostrar modal
                showJustificationModal(data);
            } else {
                // Erro - mostrar mensagem
                alert(data.message || 'Erro ao registrar ponto');
                resetPunchButton();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de comunicação com o servidor');
            resetPunchButton();
        });
    });
    
    function showSimpleConfirmation(data) {
        // Preencher modal com dados
        document.getElementById('expected-time').textContent = '14:00'; // ou 18:00 baseado no tipo
        document.getElementById('punch-type-text').textContent = data.punch_type === 'entry' ? 'Entrada' : 'Saída';
        document.getElementById('modal-punch-type').textContent = data.punch_type === 'entry' ? 'Entrada' : 'Saída';
        
        // Esconder seção de justificativa
        justificationSection.style.display = 'none';
        document.getElementById('time-difference-info').style.display = 'none';
        
        confirmModal.show();
    }
    
    function showJustificationModal(data) {
        // Preencher modal com dados
        document.getElementById('expected-time').textContent = data.expected_time;
        document.getElementById('punch-type-text').textContent = data.punch_type === 'entry' ? 'Entrada' : 'Saída';
        document.getElementById('modal-punch-type').textContent = data.punch_type === 'entry' ? 'Entrada' : 'Saída';
        
        // Mostrar diferença de tempo
        const timeDiffElement = document.getElementById('time-difference-info');
        const isEarly = data.current_time < data.expected_time;
        const isLate = data.current_time > data.expected_time;
        
        if (isEarly) {
            timeDiffElement.textContent = `Você está ${data.minutes_difference} minutos adiantado`;
            timeDiffElement.className = 'time-difference early';
        } else if (isLate) {
            timeDiffElement.textContent = `Você está ${data.minutes_difference} minutos atrasado`;
            timeDiffElement.className = 'time-difference late';
        }
        
        timeDiffElement.style.display = 'block';
        
        // Mostrar seção de justificativa
        justificationSection.style.display = 'block';
        justificationInput.value = '';
        justificationInput.required = true;
        
        confirmModal.show();
    }
    
    // Confirmação final
    confirmBtn.addEventListener('click', function() {
        const justification = justificationInput.value.trim();
        
        // Verificar se justificativa é necessária
        if (justificationSection.style.display !== 'none' && !justification) {
            alert('Justificativa é obrigatória para horários irregulares');
            return;
        }
        
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Registrando...';
        
        // Fazer registro final
        fetch('/register-attendance', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                justification: justification
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                confirmModal.hide();
                alert(data.message || 'Ponto registrado com sucesso!');
                
                // Recarregar a página para atualizar os dados
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(data.message || 'Erro ao registrar ponto');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmar';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de comunicação com o servidor');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirmar';
        });
    });
    
    function resetPunchButton() {
        punchBtn.disabled = false;
        punchBtn.innerHTML = 'BATER<br>PONTO';
    }
    
    // Reset do modal quando fechado
    document.getElementById('punchConfirmModal').addEventListener('hidden.bs.modal', function() {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirmar';
        resetPunchButton();
    });
    
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