<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>FacePoint UniFil - @yield('title', 'Sistema de Ponto por Reconhecimento Facial')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .navbar-brand-content {
            display: flex;
            flex-direction: column;
            margin-left: 10px;
        }
        
        .navbar-brand-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            line-height: 1.2;
        }
        
        .navbar-brand-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }
        
        .navbar-brand:hover .navbar-brand-title,
        .navbar-brand:hover .navbar-brand-subtitle {
            color: white;
        }
    </style>
    @yield('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #003366;">
        <div class="container">
            <a class="navbar-brand" href="{{ route('login') }}">
                <img src="{{ asset('images/logo-unifil.png') }}" alt="Logo" width="40" height="40" class="d-inline-block align-top">
                <div class="navbar-brand-content">
                    <span class="navbar-brand-title">FacePoint UniFil</span>
                    <span class="navbar-brand-subtitle">Sistema de Ponto por Reconhecimento Facial</span>
                </div>
            </a>
            @auth
            <div class="navbar-text text-white me-3">
                Olá, {{ Auth::user()->name }}!
            </div>
            <div>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-light">Sair</button>
                </form>
            </div>
            @endauth
        </div>
    </nav>
    <div class="container mt-4">
        @yield('content')
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>