@extends('layouts.app')

@section('title', 'Cadastro de Novo Usuário')

@section('styles')
<style>
    .logo-unifil {
        max-width: 180px;
        margin-bottom: 10px;
    }
    .card {
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .camera-container {
        width: 250px;
        height: 250px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 50%;
        overflow: hidden;
        background-color: #f8f9fa;
    }
    .camera-icon {
        width: 50px;
        height: 50px;
        margin-top: 100px;
        opacity: 0.5;
    }
    .form-label {
        font-weight: 500;
    }
    
    /* Enhanced styles for facial registration */
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
    
    .pulse-border {
        animation: pulse-border 2s infinite;
    }
    
    .processing-glow {
        animation: glowing-circle 1.5s infinite;
    }
    
    .status-badge {
        position: absolute;
        bottom: 15px;
        right: 15px;
        background-color: #28a745;
        color: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .countdown-animation {
        font-size: 80px;
        color: white;
        text-shadow: 0 0 10px rgba(0,0,0,0.7);
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.3s ease;
    }
    
    .countdown-animation.active {
        opacity: 1;
        transform: scale(1);
    }
    
    /* Improved photo display section */
    .photo-gallery {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 15px;
    }
    
    .photo-item {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .photo-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        border: 3px solid #f8f9fa;
        transition: all 0.3s ease;
    }
    
    .photo-item:hover img {
        transform: scale(1.05);
    }
    
    .photo-item.front img {
        border-color: #28a745;
    }
    
    .photo-item.right img {
        border-color: #ffc107;
    }
    
    .photo-item.left img {
        border-color: #17a2b8;
    }
    
    .photo-label {
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0,0,0,0.7);
        color: white;
        font-size: 10px;
        padding: 2px 10px;
        border-radius: 10px;
        white-space: nowrap;
    }
    
    .captures-done {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-align: center;
        margin-top: 10px;
        display: none;
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo-unifil.png') }}" alt="UniFil" class="logo-unifil">
            <h4>Cadastro de Novo Usuário</h4>
            <p class="text-muted small">Preencha os campos abaixo para criar sua conta no sistema</p>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('register') }}" id="registration-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Dados Pessoais</h5>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <input type="text" class="form-control @error('matricula') is-invalid @enderror" 
                                       id="matricula" name="matricula" value="{{ old('matricula') }}" required>
                                @error('matricula')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail Institucional</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <input type="text" class="form-control @error('curso') is-invalid @enderror" 
                                       id="curso" name="curso" value="{{ old('curso') }}" required>
                                @error('curso')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required>
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password-confirm" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" 
                                       id="password-confirm" name="password_confirmation" required>
                            </div>
                        </div>
                        
                        <!-- Registro Facial Aprimorado -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Registro Facial</h5>
                                <p class="text-muted small">Posicione seu rosto para o cadastro biométrico (serão necessárias 3 fotos)</p>
                            </div>

                            <div class="camera-container mb-3" id="video-container">
                                <video id="video" width="250" height="250" autoplay muted style="display: none; object-fit: cover;"></video>
                                <canvas id="canvas" width="250" height="250" style="display: none;"></canvas>
                                <img src="{{ asset('images/camera-icon.png') }}" class="camera-icon" id="camera-placeholder">
                                <div id="face-guide" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 2px dashed #28a745; border-radius: 50%;"></div>
                                <div id="scanning-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: none;"></div>
                                <div id="countdown" class="countdown-animation" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></div>
                                <div id="status-badge" class="status-badge">✓</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" id="ativar-camera">ATIVAR CÂMERA</button>
                                <div id="capture-instructions" class="text-center mt-2 mb-2" style="display: none;">
                                    <p class="mb-1 small">Foto <span id="capture-count">1</span>/3: <span id="position-instruction">Olhe diretamente para a câmera</span></p>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New Photo Gallery Outside the Circle -->
                            <div id="photo-preview-container" style="display: none;">
                                <div class="captures-done" id="captures-complete">
                                    <i class="fas fa-check-circle"></i> Capturas faciais completas
                                </div>
                                <div class="photo-gallery">
                                    <div class="photo-item front">
                                        <img src="" id="capture1" alt="Captura frontal">
                                        <div class="photo-label">Frontal</div>
                                    </div>
                                    <div class="photo-item right">
                                        <img src="" id="capture2" alt="Captura direita">
                                        <div class="photo-label">Direita</div>
                                    </div>
                                    <div class="photo-item left">
                                        <img src="" id="capture3" alt="Captura esquerda">
                                        <div class="photo-label">Esquerda</div>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="face_data" id="face_data">
                            <input type="hidden" name="face_data_2" id="face_data_2">
                            <input type="hidden" name="face_data_3" id="face_data_3">
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('login') }}" class="btn btn-light">CANCELAR</a>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn btn-primary" id="submit-btn">FINALIZAR CADASTRO</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">© 2025 UniFil NPI - Direitos reservados conforme regulação FacePoint</small>
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
        const ctx = canvas.getContext('2d');
        const ativarCameraBtn = document.getElementById('ativar-camera');
        const cameraPlaceholder = document.getElementById('camera-placeholder');
        const faceGuide = document.getElementById('face-guide');
        const captureInstructions = document.getElementById('capture-instructions');
        const captureCount = document.getElementById('capture-count');
        const positionInstruction = document.getElementById('position-instruction');
        const progressBar = document.querySelector('.progress-bar');
        const photoPreviewContainer = document.getElementById('photo-preview-container');
        const capturesComplete = document.getElementById('captures-complete');
        const statusBadge = document.getElementById('status-badge');
        const scanningOverlay = document.getElementById('scanning-overlay');
        const countdownEl = document.getElementById('countdown');
        const faceDataInputs = [
            document.getElementById('face_data'),
            document.getElementById('face_data_2'),
            document.getElementById('face_data_3')
        ];
        const captureImages = [
            document.getElementById('capture1'),
            document.getElementById('capture2'),
            document.getElementById('capture3')
        ];
        
        let stream = null;
        let currentCapture = 0;
        let faceDescriptors = [];
        let detectionInterval = null;
        let countdownInterval = null;
        let countdownValue = 0;
        
        // Instruções para cada captura
        const instructions = [
            "Olhe diretamente para a câmera, mantenha expressão neutra",
            "Vire levemente o rosto para a direita, mantenha os olhos abertos",
            "Vire levemente o rosto para a esquerda, mantenha os olhos abertos"
        ];

        // Posição visual para cada foto
        const faceGuidePositions = [
            { border: '2px solid #28a745', text: 'Centro' },
            { border: '2px solid #ffc107', text: 'Direita' },
            { border: '2px solid #17a2b8', text: 'Esquerda' }
        ];
        
        // Adicionar overlay com posição recomendada
        function createPositionOverlay() {
            const overlay = document.createElement('div');
            overlay.id = 'position-overlay';
            overlay.style.position = 'absolute';
            overlay.style.top = '10px';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.textAlign = 'center';
            overlay.style.color = 'white';
            overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            overlay.style.padding = '5px';
            overlay.style.borderRadius = '4px';
            overlay.style.fontSize = '14px';
            overlay.style.fontWeight = 'bold';
            overlay.style.zIndex = '100';
            document.getElementById('video-container').appendChild(overlay);
            return overlay;
        }
        
        const positionOverlay = createPositionOverlay();
        positionOverlay.style.display = 'none';
        
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
            
            // Add glowing effect to video container during scan
            document.getElementById('video-container').classList.add('processing-glow');
            
            // Animar linha de escaneamento
            let position = 0;
            const scanAnimation = setInterval(() => {
                if (position >= document.getElementById('video-container').offsetHeight) {
                    // When reaching bottom, start over for continuous effect
                    position = 0;
                }
                
                position += 2;
                scanLine.style.top = position + 'px';
                
                // Move secondary lines at different speeds for visual effect
                subtleLine1.style.top = (position * 0.8) + 'px';
                subtleLine2.style.top = (position * 0.6) + 'px';
            }, 10);
            
            return scanAnimation;
        }
        
        // Carregar modelos via CDN
        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
        
        console.log('Iniciando carregamento dos modelos face-api.js...');
        
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]).then(() => {
            console.log('Modelos carregados com sucesso');
            
            // Habilitar botão após carregamento
            ativarCameraBtn.disabled = false;
            ativarCameraBtn.textContent = 'ATIVAR CÂMERA';
            
        }).catch(error => {
            console.error('Erro ao carregar modelos face-api:', error);
            alert('Erro ao carregar os modelos de reconhecimento facial. Por favor, recarregue a página.');
        });
        
        // Desabilitar botão até que modelos sejam carregados
        ativarCameraBtn.disabled = true;
        ativarCameraBtn.textContent = 'CARREGANDO MODELOS...';
        
        // Função para ativar a câmera
        async function activateCamera() {
            console.log('Ativando câmera...');
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: "user"
                    } 
                });
                
                video.srcObject = stream;
                video.style.display = 'block';
                cameraPlaceholder.style.display = 'none';
                faceGuide.style.display = 'block';
                faceGuide.classList.add('pulse-border');
                captureInstructions.style.display = 'block';
                positionOverlay.style.display = 'block';
                
                // Iniciar animação de escaneamento
                let scanAnimation = createScanAnimation();
                
                // Configurar guia visual de acordo com a captura atual
                updateFaceGuide();
                
                // Mudamos para capturar imagem
                ativarCameraBtn.textContent = 'CAPTURAR IMAGEM';
                
                // Removemos o listener antigo e adicionamos o novo
                ativarCameraBtn.onclick = startCountdown;
                
                // Iniciar detecção contínua de rosto
                startFaceDetection();
                
                positionInstruction.textContent = instructions[0];
                progressBar.style.width = '0%';
                
                console.log('Câmera ativada com sucesso');
                
            } catch (err) {
                console.error('Erro ao acessar câmera:', err);
                alert('Não foi possível acessar a câmera. Verifique se você concedeu permissão para uso da câmera no navegador.');
            }
        }
        
        // Atualizar guia visual com base na posição atual
        function updateFaceGuide() {
            faceGuide.style.border = faceGuidePositions[currentCapture].border;
            positionOverlay.textContent = `Posição: ${faceGuidePositions[currentCapture].text}`;
            positionOverlay.style.backgroundColor = faceGuidePositions[currentCapture].border.split(' ')[2];
        }
        
        // Função de contagem regressiva aprimorada
        function startCountdown() {
            if (!video.srcObject) return;
            
            // Desabilitar botão durante a contagem
            ativarCameraBtn.disabled = true;
            ativarCameraBtn.textContent = 'AGUARDE...';
            
            // Iniciar contagem regressiva
            countdownValue = 3;
            
            // Limpar intervalo anterior se existir
            if (countdownInterval) clearInterval(countdownInterval);
            
            // Mostrar primeiro número com animação
            countdownEl.textContent = countdownValue;
            countdownEl.classList.add('active');
            
            countdownInterval = setInterval(() => {
                countdownValue--;
                
                if (countdownValue > 0) {
                    // Efeito de fade out/in entre números
                    countdownEl.classList.remove('active');
                    
                    setTimeout(() => {
                        countdownEl.textContent = countdownValue;
                        countdownEl.classList.add('active');
                    }, 300);
                } else {
                    clearInterval(countdownInterval);
                    countdownEl.classList.remove('active');
                    captureImage(); // Capturar imagem quando contador chegar a 0
                }
            }, 1000);
        }
        
        // Detectar face continuamente para feedback
        function startFaceDetection() {
            console.log('Iniciando detecção de face em tempo real');
            
            // Limpa intervalo anterior se existir
            if (detectionInterval) {
                clearInterval(detectionInterval);
            }
            
            detectionInterval = setInterval(async () => {
                if (!video.srcObject) {
                    console.log('Video stream indisponível, cancelando detecção');
                    clearInterval(detectionInterval);
                    return;
                }
                
                try {
                    // Detectar face
                    const detections = await faceapi.detectSingleFace(
                        video, 
                        new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 })
                    );
                    
                    if (detections) {
                        // Rosto detectado - mostre feedback visual
                        faceGuide.style.borderStyle = 'solid';
                        ativarCameraBtn.disabled = false;
                        
                        // Ajustar progresso
                        progressBar.style.width = '100%';
                    } else {
                        // Rosto não detectado
                        faceGuide.style.borderStyle = 'dashed';
                        ativarCameraBtn.disabled = true;
                        progressBar.style.width = '20%';
                    }
                } catch (error) {
                    console.error('Erro durante detecção contínua de face:', error);
                }
            }, 200);
        }
        
        // Capturar imagem com animações aprimoradas
        async function captureImage() {
            console.log('Função de captura chamada');
            
            if (!video.srcObject) {
                console.error('Stream de vídeo não disponível');
                return;
            }
            
            if (currentCapture >= 3) {
                console.log('Todas as capturas já foram realizadas');
                return;
            }
            
            try {
                // Desenhar frame no canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                console.log('Detectando face na imagem capturada...');
                
                // Detectar face para obter o descritor
                const detections = await faceapi.detectSingleFace(
                    canvas, 
                    new faceapi.TinyFaceDetectorOptions()
                ).withFaceLandmarks().withFaceDescriptor();
                
                console.log('Resultado da detecção:', detections ? 'Face detectada' : 'Nenhuma face detectada');
                
                if (detections) {
                    // Mostrar efeito de flash
                    showFlashEffect();
                    
                    // Salvar descritor facial
                    faceDescriptors[currentCapture] = Array.from(detections.descriptor);
                    faceDataInputs[currentCapture].value = JSON.stringify(faceDescriptors[currentCapture]);
                    
                    console.log(`Descritor facial ${currentCapture + 1} salvo`);
                    
                    // Atualizar preview
                    const imageData = canvas.toDataURL('image/jpeg');
                    captureImages[currentCapture].src = imageData;
                    
                    // Mostrar o container de preview de fotos se estiver escondido
                    photoPreviewContainer.style.display = 'block';
                    
                    // Atualizar interface
                    currentCapture++;
                    
                    // Mostrar efeito de sucesso no badge
                    showSuccessBadge();
                    
                    if (currentCapture < 3) {
                        // Preparar para próxima captura
                        captureCount.textContent = (currentCapture + 1).toString();
                        positionInstruction.textContent = instructions[currentCapture];
                        progressBar.style.width = `${(currentCapture / 3) * 100}%`;
                        
                        // Atualizar guia visual
                        updateFaceGuide();
                        
                        // Atualizar botão
                        ativarCameraBtn.disabled = false;
                        ativarCameraBtn.textContent = 'CAPTURAR IMAGEM';
                        
                        // Reiniciar detecção
                        startFaceDetection();
                        
                        console.log(`Preparado para captura ${currentCapture + 1}/3`);
                    } else {
                        // Todas as capturas concluídas
                        console.log('Todas as capturas concluídas com sucesso');
                        
                        // Mostrar mensagem de conclusão
                        capturesComplete.style.display = 'block';
                        
                        // Atualizar botão
                        ativarCameraBtn.textContent = 'CAPTURAS COMPLETAS';
                        ativarCameraBtn.classList.remove('btn-success');
                        ativarCameraBtn.classList.add('btn-outline-success');
                        ativarCameraBtn.disabled = true;
                        
                        // Parar transmissão da câmera
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                        
                        // Limpar interface
                        video.style.display = 'none';
                        faceGuide.style.display = 'none';
                        captureInstructions.style.display = 'none';
                        positionOverlay.style.display = 'none';
                        scanningOverlay.style.display = 'none';
                        document.getElementById('video-container').classList.remove('processing-glow');
                    }
                } else {
                    console.warn('Nenhuma face detectada na captura');
                    
                    alert('Nenhuma face detectada. Por favor, posicione seu rosto corretamente e tente novamente.');
                    
                    // Re-habilitar botão
                    ativarCameraBtn.disabled = false;
                    ativarCameraBtn.textContent = 'CAPTURAR IMAGEM';
                }
            } catch (error) {
                console.error('Erro durante captura de imagem:', error);
                
                alert('Ocorreu um erro ao processar a imagem. Por favor, tente novamente.');
                
                // Re-habilitar botão
                ativarCameraBtn.disabled = false;
                ativarCameraBtn.textContent = 'CAPTURAR IMAGEM';
            }
        }
        
        // Mostrar efeito de flash ao capturar (aprimorado)
        function showFlashEffect() {
            const flash = document.createElement('div');
            flash.style.position = 'absolute';
            flash.style.top = '0';
            flash.style.left = '0';
            flash.style.width = '100%';
            flash.style.height = '100%';
            flash.style.backgroundColor = 'white';
            flash.style.opacity = '0.8';
            flash.style.zIndex = '99';
            flash.style.borderRadius = '50%';
            
            document.getElementById('video-container').appendChild(flash);
            
            // Animação de fade out mais sofisticada
            setTimeout(() => {
                let opacity = 0.8;
                const fadeInterval = setInterval(() => {
                    opacity -= 0.1;
                    flash.style.opacity = opacity.toString();
                    
                    if (opacity <= 0) {
                        clearInterval(fadeInterval);
                        flash.remove();
                    }
                }, 30);
            }, 100);
        }
        
        // Nova função para mostrar badge de sucesso após captura
        function showSuccessBadge() {
            statusBadge.textContent = currentCapture;
            statusBadge.style.opacity = '1';
            
            setTimeout(() => {
                statusBadge.style.opacity = '0';
            }, 2000);
        }
        
        // Inicializar evento de clique do botão
        ativarCameraBtn.onclick = activateCamera;
        
        // Validação do formulário
        document.getElementById('registration-form').addEventListener('submit', function(e) {
            // Verificar se todas as fotos foram capturadas
            if (currentCapture < 3) {
                e.preventDefault();
                alert('Por favor, capture todas as 3 fotos necessárias para o registro facial.');
                return false;
            }
            
            // Verificar se os dados faciais foram salvos
            for (let i = 0; i < 3; i++) {
                if (!faceDataInputs[i].value) {
                    e.preventDefault();
                    alert('Dados de reconhecimento facial incompletos. Por favor, complete o registro facial.');
                    return false;
                }
            }
            
            console.log('Formulário válido, enviando dados...');
        });
        
        // Limpar recursos quando a página for fechada
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            if (detectionInterval) {
                clearInterval(detectionInterval);
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });
    });
</script>
@endsection