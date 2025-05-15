@extends('layouts.app')

@section('title', 'Registro de Ponto')

@section('styles')
<style>
    .camera-container {
        width: 400px;
        height: 300px;
        margin: 0 auto;
        position: relative;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .camera-icon {
        width: 60px;
        height: 60px;
        opacity: 0.5;
    }
    .nav-pills .nav-link.active {
        background-color: transparent;
        color: #003366;
        border-bottom: 3px solid #003366;
        border-radius: 0;
    }
    .nav-pills .nav-link {
        color: #6c757d;
    }
    .status-info {
        margin-top: 20px;
        padding: 10px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
    .status-card {
        border-left: 5px solid #28a745;
        padding: 10px;
        background-color: #f8f9fa;
        margin-top: 15px;
    }
    .status-card.error {
        border-left-color: #dc3545;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('attendance.create') }}">Registrar Ponto</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('attendance.history') }}">Histórico</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile') }}">Perfil</a>
            </li>
        </ul>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <h4 class="text-center mb-3">Registro de Ponto por Reconhecimento Facial</h4>
                <p class="text-center text-muted small mb-4">Posicione seu rosto na área indicada para registrar sua presença</p>

                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="camera-container mb-3" id="video-container">
                            <video id="video" width="400" height="300" autoplay muted style="display: none;"></video>
                            <canvas id="canvas" width="400" height="300" style="display: none;"></canvas>
                            <img src="{{ asset('images/camera-icon.png') }}" class="camera-icon" id="camera-placeholder">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="ativar-camera">ATIVAR CÂMERA</button>
                        </div>

                        <div class="status-info" id="status-container" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Seu último registro:</strong></p>
                                    <p>Data: <span id="last-date">--/--/----</span></p>
                                    <p>Tipo: <span id="last-type">Entrada</span></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p id="status-message" class="text-success">Registrado com sucesso!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        const ativarCameraBtn = document.getElementById('ativar-camera');
        const cameraPlaceholder = document.getElementById('camera-placeholder');
        const statusContainer = document.getElementById('status-container');
        const lastDate = document.getElementById('last-date');
        const lastType = document.getElementById('last-type');
        const statusMessage = document.getElementById('status-message');
        
        let stream = null;
        
        // Carregar modelos do face-api
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('/models')
        ]).then(() => {
            console.log('Modelos carregados');
        });
        
        // Ativar câmera
        ativarCameraBtn.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;
                video.style.display = 'block';
                cameraPlaceholder.style.display = 'none';
                ativarCameraBtn.textContent = 'VERIFICANDO...';
                ativarCameraBtn.disabled = true;
                
                // Iniciar detecção contínua
                video.addEventListener('play', startFaceDetection);
            } catch (err) {
                console.error('Erro ao acessar câmera:', err);
                alert('Não foi possível acessar a câmera. Verifique as permissões do navegador.');
            }
        });
        
        async function startFaceDetection() {
            const interval = setInterval(async () => {
                if (!video.srcObject) {
                    clearInterval(interval);
                    return;
                }
                
                // Detectar face
                const detections = await faceapi.detectSingleFace(
                    video, 
                    new faceapi.TinyFaceDetectorOptions()
                ).withFaceLandmarks().withFaceDescriptor();
                
                if (detections) {
                    clearInterval(interval);
                    
                    // Capturar frame para exibição
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    video.style.display = 'none';
                    canvas.style.display = 'block';
                    
                    // Enviar para verificação
                    const faceDescriptor = Array.from(detections.descriptor);
                    
                    fetch('/api/attendance/verify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ face_descriptor: faceDescriptor })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ativarCameraBtn.textContent = 'REGISTRADO COM SUCESSO';
                            ativarCameraBtn.classList.remove('btn-success');
                            ativarCameraBtn.classList.add('btn-outline-success');
                            
                            // Mostrar informações
                            statusContainer.style.display = 'block';
                            lastDate.textContent = new Date().toLocaleDateString('pt-BR');
                            lastType.textContent = data.type || 'Entrada';
                            statusMessage.textContent = 'Registrado com sucesso!';
                            statusMessage.classList.remove('text-danger');
                            statusMessage.classList.add('text-success');
                            
                            // Redirecionar após 3 segundos
                            setTimeout(() => {
                                window.location.href = '/dashboard';
                            }, 3000);
                        } else {
                            ativarCameraBtn.textContent = 'FALHA NO RECONHECIMENTO';
                            ativarCameraBtn.classList.remove('btn-success');
                            ativarCameraBtn.classList.add('btn-outline-danger');
                            
                            // Mostrar mensagem de erro
                            statusContainer.style.display = 'block';
                            statusMessage.textContent = data.message || 'Usuário não reconhecido. Tente novamente.';
                            statusMessage.classList.remove('text-success');
                            statusMessage.classList.add('text-danger');
                            
                            // Permitir nova tentativa após 2 segundos
                            setTimeout(resetCamera, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        ativarCameraBtn.textContent = 'ERRO DE COMUNICAÇÃO';
                        ativarCameraBtn.classList.remove('btn-success');
                        ativarCameraBtn.classList.add('btn-outline-danger');
                        
                        // Mostrar mensagem de erro
                        statusContainer.style.display = 'block';
                        statusMessage.textContent = 'Erro de comunicação com o servidor.';
                        statusMessage.classList.remove('text-success');
                        statusMessage.classList.add('text-danger');
                        
                        // Permitir nova tentativa após 2 segundos
                        setTimeout(resetCamera, 2000);
                    });
                }
            }, 500);
        }
        
        function resetCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            video.srcObject = null;
            video.style.display = 'none';
            canvas.style.display = 'none';
            cameraPlaceholder.style.display = 'block';
            ativarCameraBtn.textContent = 'ATIVAR CÂMERA';
            ativarCameraBtn.classList.remove('btn-outline-danger', 'btn-outline-success');
            ativarCameraBtn.classList.add('btn-success');
            ativarCameraBtn.disabled = false;
            statusContainer.style.display = 'none';
        }
        
        // Verificar status atual do usuário ao carregar a página
        fetch('/api/attendance/status')
            .then(response => response.json())
            .then(data => {
                if (data.last_record) {
                    const date = new Date(data.last_record.created_at);
                    lastDate.textContent = date.toLocaleDateString('pt-BR');
                    lastType.textContent = data.last_record.type;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar status:', error);
            });
        
        // Limpar stream quando a página for fechada
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    });
</script>
@endsection