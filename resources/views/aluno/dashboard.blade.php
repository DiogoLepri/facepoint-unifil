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
    
    /* Modal melhorado */
    .modal-content {
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    .modal-header {
        border-bottom: none;
        background: linear-gradient(135deg, #003366 0%, #004080 100%);
        border-radius: 15px 15px 0 0;
        padding: 25px;
    }
    .modal-body {
        padding: 30px;
    }
    .modal-footer {
        border-top: none;
        padding: 20px 30px 30px;
    }
    
    .current-time-large {
        font-size: 3rem;
        font-weight: bold;
        color: #003366;
        font-family: 'Courier New', monospace;
    }
    
    .punch-type-badge {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: bold;
        display: inline-block;
        margin-top: 10px;
    }
    
    .info-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #e9ecef;
    }
    
    .info-item {
        text-align: center;
    }
    
    .info-value {
        font-size: 1.2rem;
        font-weight: bold;
        color: #003366;
        margin-top: 5px;
    }
    
    .time-alert {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        color: #856404;
    }
    
    .time-alert.late {
        background: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
    
    .time-alert.early {
        background: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }
    
    .justification-card {
        background: #fff;
        border: 2px dashed #dc3545;
        border-radius: 10px;
        padding: 20px;
        margin-top: 15px;
    }
    
    .justification-card h6 {
        color: #dc3545;
        margin-bottom: 15px;
    }
    
    .confirmation-text p {
        font-size: 1.1rem;
        color: #495057;
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-center">
                <div class="w-100">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h4 class="modal-title mb-0">Registro de Ponto</h4>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Horário Atual -->
                <div class="text-center mb-4">
                    <div class="current-time-large" id="modal-current-time">--:--</div>
                    <div class="punch-type-badge" id="modal-punch-type">Entrada</div>
                </div>
                
                <!-- Card de Informações -->
                <div class="info-card mb-3">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-item">
                                <small class="text-muted">Horário Esperado</small>
                                <div class="info-value" id="expected-time">--:--</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="info-item">
                                <small class="text-muted">Tipo de Registro</small>
                                <div class="info-value" id="punch-type-text">Entrada</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerta de Diferença de Tempo -->
                <div class="time-alert" id="time-difference-info" style="display: none;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <strong id="time-difference-text"></strong>
                            <br><small id="time-difference-detail"></small>
                        </div>
                    </div>
                </div>
                
                <!-- Seção de Justificativa -->
                <div id="justification-section" style="display: none;">
                    <div class="justification-card">
                        <h6><i class="fas fa-edit me-2"></i>Justificativa Obrigatória</h6>
                        <textarea class="form-control" id="justification" rows="3" 
                                placeholder="Explique o motivo do registro fora do horário padrão..."></textarea>
                        <small class="text-muted mt-1 d-block">Esta informação será enviada ao administrador.</small>
                    </div>
                </div>
                
                <!-- Confirmação -->
                <div class="confirmation-text text-center mt-3">
                    <p class="mb-0">Confirmar registro de ponto?</p>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirm-punch-btn">
                    <i class="fas fa-check me-1"></i>Confirmar
                </button>
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
        const timeDiffText = document.getElementById('time-difference-text');
        const timeDiffDetail = document.getElementById('time-difference-detail');
        const isEarly = data.current_time < data.expected_time;
        const isLate = data.current_time > data.expected_time;
        
        // Converter minutos para formato legível
        const totalMinutes = Math.floor(data.minutes_difference);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        
        let timeText = '';
        if (hours > 0) {
            timeText = `${hours}h ${minutes}min`;
        } else {
            timeText = `${minutes} minutos`;
        }
        
        if (isEarly) {
            timeDiffText.textContent = `Você está ${timeText} adiantado`;
            timeDiffDetail.textContent = `Horário esperado: ${data.expected_time} | Horário atual: ${data.current_time}`;
            timeDiffElement.className = 'time-alert early';
        } else if (isLate) {
            timeDiffText.textContent = `Você está ${timeText} atrasado`;
            timeDiffDetail.textContent = `Horário esperado: ${data.expected_time} | Horário atual: ${data.current_time}`;
            timeDiffElement.className = 'time-alert late';
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