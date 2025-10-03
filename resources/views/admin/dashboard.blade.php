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
        color: #f08223;
    }
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    .activity-table th,
    .activity-table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    .activity-card {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .activity-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    .activity-item {
        padding: 16px;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s ease;
    }
    .activity-item:hover {
        background-color: #f8f9fa;
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .activity-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f08223, #ff9800);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    .activity-info {
        flex: 1;
        margin-left: 12px;
    }
    .activity-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }
    .activity-details {
        font-size: 13px;
        color: #666;
    }
    .activity-time {
        text-align: right;
        color: #999;
        font-size: 12px;
    }
    .activity-status {
        margin-top: 4px;
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }
    .status-confirmed {
        background-color: #d4edda;
        color: #155724;
    }
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
        color: #ddd;
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
                    <i class="fas fa-users me-2"></i>Alunos
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
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-clock me-2 text-primary-custom"></i>Atividade Recente</h5>
                        
                        <div class="card activity-card">
                            <div class="card-body p-0">
                                @forelse($recentActivity as $activity)
                                    <div class="activity-item d-flex align-items-center" data-date="{{ $activity->created_at->format('Y-m-d') }}">
                                        <div class="activity-avatar">
                                            {{ strtoupper(substr($activity->user->name ?? 'N', 0, 2)) }}
                                        </div>
                                        <div class="activity-info">
                                            <div class="activity-name">{{ $activity->user->name ?? 'Usuário não encontrado' }}</div>
                                            <div class="activity-details">
                                                <span class="me-2">{{ ucfirst($activity->type ?? 'Registro de ponto') }}</span>
                                                <span class="text-muted">•</span>
                                                <span class="ms-2">{{ $activity->user->course ?? 'Curso não informado' }}</span>
                                            </div>
                                            <div class="activity-status">
                                                <span class="status-badge status-confirmed">Confirmado</span>
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            <div>{{ $activity->created_at->format('H:i') }}</div>
                                            <div class="text-muted">{{ $activity->created_at->format('d/m/Y') }}</div>
                                            @if($activity->created_at->isToday())
                                                <small class="text-success">Hoje</small>
                                            @elseif($activity->created_at->isYesterday())
                                                <small class="text-warning">Ontem</small>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">
                                        <i class="fas fa-clock"></i>
                                        <div><strong>Nenhuma atividade recente</strong></div>
                                        <small>Os registros de ponto aparecerão aqui</small>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        
                        @if($recentActivity->count() > 0)
                            <div class="text-center mt-3">
                                <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver todos os registros
                                </a>
                            </div>
                        @endif
                    </div>
                    
                    <div class="col-md-6">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const activityItems = document.querySelectorAll('.activity-item');
    
    // Add hover effects for activity items
    activityItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(4px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});
</script>
@endpush