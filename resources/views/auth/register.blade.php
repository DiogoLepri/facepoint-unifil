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
    
    .required-asterisk {
        color: #dc3545;
        margin-left: 3px;
        font-weight: bold;
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
        font-weight: 600;
        padding: 10px;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
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
        position: relative;
        overflow: hidden;
    }

    .btn-success:hover:not(:disabled) {
        background-color: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .btn-success:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .btn-success:not(:disabled) {
        animation: buttonReady 1.5s infinite;
    }

    @keyframes buttonReady {
        0%, 100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        50% { box-shadow: 0 0 15px 5px rgba(40, 167, 69, 0.2); }
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
        transform: scaleX(-1);
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
        gap: 8px;
        padding: 8px 15px;
        border-radius: 20px;
        background-color: #f8f9fa;
        border: 2px solid #ddd;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .quality-indicator.good {
        background-color: #d4edda;
        border-color: #28a745;
        color: #155724;
        animation: pulseGreen 2s infinite;
    }

    .quality-indicator.bad {
        background-color: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }

    .quality-indicator .icon {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #6c757d;
        transition: all 0.3s ease;
        box-shadow: 0 0 0 0 rgba(108, 117, 125, 0.4);
    }

    .quality-indicator.good .icon {
        background-color: #28a745;
        box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
    }

    .quality-indicator.bad .icon {
        background-color: #dc3545;
        box-shadow: 0 0 4px rgba(220, 53, 69, 0.4);
    }

    @keyframes pulseGreen {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
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
<div class="main-container fade-in-up">
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
                    <h2 class="section-title"><i class="fas fa-user me-2 text-primary-custom"></i>Dados Pessoais</h2>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Completo:<span class="required-asterisk">*</span></label>
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
                        <label for="matricula" class="form-label">Matrícula:<span class="required-asterisk">*</span></label>
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
                        <label for="email" class="form-label">E-mail Institucional:<span class="required-asterisk">*</span></label>
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
                        <label for="curso" class="form-label">Curso:<span class="required-asterisk">*</span></label>
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
                        <label for="password" class="form-label">Senha:<span class="required-asterisk">*</span></label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               minlength="8"
                               required>
                        <small class="form-text text-muted">A senha deve conter pelo menos 8 dígitos</small>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">Confirmar Senha:<span class="required-asterisk">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="password-confirm" 
                               name="password_confirmation" 
                               required>
                    </div>
                </div>
                
                <!-- Right Column - Facial Registration -->
                <div>
                    <h2 class="section-title"><i class="fas fa-camera me-2 text-primary-custom"></i>Registro Facial</h2>
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
                            Concordo com a coleta de dados biométricos (LGPD)<span class="required-asterisk">*</span>
                        </label>
                    </div>
                    
                    <!-- Campos face_data removidos - biometria agora é cadastrada via /admin/facial/enrol (ETAPA 2) -->
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
<!-- face-api.js removido - agora usando Flask API (DeepFace + Facenet512) -->
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
    let capturedImageData = null; // Base64 da foto capturada (será enviado na ETAPA 2)

    // Using Flask API (DeepFace + Facenet512) - No need to load face-api models
    console.log('Sistema de registro facial via Flask API inicializado (2 ETAPAS)');

    ativarCameraBtn.addEventListener('click', async function() {
        
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
        // Start continuous quality verification
        verificationInterval = setInterval(performQualityCheck, 500); // Check every 500ms
    }

    async function performQualityCheck() {
        if (photoCaptured) {
            clearInterval(verificationInterval);
            return;
        }

        try {
            // Set canvas to video size for analysis
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            // Draw current video frame
            ctx.save();
            ctx.scale(-1, 1);
            ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
            ctx.restore();

            // Get image data for analysis
            const imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);

            // Analyze quality metrics
            const faceDetected = await detectFace(imageDataUrl);
            const brightness = calculateImageBrightness();
            const faceSize = faceDetected ? calculateFaceSize(faceDetected) : 0;

            // Update quality status
            qualityStatus.face = faceDetected !== null;
            qualityStatus.brightness = brightness >= 60 && brightness <= 180;
            qualityStatus.size = faceSize >= 0.15 && faceSize <= 0.5;

            // Update UI indicators
            updateIndicator(document.getElementById('face-indicator'), qualityStatus.face);
            updateIndicator(document.getElementById('brightness-indicator'), qualityStatus.brightness);
            updateIndicator(document.getElementById('size-indicator'), qualityStatus.size);

            // Update face guide visual feedback
            const faceGuide = document.getElementById('face-guide');
            if (qualityStatus.face) {
                faceGuide.classList.add('detected');
            } else {
                faceGuide.classList.remove('detected');
            }

            // Enable/disable capture button based on all quality checks
            const allQualityGood = qualityStatus.face && qualityStatus.brightness && qualityStatus.size;

            if (allQualityGood) {
                ativarCameraBtn.disabled = false;
                ativarCameraBtn.style.backgroundColor = '#28a745';
                cameraStatus.textContent = '✓ Qualidade adequada! Clique para capturar';
                cameraStatus.style.color = '#28a745';
            } else {
                ativarCameraBtn.disabled = true;
                ativarCameraBtn.style.backgroundColor = '#6c757d';

                // Provide specific feedback
                if (!qualityStatus.face) {
                    cameraStatus.textContent = '⚠ Nenhum rosto detectado';
                    cameraStatus.style.color = '#dc3545';
                } else if (!qualityStatus.size) {
                    if (faceSize < 0.15) {
                        cameraStatus.textContent = '⚠ Aproxime-se da câmera';
                    } else {
                        cameraStatus.textContent = '⚠ Afaste-se da câmera';
                    }
                    cameraStatus.style.color = '#ffc107';
                } else if (!qualityStatus.brightness) {
                    if (brightness < 60) {
                        cameraStatus.textContent = '⚠ Ambiente muito escuro';
                    } else {
                        cameraStatus.textContent = '⚠ Ambiente muito claro';
                    }
                    cameraStatus.style.color = '#ffc107';
                }
            }

        } catch (error) {
            console.error('Error in quality check:', error);
        }
    }

    // Detect face using a simple method (color detection and shape analysis)
    async function detectFace(imageDataUrl) {
        try {
            // Create a temporary image
            const img = new Image();
            img.src = imageDataUrl;

            await new Promise((resolve) => {
                img.onload = resolve;
            });

            // Create a temporary canvas for analysis
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = 160; // Reduce size for faster processing
            tempCanvas.height = 120;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(img, 0, 0, 160, 120);

            const imageData = tempCtx.getImageData(0, 0, 160, 120);
            const data = imageData.data;

            // Simple skin tone detection
            let skinPixels = 0;
            let totalPixels = 0;

            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];

                totalPixels++;

                // Simple skin tone detection (HSV-based approximation)
                // Skin tones typically have: R > 95, G > 40, B > 20, R > G, R > B
                if (r > 95 && g > 40 && b > 20 &&
                    r > g && r > b &&
                    Math.abs(r - g) > 15) {
                    skinPixels++;
                }
            }

            const skinRatio = skinPixels / totalPixels;

            // If more than 8% of pixels are skin-colored, assume face is present
            return skinRatio > 0.08 ? { skinRatio } : null;

        } catch (error) {
            console.error('Face detection error:', error);
            return null;
        }
    }

    function calculateImageBrightness() {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        let sum = 0;

        for (let i = 0; i < data.length; i += 4) {
            // Calculate luminance using standard formula (ITU-R BT.709)
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            sum += (0.2126 * r + 0.7152 * g + 0.0722 * b);
        }

        return sum / (data.length / 4);
    }

    function calculateFaceSize(faceDetection) {
        // Estimate face size based on skin pixel ratio
        // This is a simplified approach
        return faceDetection.skinRatio * 2; // Scale to approximate face area ratio
    }
    
    function updateIndicator(indicator, isGood) {
        indicator.className = 'quality-indicator ' + (isGood ? 'good' : 'bad');
    }
    
    function capturePhoto() {
        if (photoCaptured) return;

        // Stop real-time verification
        clearInterval(verificationInterval);

        ativarCameraBtn.disabled = true;
        cameraStatus.textContent = 'Capturando foto...';

        // Set canvas dimensions to match video
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;

        // Draw video frame to canvas (correcting the mirroring)
        ctx.save();
        ctx.scale(-1, 1);
        ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
        ctx.restore();

        // Create high-quality image data (base64)
        const imageData = canvas.toDataURL('image/jpeg', 0.95);

        // SALVAR NA VARIÁVEL GLOBAL (será enviado na ETAPA 2)
        capturedImageData = imageData;

        console.log('✓ Foto capturada e salva em memória (tamanho:', imageData.length, 'bytes)');

        // Update photo preview
        const photoItem = document.getElementById('photo-1');
        const img = photoItem.querySelector('img');

        img.src = imageData;
        img.onload = function() {
            photoItem.style.display = 'block';
            console.log('Preview da foto exibido');
        };

        // Update progress
        progressBar.style.width = '100%';
        photoCaptured = true;

        cameraStatus.textContent = '✓ Foto capturada com sucesso!';
        ativarCameraBtn.textContent = 'FOTO CAPTURADA ✓';
        ativarCameraBtn.style.backgroundColor = '#28a745';

        // Stop camera
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }

        // Hide camera container and indicators
        document.getElementById('video-container').style.display = 'none';
        document.getElementById('quality-indicators').style.display = 'none';
    }
    
    // Form validation
    // FLUXO DE 2 ETAPAS: Cadastro de Usuário + Cadastro Facial via Flask
    document.getElementById('registration-form').addEventListener('submit', async function(e) {
        e.preventDefault(); // Sempre previne submit normal do form

        // Validação: Foto obrigatória
        if (!photoCaptured || !capturedImageData) {
            alert('❌ Por favor, capture sua foto antes de finalizar o cadastro.');
            return false;
        }

        const submitBtn = document.getElementById('submit-btn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            // =============================================
            // ETAPA 1: CRIAR USUÁRIO (/register)
            // =============================================
            submitBtn.disabled = true;
            submitBtn.textContent = 'CRIANDO USUÁRIO...';

            console.log('[ETAPA 1] Criando usuário via POST /register');

            const formData = new FormData(this);

            const registerResponse = await fetch('/register', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const registerData = await registerResponse.json();

            if (!registerResponse.ok || !registerData.success) {
                const errorMessage = registerData.message || 'Erro ao criar usuário';
                throw new Error(errorMessage);
            }

            const createdUserId = registerData.user_id;

            if (!createdUserId) {
                throw new Error('Servidor não retornou user_id');
            }

            console.log('[ETAPA 1] ✓ Usuário criado com sucesso. user_id:', createdUserId);

            // =============================================
            // ETAPA 2: CADASTRAR BIOMETRIA (/admin/facial/enrol)
            // =============================================
            submitBtn.textContent = 'CADASTRANDO BIOMETRIA...';

            console.log('[ETAPA 2] Cadastrando biometria via POST /admin/facial/enrol');

            const enrolResponse = await fetch('/admin/facial/enrol', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_id: createdUserId,
                    image_data: capturedImageData  // Base64 da foto capturada
                }),
                credentials: 'same-origin'
            });

            const enrolData = await enrolResponse.json();

            console.log('[ETAPA 2] Resposta enrol:', enrolData);

            if (!enrolData.ok || enrolData.ok !== true) {
                // Falha no cadastro facial
                const stage = enrolData.stage || 'unknown';
                const error = enrolData.error || 'Erro desconhecido ao cadastrar biometria';

                console.error('[ETAPA 2] ✗ Falha:', stage, error);

                let userMessage = `❌ Erro ao cadastrar biometria facial:\n\n${error}`;

                if (stage === 'flask_failed') {
                    userMessage += '\n\nMotivo: O sistema de reconhecimento facial não detectou um rosto válido na imagem.';
                    userMessage += '\n\nTente tirar outra foto com melhor iluminação.';
                } else if (stage === 'flask_unreachable') {
                    userMessage += '\n\nMotivo: Não foi possível conectar ao serviço de reconhecimento facial.';
                    userMessage += '\n\nContate o administrador.';
                }

                alert(userMessage);
                submitBtn.disabled = false;
                submitBtn.textContent = 'FINALIZAR CADASTRO';
                return false;
            }

            // =============================================
            // SUCESSO COMPLETO!
            // =============================================
            console.log('[ETAPA 2] ✓ Biometria cadastrada com sucesso!');
            console.log('✓✓✓ CADASTRO COMPLETO! Redirecionando para /dashboard...');

            submitBtn.textContent = 'CADASTRO CONCLUÍDO ✓';
            submitBtn.style.backgroundColor = '#28a745';

            alert('✅ Cadastro realizado com sucesso!\n\nSeu rosto foi cadastrado no sistema.');

            // Redirecionar para dashboard
            window.location.href = '/dashboard';

        } catch (error) {
            console.error('Erro durante cadastro:', error);
            alert('❌ Erro ao realizar cadastro:\n\n' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = 'FINALIZAR CADASTRO';
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