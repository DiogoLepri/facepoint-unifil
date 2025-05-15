@extends('layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    .logo-unifil {
        max-width: 180px;
        margin-bottom: 10px;
    }
    .system-title {
        margin-bottom: 1.5rem;
    }
    .card {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        height: 100%;
    }
    .camera-container {
        width: 200px;
        height: 200px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 50%;
        overflow: hidden;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .camera-icon {
        width: 50px;
        height: 50px;
        opacity: 0.5;
    }
    .center-icon {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    .container-center {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .button-container {
        width: 100%;
        margin-top: 2rem;
    }
    .footer-text {
        margin-top: 2rem;
        text-align: center;
    }

    /* New styles for enhanced facial login */
    .scan-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, rgba(40, 167, 69, 0), rgba(40, 167, 69, 0.8), rgba(40, 167, 69, 0));
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.8);
        z-index: 100;
    }
    
    .scan-line.subtle {
        height: 1px;
        background: linear-gradient(to right, rgba(40, 167, 69, 0), rgba(40, 167, 69, 0.4), rgba(40, 167, 69, 0));
        box-shadow: 0 0 4px rgba(40, 167, 69, 0.4);
    }

    #face-guide {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 3px dashed #28a745;
        border-radius: 50%;
        box-sizing: border-box;
        z-index: 99;
    }

    .pulse-border {
        animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {
        0% { border-color: rgba(40, 167, 69, 0.5); }
        50% { border-color: rgba(40, 167, 69, 1); }
        100% { border-color: rgba(40, 167, 69, 0.5); }
    }
    
    @keyframes glowing-circle {
        0% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
        50% { box-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
        100% { box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
    }
    
    .processing-glow {
        animation: glowing-circle 1.5s infinite;
    }

    .result-message {
        margin-top: 1rem;
    }

    .recognition-status {
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
        font-size: 14px;
        display: none;
    }

    .success {
        background-color: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid #28a745;
    }

    .error {
        background-color: rgba(220, 53, 69, 0.2);
        color: #dc3545;
        border: 1px solid #dc3545;
    }

    .processing {
        background-color: rgba(0, 123, 255, 0.2);
        color: #007bff;
        border: 1px solid #007bff;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="text-center mb-4">
                <img src="{{ asset('images/logo-unifil.png') }}" alt="UniFil" class="logo-unifil">
                <h4 class="system-title">Sistema de Ponto por Reconhecimento Facial</h4>
                <p class="text-muted small">Núcleo de Práticas em Informática (NPI)</p>
            </div>

            <div class="row">
                <!-- Login tradicional -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <h5 class="mb-4 text-center">Login no Sistema</h5>
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail institucional</label>
                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                    >
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Senha</label>
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
                                    <button type="submit" class="btn btn-primary">
                                        ENTRAR COMO ALUNO
                                    </button>
                                </div>
                            </form>
                            <div class="d-grid mt-3">
                                <a href="{{ route('admin.login') }}" class="btn btn-warning">
                                    ENTRAR COMO ADMINISTRADOR
                                </a>
                            </div>
                            <div class="text-center mt-3">
                                <p class="mb-1">Ainda não tem uma conta?</p>
                                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">
                                    CADASTRE-SE AQUI
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login facial aprimorado -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-4 d-flex flex-column align-items-center justify-content-between">
                            <h5 class="mb-4 text-center">Login por Reconhecimento Facial</h5>
                            <div class="camera-container mb-4" id="video-container">
                                <video
                                    id="video"
                                    width="200"
                                    height="200"
                                    autoplay
                                    muted
                                    style="display: none; object-fit: cover;"
                                ></video>
                                <canvas
                                    id="canvas"
                                    width="200"
                                    height="200"
                                    style="display: none;"
                                ></canvas>
                                <div id="face-guide" style="display: none;"></div>
                                <div
                                    id="scanning-overlay"
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none;"
                                ></div>
                                <div class="center-icon" id="camera-icon-container">
                                    <img
                                        src="{{ asset('images/camera-icon.png') }}"
                                        class="camera-icon"
                                        id="camera-placeholder"
                                    >
                                </div>
                                <div
                                    id="processing-indicator"
                                    style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;"
                                >
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Processando...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center result-message mb-3">
                                <div id="recognition-status" class="recognition-status"></div>
                            </div>
                            <div class="button-container">
                                <div class="d-grid">
                                    <button
                                        type="button"
                                        class="btn btn-success"
                                        id="ativar-reconhecimento"
                                    >
                                        ATIVAR RECONHECIMENTO FACIAL
                                    </button>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Posicione seu rosto no centro da câmera
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-text">
                <small class="text-muted">
                    © 2023 UniFil NPI - Direitos reservados conforme regulação FacePoint
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ativarReconhecimentoBtn = document.getElementById('ativar-reconhecimento');
        const cameraIconContainer = document.getElementById('camera-icon-container');
        const videoContainer = document.getElementById('video-container');
        const faceGuide = document.getElementById('face-guide');
        const scanningOverlay = document.getElementById('scanning-overlay');
        const processingIndicator = document.getElementById('processing-indicator');
        const recognitionStatus = document.getElementById('recognition-status');

        let stream = null;
        let isProcessing = false;
        let detectionInterval = null;
        let modelsLoaded = false;

        function showStatus(message, type) {
            recognitionStatus.textContent = message;
            recognitionStatus.className = 'recognition-status';
            recognitionStatus.classList.add(type);
            recognitionStatus.style.display = 'block';
        }

        function hideStatus() {
            recognitionStatus.style.display = 'none';
        }

        // Add camera error handling
        video.addEventListener('loadedmetadata', function() {
            console.log('Camera stream loaded successfully');
        });

        video.addEventListener('error', function(e) {
            console.error('Video error:', e);
            showStatus('Erro ao inicializar câmera. Verifique permissões.', 'error');
            resetInterface();
        });

        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
        showStatus('Carregando modelos de reconhecimento facial...', 'processing');
        ativarReconhecimentoBtn.disabled = true;
        ativarReconhecimentoBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> CARREGANDO MODELOS...';

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]).then(() => {
            modelsLoaded = true;
            ativarReconhecimentoBtn.disabled = false;
            ativarReconhecimentoBtn.innerHTML = 'ATIVAR RECONHECIMENTO FACIAL';
            hideStatus();
        }).catch(error => {
            console.error('Erro ao carregar modelos:', error);
            showStatus('Erro ao carregar modelos. Recarregue a página.', 'error');
            ativarReconhecimentoBtn.innerHTML = 'ERRO AO CARREGAR MODELOS';
        });

        ativarReconhecimentoBtn.addEventListener('click', async function() {
            if (isProcessing || !modelsLoaded) return;

            try {
                hideStatus();
                processingIndicator.style.display = 'block';
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                });
                video.srcObject = stream;
                video.style.display = 'block';
                cameraIconContainer.style.display = 'none';
                await video.play();
                faceGuide.style.display = 'block';
                faceGuide.classList.add('pulse-border');
                processingIndicator.style.display = 'none';
                ativarReconhecimentoBtn.disabled = true;
                ativarReconhecimentoBtn.innerHTML = 'POSICIONE SEU ROSTO';
                startFaceDetection();
            } catch (err) {
                console.error('Erro ao acessar câmera:', err);
                showStatus(
                    'Não foi possível acessar a câmera. Verifique as permissões do navegador.',
                    'error'
                );
                resetInterface();
            }
        });

        // Improved scan animation function
        function createScanAnimation() {
            // Limpar qualquer animação anterior
            scanningOverlay.innerHTML = '';
            scanningOverlay.style.display = 'block';
            
            // Create main scan line
            const scanLine = document.createElement('div');
            scanLine.className = 'scan-line';
            scanningOverlay.appendChild(scanLine);
            
            // Create additional subtle scan lines for visual effect
            const subtleLine1 = document.createElement('div');
            subtleLine1.className = 'scan-line subtle';
            subtleLine1.style.opacity = '0.3';
            subtleLine1.style.animationDelay = '0.5s';
            scanningOverlay.appendChild(subtleLine1);
            
            const subtleLine2 = document.createElement('div');
            subtleLine2.className = 'scan-line subtle';
            subtleLine2.style.opacity = '0.2';
            subtleLine2.style.animationDelay = '1s';
            scanningOverlay.appendChild(subtleLine2);
            
            // Add subtle pulse to the face guide
            faceGuide.classList.add('pulse-border');
            
            // Add glowing effect to video container during scan
            videoContainer.classList.add('processing-glow');
            
            // Animar linha de escaneamento
            let position = 0;
            const scanAnimation = setInterval(() => {
                if (position >= videoContainer.offsetHeight || !isProcessing) {
                    // When reaching bottom, start over for continuous effect
                    if (isProcessing) {
                        position = 0;
                    } else {
                        clearInterval(scanAnimation);
                        scanLine.remove();
                        subtleLine1.remove();
                        subtleLine2.remove();
                        faceGuide.classList.remove('pulse-border');
                        videoContainer.classList.remove('processing-glow');
                        return;
                    }
                }
                
                position += 2;
                scanLine.style.top = position + 'px';
                
                // Move secondary lines at different speeds for visual effect
                subtleLine1.style.top = (position * 0.8) + 'px';
                subtleLine2.style.top = (position * 0.6) + 'px';
            }, 10);
        }

        function startFaceDetection() {
            isProcessing = true;
            createScanAnimation();
            showStatus('Detectando seu rosto...', 'processing');

            let attempts = 0;
            detectionInterval = setInterval(async () => {
                try {
                    if (!video.srcObject) {
                        clearInterval(detectionInterval);
                        return;
                    }
                    const detections = await faceapi
                        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    attempts++;
                    if (detections) {
                        clearInterval(detectionInterval);
                        const context = canvas.getContext('2d');
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const faceDescriptor = Array.from(detections.descriptor);
                        video.style.display = 'none';
                        canvas.style.display = 'block';
                        faceGuide.style.display = 'none';
                        scanningOverlay.style.display = 'none';
                        videoContainer.classList.remove('processing-glow');
                        showStatus('Verificando identidade...', 'processing');
                        ativarReconhecimentoBtn.innerHTML =
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> VERIFICANDO IDENTIDADE...';
                        sendFaceForVerification(faceDescriptor);
                    } else if (attempts >= 20) {
                        clearInterval(detectionInterval);
                        showStatus(
                            'Não foi possível detectar seu rosto. Por favor, verifique a iluminação e posicionamento.',
                            'error'
                        );
                        resetInterface();
                    }
                } catch (error) {
                    console.error('Erro durante processamento:', error);
                    clearInterval(detectionInterval);
                    showStatus(
                        'Ocorreu um erro durante o processamento. Por favor, tente novamente.',
                        'error'
                    );
                    resetInterface();
                }
            }, 500);
        }

        function sendFaceForVerification(faceDescriptor) {
            showStatus('Verificando identidade...', 'processing');
            
            // Get the CSRF token from meta tag
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // If meta tag not found, try to get from form
            if (!token) {
                const csrfInput = document.querySelector('input[name="_token"]');
                if (csrfInput) {
                    token = csrfInput.value;
                }
            }
            
            // Check if we have a token
            if (!token) {
                console.error('CSRF token não encontrado');
                showStatus('Erro de segurança: Token CSRF não encontrado. Recarregue a página.', 'error');
                resetInterface();
                return;
            }
            
            fetch('/facial-login', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    face_descriptor: faceDescriptor
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        try {
                            // Try to parse as JSON
                            const errorData = JSON.parse(text);
                            throw new Error(errorData.message || `Server error: ${response.status}`);
                        } catch (e) {
                            // If not JSON, use the text
                            throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
                        }
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showStatus('Usuário reconhecido! Redirecionando...', 'success');
                    videoContainer.style.borderColor = '#28a745';
                    videoContainer.style.boxShadow = '0 0 15px rgba(40, 167, 69, 0.5)';
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showStatus(
                        data.message || 'Usuário não reconhecido. Tente novamente ou use e-mail e senha.',
                        'error'
                    );
                    resetInterface();
                }
            })
            .catch(error => {
                console.error('Erro durante verificação:', error);
                showStatus(`Erro: ${error.message}`, 'error');
                resetInterface();
            });
        }

        function resetInterface() {
            isProcessing = false;
            if (detectionInterval) clearInterval(detectionInterval);
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.style.display = 'none';
            canvas.style.display = 'none';
            cameraIconContainer.style.display = 'flex';
            faceGuide.style.display = 'none';
            scanningOverlay.style.display = 'none';
            processingIndicator.style.display = 'none';
            faceGuide.classList.remove('pulse-border');
            videoContainer.classList.remove('processing-glow');
            ativarReconhecimentoBtn.disabled = false;
            ativarReconhecimentoBtn.innerHTML = 'TENTAR NOVAMENTE';
            videoContainer.style.borderColor = '#ddd';
            videoContainer.style.boxShadow = 'none';
        }

        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
        
        // Adicionar botão de teste para depuração - melhorado com verificação de CSRF
        function addTestButton() {
            const testBtn = document.createElement('button');
            testBtn.id = 'test-api-btn';
            testBtn.textContent = 'Testar API';
            testBtn.className = 'btn btn-sm btn-secondary';
            testBtn.style.position = 'fixed';
            testBtn.style.bottom = '10px';
            testBtn.style.right = '10px';
            testBtn.style.zIndex = '9999';
            
            testBtn.addEventListener('click', function() {
                console.log('Testando API...');
                
                // Obter o token CSRF de várias formas possíveis
                let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                if (!token) {
                    const csrfInput = document.querySelector('input[name="_token"]');
                    if (csrfInput) {
                        token = csrfInput.value;
                    }
                }
                
                if (!token) {
                    console.error('CSRF token não encontrado');
                    alert('CSRF token não encontrado. Verifique se a meta tag csrf-token existe no cabeçalho da página.');
                    return;
                }
                
                console.log('CSRF Token encontrado:', token);
                
                // Criar um descritor de teste (128 valores entre 0 e 1)
                const testDescriptor = Array.from({length: 128}, () => Math.random());
                
                // Usar fetch com o token CSRF
                fetch('/facial-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ 
                        face_descriptor: testDescriptor
                    })
                })
                .then(response => {
                    console.log('Status da resposta:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Resposta do teste:', text);
                    alert('Resposta do teste: ' + text.substring(0, 100));
                })
                .catch(error => {
                    console.error('Erro no teste:', error);
                    alert('Erro no teste: ' + error.message);
                });
            });
            
            document.body.appendChild(testBtn);
        }
        
        // Adicionar o botão de teste (útil para depuração)
        addTestButton();
    });
</script>
@endsection