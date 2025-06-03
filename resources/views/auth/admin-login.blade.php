@extends('layouts.app')

@section('title', 'Login Administrativo')

@section('styles')
<style>
    html, body {
        background-color: #f5f5f5;
        overflow-x: hidden;
        height: 100%;
    }
    
    .navbar {
        background-color: #003366 !important;
    }
    
    .navbar-brand {
        font-size: 1.1rem;
    }
    
    .main-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 20px;
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .logo-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .logo-unifil {
        max-width: 120px;
        margin-bottom: 20px;
    }
    
    .npi-logo {
        max-width: 150px;
        margin-left: 20px;
    }
    
    .system-title {
        color: #003366;
        font-size: 1.6rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .subtitle {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .admin-badge {
        color: #FFA500;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .login-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    
    .login-card h5 {
        color: #003366;
        font-weight: 600;
        margin-bottom: 25px;
        text-align: center;
    }
    
    .form-label {
        color: #333;
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .form-control {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        font-size: 0.95rem;
    }
    
    .form-control:focus {
        border-color: #003366;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
    }
    
    .btn-warning {
        background-color: #FFA500;
        border: none;
        color: white;
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
        width: 100%;
    }
    
    .btn-warning:hover {
        background-color: #FF8C00;
        color: white;
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border: none;
        color: white;
        padding: 10px;
        font-weight: 500;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
        width: 100%;
        margin-top: 10px;
    }
    
    .btn-secondary:hover {
        background-color: #5a6268;
        color: white;
    }
    
    .footer {
        text-align: center;
        margin-top: 40px;
        padding: 20px 0;
        color: #666;
        font-size: 0.85rem;
    }
    
    .alert {
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .admin-icon {
        font-size: 2rem;
        color: #FFA500;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .logo-container {
            flex-direction: column;
        }
        
        .npi-logo {
            margin-left: 0;
            margin-top: 10px;
        }
        
        .main-container {
            padding: 15px;
        }
    }
</style>
@endsection

@section('content')
<div class="main-container">
    <!-- Logo Section -->
    <div class="logo-container">
        <div class="d-flex align-items-center justify-content-center">
            <img src="{{ asset('images/logo-computacao.png') }}" alt="UniFil" class="logo-unifil">
            <img src="{{ asset('images/logo-npi.png') }}" alt="NPI" class="npi-logo">
        </div>
        <h1 class="system-title">Sistema de Ponto por Reconhecimento Facial</h1>
        <p class="subtitle">Núcleo de Práticas em Informática (NPI)</p>
        <p class="admin-badge">🔐 ACESSO ADMINISTRATIVO</p>
    </div>

    <!-- Admin Login Form -->
    <div class="login-card">
        <div class="text-center">
            <div class="admin-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z"/>
                    <path d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5Z"/>
                </svg>
            </div>
        </div>
        
        <h5>Login de Administrador</h5>
        
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif
        
        <form method="POST" action="{{ route('login') }}" id="admin-login-form">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">E-mail Administrativo:</label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@unifil.br"
                    required
                    autofocus
                >
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Senha:</label>
                <input
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    required
                >
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-warning">
                    ENTRAR COMO ADMINISTRADOR
                </button>
                <a href="{{ route('login') }}" class="btn btn-secondary">
                    VOLTAR AO LOGIN
                </a>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <div class="footer">
        © 2025 UniFil NPI - Sistema desenvolvido conforme especificações LGPD
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh CSRF token if page has been idle for too long
    const form = document.getElementById('admin-login-form');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    
    if (form && csrfMeta) {
        form.addEventListener('submit', function(e) {
            // Update CSRF token before submission
            const csrfInput = form.querySelector('input[name="_token"]');
            if (csrfInput) {
                csrfInput.value = csrfMeta.getAttribute('content');
            }
        });
        
        // Auto-refresh CSRF token every 30 minutes
        setInterval(function() {
            fetch('{{ route("login") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCsrfToken = doc.querySelector('meta[name="csrf-token"]');
                const newCsrfInput = doc.querySelector('input[name="_token"]');
                
                if (newCsrfToken) {
                    csrfMeta.setAttribute('content', newCsrfToken.getAttribute('content'));
                }
                
                if (newCsrfInput) {
                    const currentCsrfInput = form.querySelector('input[name="_token"]');
                    if (currentCsrfInput) {
                        currentCsrfInput.value = newCsrfInput.value;
                    }
                }
            })
            .catch(error => {
                console.log('Failed to refresh CSRF token:', error);
            });
        }, 30 * 60 * 1000); // 30 minutes
    }
});
</script>
@endsection