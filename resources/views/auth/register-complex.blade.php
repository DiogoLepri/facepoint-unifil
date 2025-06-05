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
        width: 200px;
        height: 200px;
        border-radius: 15px;
        overflow: hidden;
        border: 3px solid #28a745;
        position: relative;
        display: none;
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        background-color: #f8f9fa;
    }
    
    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .photo-item.captured {
        border-color: #28a745;
        animation: fadeIn 0.5s ease-in;
    }
    
    .photo-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(40, 167, 69, 0.9), transparent);
        color: white;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        padding: 8px 4px;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .camera-container.active {
        border-color: #003366;
        border-style: solid;
        background-color: #f8f9fa;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 51, 102, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(0, 51, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 51, 102, 0); }
    }
    
    .face-guide {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 150px;
        height: 190px;
        border: 2px dashed rgba(0, 51, 102, 0.5);
        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
        pointer-events: none;
        z-index: 10;
    }
    
    .face-guide.detected {
        border-color: #28a745;
        border-style: solid;
        animation: faceDetected 0.5s ease;
    }
    
    @keyframes faceDetected {
        0% { transform: translate(-50%, -50%) scale(1); }
        50% { transform: translate(-50%, -50%) scale(1.1); }
        100% { transform: translate(-50%, -50%) scale(1); }
    }
    
    .quality-indicators {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin: 10px 0;
        font-size: 0.85rem;
    }
    
    .quality-indicator {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 15px;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        transition: all 0.3s;
    }
    
    .quality-indicator.good {
        background-color: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    
    .quality-indicator.bad {
        background-color: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }
    
    .quality-indicator .icon {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #6c757d;
    }
    
    .quality-indicator.good .icon {
        background-color: #28a745;
    }
    
    .quality-indicator.bad .icon {
        background-color: #dc3545;
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
                               pattern="[A-Za-zÀ-ÿ\s]+"
                               title="Apenas letras e espaços são permitidos"
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
                               pattern="[0-9]{9}"
                               maxlength="9"
                               minlength="9"
                               title="Matrícula deve conter exatamente 9 números"
                               placeholder="000000000"
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
                               pattern="[a-zA-Z0-9._%+-]+@edu\.unifil\.br$"
                               title="Email deve terminar com @edu.unifil.br"
                               placeholder="seuemail@edu.unifil.br"
                               required>
                        @error('email')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="curso" class="form-label">Curso:</label>
                        <select class="form-control @error('curso') is-invalid @enderror" 
                                id="curso" 
                                name="curso" 
                                required>
                            <option value="">Selecione seu curso</option>
                            <option value="Ciencia da Computacao" {{ old('curso') == 'Ciencia da Computacao' ? 'selected' : '' }}>Ciência da Computação</option>
                            <option value="Engenharia de Software" {{ old('curso') == 'Engenharia de Software' ? 'selected' : '' }}>Engenharia de Software</option>
                        </select>
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
                        <div class="face-guide" id="face-guide" style="display: none;"></div>
                    </div>
                    
                    <div class="quality-indicators" id="quality-indicators" style="display: none;">
                        <div class="quality-indicator" id="face-indicator">
                            <div class="icon"></div>
                            <span>Rosto</span>
                        </div>
                        <div class="quality-indicator" id="brightness-indicator">
                            <div class="icon"></div>
                            <span>Iluminação</span>
                        </div>
                        <div class="quality-indicator" id="size-indicator">
                            <div class="icon"></div>
                            <span>Tamanho</span>
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
                            <span id="position-instruction">Olhe diretamente para a câmera</span>
                        </p>
                    </div>
                    
                    <div class="photo-preview" id="photo-preview" style="display: none;">
                        <div class="photo-item captured" id="photo-1">
                            <img src="" alt="Foto Facial">
                            <div class="photo-label">✓ Capturada</div>
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
    let photoCaptured = false;
    let faceDescriptor = null;
    let modelsLoaded = false;
    
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
            
            video.onloadedmetadata = function() {
                video.style.display = 'block';
                cameraPlaceholder.style.display = 'none';
                document.getElementById('video-container').classList.add('active');
                
                // Update UI
                cameraStatus.textContent = 'Posicione seu rosto dentro do guia e aguarde a verificação';
                ativarCameraBtn.textContent = 'CAPTURAR FOTO';
                ativarCameraBtn.onclick = capturePhoto;
                ativarCameraBtn.disabled = true; // Will be enabled when quality is good
                
                // Show face guide and quality indicators
                document.getElementById('face-guide').style.display = 'block';
                document.getElementById('quality-indicators').style.display = 'flex';
                
                // Start real-time verification
                startRealTimeVerification();
                
                console.log('Video metadata loaded, dimensions:', video.videoWidth, 'x', video.videoHeight);
            };
            
            captureProgress.style.display = 'block';
            photoPreview.style.display = 'flex';
            photoPreview.style.justifyContent = 'center';
            
        } catch (error) {
            console.error('Error accessing camera:', error);
            alert('Erro ao acessar câmera. Verifique as permissões.');
        }
    });
    
    let verificationInterval;
    let qualityStatus = {
        face: false,
        brightness: false,
        size: false
    };
    
    function startRealTimeVerification() {
        verificationInterval = setInterval(async () => {
            if (photoCaptured) {
                clearInterval(verificationInterval);
                return;
            }
            
            await performQualityCheck();
        }, 500); // Check every 500ms
    }
    
    async function performQualityCheck() {
        // Set canvas dimensions to match video
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        // Draw current video frame to canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        try {
            // Detect face
            const detections = await faceapi
                .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            const faceGuide = document.getElementById('face-guide');
            const faceIndicator = document.getElementById('face-indicator');
            const brightnessIndicator = document.getElementById('brightness-indicator');
            const sizeIndicator = document.getElementById('size-indicator');
            
            if (detections) {
                // Face detected
                qualityStatus.face = true;
                faceGuide.classList.add('detected');
                updateIndicator(faceIndicator, true);
                
                // Check face size (relative to image)
                const faceBox = detections.detection.box;
                const faceArea = faceBox.width * faceBox.height;
                const imageArea = canvas.width * canvas.height;
                const faceRatio = faceArea / imageArea;
                
                qualityStatus.size = faceRatio > 0.1 && faceRatio < 0.6; // Face should be 10-60% of image
                updateIndicator(sizeIndicator, qualityStatus.size);
                
                // Check brightness
                const brightness = calculateBrightness(canvas, faceBox);
                qualityStatus.brightness = brightness > 50 && brightness < 200; // Good range
                updateIndicator(brightnessIndicator, qualityStatus.brightness);
                
            } else {
                // No face detected
                qualityStatus.face = false;
                faceGuide.classList.remove('detected');
                updateIndicator(faceIndicator, false);
                updateIndicator(sizeIndicator, false);
                updateIndicator(brightnessIndicator, false);
            }
            
            // Update button state and message
            const allGood = qualityStatus.face && qualityStatus.brightness && qualityStatus.size;
            ativarCameraBtn.disabled = !allGood;
            
            if (allGood) {
                cameraStatus.textContent = '✓ Qualidade boa! Clique para capturar';
                ativarCameraBtn.style.backgroundColor = '#28a745';
            } else {
                let issues = [];
                if (!qualityStatus.face) issues.push('rosto não detectado');
                if (!qualityStatus.brightness) issues.push('ajuste a iluminação');
                if (!qualityStatus.size) issues.push('ajuste a distância');
                
                cameraStatus.textContent = 'Aguardando: ' + issues.join(', ');
                ativarCameraBtn.style.backgroundColor = '#6c757d';
            }
            
        } catch (error) {
            console.error('Error during quality check:', error);
        }
    }
    
    function updateIndicator(indicator, isGood) {
        indicator.className = 'quality-indicator ' + (isGood ? 'good' : 'bad');
    }
    
    function calculateBrightness(canvas, faceBox) {
        const imageData = ctx.getImageData(faceBox.x, faceBox.y, faceBox.width, faceBox.height);
        const data = imageData.data;
        let sum = 0;
        
        for (let i = 0; i < data.length; i += 4) {
            // Calculate luminance using standard formula
            sum += (0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2]);
        }
        
        return sum / (data.length / 4);
    }
    
    async function capturePhoto() {
        if (photoCaptured) return;
        
        // Stop real-time verification
        clearInterval(verificationInterval);
        
        ativarCameraBtn.disabled = true;
        cameraStatus.textContent = 'Capturando e verificando qualidade...';
        
        // Set canvas dimensions to match video
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        
        // Draw video frame to canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        try {
            // Final quality check
            const detections = await faceapi
                .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (detections) {
                // Additional quality validation
                const qualityCheck = await performFinalQualityCheck(detections);
                
                if (qualityCheck.passed) {
                    // Save face descriptor (create 3 copies for backward compatibility)
                    faceDescriptor = Array.from(detections.descriptor);
                    document.getElementById('face_data').value = JSON.stringify(faceDescriptor);
                    document.getElementById('face_data_2').value = JSON.stringify(faceDescriptor);
                    document.getElementById('face_data_3').value = JSON.stringify(faceDescriptor);
                    
                    // Update photo preview
                    const photoItem = document.getElementById('photo-1');
                    const img = photoItem.querySelector('img');
                    
                    // Create high-quality image data
                    const imageData = canvas.toDataURL('image/jpeg', 0.95);
                    
                    img.src = imageData;
                    img.onload = function() {
                        photoItem.style.display = 'block';
                        console.log('High-quality photo captured and displayed');
                    };
                    
                    // Update progress
                    progressBar.style.width = '100%';
                    photoCaptured = true;
                    
                    cameraStatus.textContent = '✓ Foto de alta qualidade capturada!';
                    ativarCameraBtn.textContent = 'FOTO CAPTURADA ✓';
                    ativarCameraBtn.style.backgroundColor = '#28a745';
                    
                    // Stop camera
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    
                    // Hide camera container and indicators
                    document.getElementById('video-container').style.display = 'none';
                    document.getElementById('quality-indicators').style.display = 'none';
                    
                } else {
                    cameraStatus.textContent = 'Qualidade insuficiente: ' + qualityCheck.reason;
                    ativarCameraBtn.disabled = false;
                    ativarCameraBtn.textContent = 'TENTAR NOVAMENTE';
                    startRealTimeVerification(); // Restart verification
                }
                
            } else {
                cameraStatus.textContent = 'Nenhum rosto detectado durante a captura. Tente novamente.';
                ativarCameraBtn.disabled = false;
                ativarCameraBtn.textContent = 'TENTAR NOVAMENTE';
                startRealTimeVerification(); // Restart verification
            }
        } catch (error) {
            console.error('Error during face capture:', error);
            cameraStatus.textContent = 'Erro durante captura. Tente novamente.';
            ativarCameraBtn.disabled = false;
            ativarCameraBtn.textContent = 'TENTAR NOVAMENTE';
            startRealTimeVerification(); // Restart verification
        }
    }
    
    async function performFinalQualityCheck(detections) {
        const faceBox = detections.detection.box;
        const faceArea = faceBox.width * faceBox.height;
        const imageArea = canvas.width * canvas.height;
        const faceRatio = faceArea / imageArea;
        
        // Check if face is properly sized
        if (faceRatio < 0.1) {
            return { passed: false, reason: 'rosto muito pequeno, aproxime-se da câmera' };
        }
        
        if (faceRatio > 0.6) {
            return { passed: false, reason: 'rosto muito próximo, afaste-se da câmera' };
        }
        
        // Check brightness
        const brightness = calculateBrightness(canvas, faceBox);
        if (brightness < 50) {
            return { passed: false, reason: 'imagem muito escura, melhore a iluminação' };
        }
        
        if (brightness > 200) {
            return { passed: false, reason: 'imagem muito clara, reduza a iluminação' };
        }
        
        // Check if face is centered
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const faceCenterX = faceBox.x + faceBox.width / 2;
        const faceCenterY = faceBox.y + faceBox.height / 2;
        
        const offsetX = Math.abs(faceCenterX - centerX);
        const offsetY = Math.abs(faceCenterY - centerY);
        
        if (offsetX > canvas.width * 0.2 || offsetY > canvas.height * 0.2) {
            return { passed: false, reason: 'centralize o rosto na câmera' };
        }
        
        return { passed: true };
    }
    
    // Form validation
    document.getElementById('registration-form').addEventListener('submit', function(e) {
        if (!photoCaptured) {
            e.preventDefault();
            alert('Por favor, capture sua foto para o registro facial.');
            return false;
        }
    });
    
    // Real-time validation
    document.getElementById('matricula').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);
    });
    
    document.getElementById('name').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^A-Za-zÀ-ÿ\s]/g, '');
    });
    
    document.getElementById('email').addEventListener('blur', function(e) {
        if (this.value && !this.value.endsWith('@edu.unifil.br')) {
            if (!this.value.includes('@')) {
                this.value += '@edu.unifil.br';
            } else if (!this.value.endsWith('@edu.unifil.br')) {
                this.setCustomValidity('Email deve terminar com @edu.unifil.br');
            }
        } else {
            this.setCustomValidity('');
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        if (verificationInterval) {
            clearInterval(verificationInterval);
        }
    });
});
</script>
@endsection