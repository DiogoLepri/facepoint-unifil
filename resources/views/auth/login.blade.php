@extends('layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    html, body {
        background-color: #f5f5f5;
        height: 100%;
        overflow-x: hidden;
    }
    
    .navbar {
        background-color: #f08223 !important;
    }
    
    .navbar-brand {
        font-size: 1.1rem;
    }
    
    .main-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
    }
    
    .logo-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .logo-unifil {
        max-width: 150px;
        margin-bottom: 20px;
    }
    
    .npi-logo {
        max-width: 200px;
        margin-left: 20px;
    }
    
    .system-title {
        color: #f08223;
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .subtitle {
        color: #666;
        font-size: 0.9rem;
    }
    
    .login-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }
    
    .login-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .login-card h5 {
        color: #f08223;
        font-weight: 600;
        margin-bottom: 25px;
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
        border-color: #f08223;
        box-shadow: 0 0 0 0.2rem rgba(240, 130, 35, 0.25);
    }
    
    .btn-primary {
        background-color: #f08223;
        border: none;
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        background-color: #e6751e;
    }
    
    .btn-warning {
        background-color: #f08223;
        border: none;
        color: white;
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
    }
    
    .btn-warning:hover {
        background-color: #e6751e;
        color: white;
    }
    
    .btn-success {
        background-color: #28a745;
        border: none;
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .camera-container {
        width: 200px;
        height: 200px;
        margin: 20px auto;
        border: 2px dashed #ccc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        position: relative;
    }
    
    .camera-icon {
        font-size: 48px;
        color: #6c757d;
    }
    
    .camera-status {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        margin-top: 10px;
    }
    
    .footer {
        text-align: center;
        margin-top: auto;
        padding: 20px 0;
        color: #666;
        font-size: 0.85rem;
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
    }
    
    .cadastre-link {
        color: #f08223;
        text-decoration: none;
        font-weight: 500;
    }
    
    .cadastre-link:hover {
        text-decoration: underline;
    }
    
    #video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    
    #canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
    }
    
    .recognition-status {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
        font-size: 0.9rem;
        display: none;
    }
    
    .recognition-status.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .recognition-status.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .recognition-status.processing {
        background-color: #cce5ff;
        color: #004085;
        border: 1px solid #b8daff;
    }
    
    /* Confirmation Modal Styles */
    .confirmation-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .confirmation-modal.show {
        display: flex;
    }
    
    .confirmation-content {
        background: white;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .confirmation-content h3 {
        color: #f08223;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .confirmation-content p {
        color: #666;
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    
    .user-name {
        color: #f08223;
        font-weight: bold;
        font-size: 1.3rem;
    }
    
    .confirmation-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    
    .btn-confirm {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-confirm:hover {
        background-color: #218838;
    }
    
    .btn-cancel {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-cancel:hover {
        background-color: #c82333;
    }
    
    @media (max-width: 768px) {
        .login-container {
            grid-template-columns: 1fr;
        }
        
        .logo-container {
            flex-direction: column;
        }
        
        .npi-logo {
            margin-left: 0;
            margin-top: 10px;
        }
    }
</style>
@endsection

@section('content')
<div class="main-container fade-in-up">
    <!-- Logo Section -->
    <div class="logo-container">
        <div class="d-flex align-items-center justify-content-center">
            <img src="{{ asset('images/logo-computacao.png') }}" alt="UniFil" class="logo-unifil" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
            <img src="{{ asset('images/logo-npi.png') }}" alt="NPI" class="npi-logo" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
        </div>
        <h1 class="page-title">Sistema de Ponto por Reconhecimento Facial</h1>
        <p class="page-subtitle">Núcleo de Práticas em Informática (NPI)</p>
    </div>

    <!-- Login Forms Container -->
    <div class="login-container">
        <!-- Traditional Login -->
        <div class="login-card">
            <h5><i class="fas fa-user-lock me-2 text-primary-custom"></i>Login no Sistema</h5>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail Institucional:</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="seuemail@edu.unifil.br"
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
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        ENTRAR
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <small>
                        Não é cadastrado? 
                        <a href="{{ route('register') }}" class="cadastre-link">Cadastre-se</a>
                    </small>
                </div>
            </form>
        </div>

        <!-- Facial Recognition Login -->
        <div class="login-card">
            <h5><i class="fas fa-camera me-2 text-primary-custom"></i>Registro Rápido por Facial</h5>
            
            <div class="camera-container" id="video-container">
                <video id="video" style="display: none;" autoplay muted></video>
                <canvas id="canvas" style="display: none;"></canvas>
                <div id="camera-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" viewBox="0 0 16 16">
                        <path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1v6zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2z"/>
                        <path d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM3 6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                    </svg>
                </div>
            </div>
            
            <p class="camera-status">Câmera desativada</p>
            
            <div id="recognition-status" class="recognition-status"></div>
            
            <div class="d-grid mt-4">
                <button type="button" class="btn btn-success" id="ativar-reconhecimento">
                    ATIVAR RECONHECIMENTO FACIAL
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        © 2025 UniFil NPI - Sistema desenvolvido conforme especificações LGPD
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-content">
        <h3>Confirmar Identidade</h3>
        <p>Você é <span id="userName" class="user-name"></span>?</p>
        <div class="confirmation-buttons">
            <button id="confirmYes" class="btn-confirm">Sim, sou eu</button>
            <button id="confirmNo" class="btn-cancel">Não sou eu</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- face-api.js REMOVIDO - Agora usando Flask API (DeepFace + Facenet512) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ativarBtn = document.getElementById('ativar-reconhecimento');
    const cameraPlaceholder = document.getElementById('camera-placeholder');
    const cameraStatus = document.querySelector('.camera-status');
    const recognitionStatus = document.getElementById('recognition-status');

    let stream = null;
    let isProcessing = false;
    let recognitionInterval = null;

    console.log('Sistema de login facial via Flask API inicializado');

    // Check camera support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showStatus('Seu navegador não suporta acesso à câmera.', 'error');
        ativarBtn.disabled = true;
        return;
    }

    function showStatus(message, type) {
        recognitionStatus.textContent = message;
        recognitionStatus.className = 'recognition-status ' + type;
        recognitionStatus.style.display = 'block';
    }

    function hideStatus() {
        recognitionStatus.style.display = 'none';
    }

    ativarBtn.addEventListener('click', async function() {
        if (isProcessing) return;

        try {
            hideStatus();
            
            // Request camera access
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            });
            
            video.srcObject = stream;
            video.style.display = 'block';
            cameraPlaceholder.style.display = 'none';
            cameraStatus.textContent = 'Câmera ativada - Reconhecimento automático ativo';
            
            ativarBtn.textContent = 'RECONHECIMENTO ATIVO';
            ativarBtn.classList.remove('btn-success');
            ativarBtn.classList.add('btn-primary');
            ativarBtn.disabled = true;
            
            // Start automatic recognition
            startContinuousRecognition();
            
        } catch (error) {
            console.error('Error accessing camera:', error);

            let errorMessage = 'Erro ao acessar câmera. ';

            if (error.name === 'NotAllowedError') {
                errorMessage += 'Permissão negada. Clique no ícone 🔒 na barra de endereço e permita o acesso à câmera.';
            } else if (error.name === 'NotFoundError') {
                errorMessage += 'Nenhuma câmera encontrada no dispositivo.';
            } else if (error.name === 'NotReadableError') {
                errorMessage += 'Câmera está sendo usada por outro aplicativo.';
            } else if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                errorMessage += 'Acesso à câmera requer HTTPS ou localhost.';
            } else {
                errorMessage += 'Verifique as permissões do navegador.';
            }

            showStatus(errorMessage, 'error');
        }
    });

    function startContinuousRecognition() {
        showStatus('Procurando rosto e processando reconhecimento...', 'processing');

        recognitionInterval = setInterval(async () => {
            if (isProcessing) return;

            await captureAndRecognize();
        }, 2000); // Captura e reconhece a cada 2 segundos
    }

    async function captureAndRecognize() {
        try {
            isProcessing = true;

            // Configura canvas com dimensões do vídeo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Captura frame atual do vídeo
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Converte para base64 (JPEG com qualidade 0.95)
            const imageData = canvas.toDataURL('image/jpeg', 0.95);

            console.log('Capturando frame e enviando para reconhecimento...');

            // Envia para o endpoint /facial-login via Laravel → Flask
            const response = await fetch('/facial-login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    face_descriptor: imageData  // Envia a imagem base64 diretamente
                })
            });

            const data = await response.json();

            if (data.success && data.requires_confirmation) {
                // Match encontrado - para o reconhecimento e mostra modal
                clearInterval(recognitionInterval);
                showConfirmationModal(data.user_name);
            } else if (data.success) {
                // Login direto (caso o sistema esteja configurado assim)
                clearInterval(recognitionInterval);
                showStatus('Reconhecido! Redirecionando...', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '/dashboard';
                }, 1500);
            } else {
                // Nenhum match - continua tentando
                console.log('Nenhum match encontrado, continuando reconhecimento...');
            }

        } catch (error) {
            console.error('Erro durante reconhecimento:', error);
            showStatus('Erro ao processar frame. Tentando novamente...', 'error');
        } finally {
            isProcessing = false;
        }
    }
    
    function showConfirmationModal(userName) {
        const modal = document.getElementById('confirmationModal');
        const userNameElement = document.getElementById('userName');
        const confirmYes = document.getElementById('confirmYes');
        const confirmNo = document.getElementById('confirmNo');
        
        userNameElement.textContent = userName;
        modal.classList.add('show');
        
        // Stop camera while showing modal
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        confirmYes.onclick = async () => {
            confirmYes.disabled = true;
            confirmNo.disabled = true;
            
            try {
                const response = await fetch('/facial-login/confirm', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    modal.classList.remove('show');
                    showStatus('Acesso confirmado! Redirecionando...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/dashboard';
                    }, 1500);
                } else {
                    modal.classList.remove('show');
                    showStatus(data.message || 'Erro na confirmação', 'error');
                    setTimeout(() => {
                        isProcessing = false;
                        startContinuousRecognition();
                    }, 2000);
                }
            } catch (error) {
                console.error('Error confirming login:', error);
                modal.classList.remove('show');
                showStatus('Erro na confirmação', 'error');
                setTimeout(() => {
                    isProcessing = false;
                    startContinuousRecognition();
                }, 2000);
            }
        };
        
        confirmNo.onclick = async () => {
            confirmYes.disabled = true;
            confirmNo.disabled = true;
            
            try {
                await fetch('/facial-login/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            } catch (error) {
                console.error('Error rejecting login:', error);
            }
            
            modal.classList.remove('show');
            window.location.href = '/';
        };
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
});
</script>
@endsection