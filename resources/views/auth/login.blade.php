@extends('layouts.app')

@section('title', 'Login')

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
        color: #003366;
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
        color: #003366;
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
        border-color: #003366;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
    }
    
    .btn-primary {
        background-color: #003366;
        border: none;
        padding: 12px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        background-color: #002244;
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
    }
    
    .btn-warning:hover {
        background-color: #FF8C00;
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
        margin-top: 40px;
        padding: 20px 0;
        color: #666;
        font-size: 0.85rem;
    }
    
    .cadastre-link {
        color: #003366;
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
<div class="main-container">
    <!-- Logo Section -->
    <div class="logo-container">
        <div class="d-flex align-items-center justify-content-center">
            <img src="{{ asset('images/logo-computacao.png') }}" alt="UniFil" class="logo-unifil">
            <img src="{{ asset('images/logo-npi.png') }}" alt="NPI" class="npi-logo">
        </div>
        <h1 class="system-title">Sistema de Ponto por Reconhecimento Facial</h1>
        <p class="subtitle">Núcleo de Práticas em Informática (NPI)</p>
    </div>

    <!-- Login Forms Container -->
    <div class="login-container">
        <!-- Traditional Login -->
        <div class="login-card">
            <h5>Login no Sistema</h5>
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
                        ENTRAR COMO ALUNO
                    </button>
                    <a href="{{ route('admin.login') }}" class="btn btn-warning">
                        ENTRAR COMO ADMINISTRADOR
                    </a>
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
            <h5>Registro Rápido por Facial</h5>
            
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
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
    let modelsLoaded = false;

    // Load face-api models from local files
    const MODEL_URL = '/models';
    
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
    ]).then(() => {
        modelsLoaded = true;
        console.log('Face-api models loaded');
    }).catch(error => {
        console.error('Error loading models:', error);
        showStatus('Erro ao carregar modelos de reconhecimento facial', 'error');
    });

    function showStatus(message, type) {
        recognitionStatus.textContent = message;
        recognitionStatus.className = 'recognition-status ' + type;
        recognitionStatus.style.display = 'block';
    }

    function hideStatus() {
        recognitionStatus.style.display = 'none';
    }

    ativarBtn.addEventListener('click', async function() {
        if (!modelsLoaded) {
            showStatus('Aguarde o carregamento dos modelos...', 'processing');
            return;
        }

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
            cameraStatus.textContent = 'Câmera ativada - Posicione seu rosto';
            
            ativarBtn.textContent = 'CAPTURAR IMAGEM';
            ativarBtn.classList.remove('btn-success');
            ativarBtn.classList.add('btn-primary');
            
            // Change button functionality to capture
            ativarBtn.removeEventListener('click', arguments.callee);
            ativarBtn.addEventListener('click', captureImage);
            
        } catch (error) {
            console.error('Error accessing camera:', error);
            showStatus('Erro ao acessar câmera. Verifique as permissões.', 'error');
        }
    });

    async function captureImage() {
        if (isProcessing) return;
        
        isProcessing = true;
        showStatus('Processando reconhecimento facial...', 'processing');
        
        // Draw video frame to canvas
        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);
        
        try {
            // Detect face
            const detections = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (detections) {
                const faceDescriptor = Array.from(detections.descriptor);
                
                // Send to server
                fetch('/facial-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        face_descriptor: faceDescriptor
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatus('Reconhecimento bem-sucedido! Redirecionando...', 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect || '/dashboard';
                        }, 1500);
                    } else {
                        showStatus(data.message || 'Falha no reconhecimento. Tente novamente.', 'error');
                        isProcessing = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showStatus('Erro ao processar reconhecimento facial', 'error');
                    isProcessing = false;
                });
            } else {
                showStatus('Nenhum rosto detectado. Tente novamente.', 'error');
                isProcessing = false;
            }
        } catch (error) {
            console.error('Error during face detection:', error);
            showStatus('Erro durante detecção facial', 'error');
            isProcessing = false;
        }
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