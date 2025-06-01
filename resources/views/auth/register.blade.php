@extends('layouts.app')

@section('title', 'Cadastro de Novo Usuário')

@section('styles')
<style>
    html, body {
        background-color: #f5f5f5;
        overflow-x: hidden;
        height: 100%;
    }
    
    .main-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        min-height: calc(100vh - 80px);
    }
    
    .page-title {
        color: #003366;
        font-size: 1.8rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 0.5rem;
    }
    
    .page-subtitle {
        color: #666;
        font-size: 0.9rem;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .form-container {
        background: white;
        border-radius: 10px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    
    .section-title {
        color: #003366;
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .section-subtitle {
        color: #666;
        font-size: 0.85rem;
        margin-top: -15px;
        margin-bottom: 20px;
    }
    
    .form-label {
        color: #333;
        font-weight: 500;
        margin-bottom: 5px;
        font-size: 0.95rem;
    }
    
    .form-control {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        font-size: 0.95rem;
        width: 100%;
    }
    
    .form-control:focus {
        border-color: #003366;
        box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.25);
        outline: none;
    }
    
    .camera-container {
        width: 200px;
        height: 200px;
        margin: 0 auto 20px;
        border: 2px dashed #ccc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
        position: relative;
        overflow: hidden;
    }
    
    .camera-icon {
        font-size: 48px;
        color: #6c757d;
    }
    
    .camera-status {
        text-align: center;
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    
    .btn {
        padding: 12px 30px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9rem;
        border-radius: 5px;
        border: none;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .btn-success {
        background-color: #28a745;
        color: white;
        width: 100%;
    }
    
    .btn-success:hover {
        background-color: #218838;
    }
    
    .btn-primary {
        background-color: #003366;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #002244;
    }
    
    .btn-secondary {
        background-color: #e9ecef;
        color: #333;
    }
    
    .btn-secondary:hover {
        background-color: #dae0e5;
    }
    
    .form-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #eee;
    }
    
    .form-check {
        margin-top: 20px;
    }
    
    .form-check-input {
        margin-right: 8px;
    }
    
    .form-check-label {
        color: #666;
        font-size: 0.9rem;
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
    
    .photo-preview {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 15px;
    }
    
    .photo-item {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #ddd;
        position: relative;
    }
    
    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .photo-item.captured {
        border-color: #28a745;
    }
    
    .photo-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        font-size: 10px;
        text-align: center;
        padding: 2px;
    }
    
    .capture-progress {
        margin-top: 15px;
    }
    
    .progress {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        background-color: #28a745;
        transition: width 0.3s ease;
    }
    
    .capture-status {
        text-align: center;
        font-size: 0.85rem;
        color: #666;
        margin-top: 5px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .form-footer {
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="main-container">
    <h1 class="page-title">Cadastro de Novo Usuário</h1>
    <p class="page-subtitle">Preencha os campos abaixo para criar sua conta no sistema</p>
    
    <div class="form-container">
        <form method="POST" action="{{ route('register') }}" id="registration-form">
            @csrf
            
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="form-grid">
                <!-- Left Column - Personal Data -->
                <div>
                    <h2 class="section-title">Dados Pessoais</h2>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo:</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               autofocus>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="matricula" class="form-label">Matrícula:</label>
                        <input type="text" 
                               class="form-control @error('matricula') is-invalid @enderror" 
                               id="matricula" 
                               name="matricula" 
                               value="{{ old('matricula') }}" 
                               required>
                        @error('matricula')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail Institucional:</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               placeholder="seuemail@edu.unifil.br"
                               required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="curso" class="form-label">Curso:</label>
                        <input type="text" 
                               class="form-control @error('curso') is-invalid @enderror" 
                               id="curso" 
                               name="curso" 
                               value="{{ old('curso') }}" 
                               required>
                        @error('curso')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha:</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               required>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">Confirmar Senha:</label>
                        <input type="password" 
                               class="form-control" 
                               id="password-confirm" 
                               name="password_confirmation" 
                               required>
                    </div>
                </div>
                
                <!-- Right Column - Facial Registration -->
                <div>
                    <h2 class="section-title">Registro Facial</h2>
                    <p class="section-subtitle">Posicione seu rosto para o cadastro biométrico</p>
                    
                    <div class="camera-container" id="video-container">
                        <video id="video" style="display: none;" autoplay muted></video>
                        <canvas id="canvas" style="display: none;"></canvas>
                        <div id="camera-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" viewBox="0 0 16 16">
                                <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                                <path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM8 14.5a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <p class="camera-status" id="camera-status">Câmera desativada</p>
                    
                    <button type="button" class="btn btn-success" id="ativar-camera">
                        ATIVAR CÂMERA
                    </button>
                    
                    <div class="capture-progress" id="capture-progress" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar" id="progress-bar" style="width: 0%;"></div>
                        </div>
                        <p class="capture-status">
                            Foto <span id="capture-count">0</span>/3 - 
                            <span id="position-instruction">Prepare-se</span>
                        </p>
                    </div>
                    
                    <div class="photo-preview" id="photo-preview" style="display: none;">
                        <div class="photo-item" id="photo-1">
                            <img src="" alt="Frontal">
                            <div class="photo-label">Frontal</div>
                        </div>
                        <div class="photo-item" id="photo-2">
                            <img src="" alt="Direita">
                            <div class="photo-label">Direita</div>
                        </div>
                        <div class="photo-item" id="photo-3">
                            <img src="" alt="Esquerda">
                            <div class="photo-label">Esquerda</div>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="lgpd-consent" 
                               name="lgpd_consent" 
                               required>
                        <label class="form-check-label" for="lgpd-consent">
                            Concordo com a coleta de dados biométricos (LGPD)
                        </label>
                    </div>
                    
                    <input type="hidden" name="face_data" id="face_data">
                    <input type="hidden" name="face_data_2" id="face_data_2">
                    <input type="hidden" name="face_data_3" id="face_data_3">
                </div>
            </div>
            
            <div class="form-footer">
                <a href="{{ route('login') }}" class="btn btn-secondary">CANCELAR</a>
                <button type="submit" class="btn btn-primary" id="submit-btn">FINALIZAR CADASTRO</button>
            </div>
        </form>
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
    const cameraStatus = document.getElementById('camera-status');
    const captureProgress = document.getElementById('capture-progress');
    const photoPreview = document.getElementById('photo-preview');
    const progressBar = document.getElementById('progress-bar');
    const captureCount = document.getElementById('capture-count');
    const positionInstruction = document.getElementById('position-instruction');
    
    let stream = null;
    let currentCapture = 0;
    let faceDescriptors = [];
    let captureInterval = null;
    let modelsLoaded = false;
    
    const instructions = [
        "Olhe diretamente para a câmera",
        "Vire o rosto levemente para a direita",
        "Vire o rosto levemente para a esquerda"
    ];
    
    // Load face-api models
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
        alert('Erro ao carregar modelos de reconhecimento facial');
    });
    
    ativarCameraBtn.addEventListener('click', async function() {
        if (!modelsLoaded) {
            alert('Aguarde o carregamento dos modelos...');
            return;
        }
        
        try {
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
            
            // Set canvas size
            canvas.width = 200;
            canvas.height = 200;
            
            // Update UI
            cameraStatus.textContent = 'Câmera ativada - Posicione seu rosto';
            ativarCameraBtn.textContent = 'CAPTURAR FOTOS';
            ativarCameraBtn.onclick = startCapture;
            
            captureProgress.style.display = 'block';
            photoPreview.style.display = 'flex';
            
        } catch (error) {
            console.error('Error accessing camera:', error);
            alert('Erro ao acessar câmera. Verifique as permissões.');
        }
    });
    
    async function startCapture() {
        if (currentCapture >= 3) return;
        
        ativarCameraBtn.disabled = true;
        positionInstruction.textContent = instructions[currentCapture];
        
        // Countdown before capture
        let countdown = 3;
        const countdownInterval = setInterval(() => {
            cameraStatus.textContent = `Capturando em ${countdown}...`;
            countdown--;
            
            if (countdown < 0) {
                clearInterval(countdownInterval);
                captureImage();
            }
        }, 1000);
    }
    
    async function captureImage() {
        // Draw video frame to canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        try {
            // Detect face
            const detections = await faceapi
                .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (detections) {
                // Save face descriptor
                faceDescriptors[currentCapture] = Array.from(detections.descriptor);
                document.getElementById(`face_data${currentCapture > 0 ? '_' + (currentCapture + 1) : ''}`).value = 
                    JSON.stringify(faceDescriptors[currentCapture]);
                
                // Update photo preview
                const photoItem = document.getElementById(`photo-${currentCapture + 1}`);
                const img = photoItem.querySelector('img');
                img.src = canvas.toDataURL('image/jpeg');
                photoItem.classList.add('captured');
                
                currentCapture++;
                captureCount.textContent = currentCapture;
                progressBar.style.width = `${(currentCapture / 3) * 100}%`;
                
                if (currentCapture < 3) {
                    cameraStatus.textContent = 'Foto capturada! Prepare-se para a próxima';
                    ativarCameraBtn.disabled = false;
                } else {
                    cameraStatus.textContent = 'Todas as fotos capturadas com sucesso!';
                    ativarCameraBtn.textContent = 'CAPTURAS COMPLETAS';
                    ativarCameraBtn.disabled = true;
                    
                    // Stop camera
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    video.style.display = 'none';
                    canvas.style.display = 'block';
                }
            } else {
                alert('Nenhum rosto detectado. Tente novamente.');
                ativarCameraBtn.disabled = false;
                cameraStatus.textContent = 'Câmera ativada - Posicione seu rosto';
            }
        } catch (error) {
            console.error('Error during face detection:', error);
            alert('Erro durante detecção facial');
            ativarCameraBtn.disabled = false;
        }
    }
    
    // Form validation
    document.getElementById('registration-form').addEventListener('submit', function(e) {
        if (currentCapture < 3) {
            e.preventDefault();
            alert('Por favor, capture todas as 3 fotos necessárias para o registro facial.');
            return false;
        }
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
    });
});
</script>
@endsection