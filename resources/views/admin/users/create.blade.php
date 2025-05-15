@extends('layouts.app')

@section('title', 'Criar Novo Usuário')

@section('styles')
<style>
    .nav-pills .nav-link.active {
        background-color: transparent;
        color: #003366;
        border-bottom: 3px solid #003366;
        border-radius: 0;
    }
    .nav-pills .nav-link {
        color: #6c757d;
    }
    .camera-container {
        width: 150px;
        height: 150px;
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
        margin-top: 50px;
        opacity: 0.5;
    }
    .form-label {
        font-weight: 500;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('users.index') }}">Alunos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports') }}">Relatórios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.config') }}">Configurações</a>
            </li>
        </ul>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <h4 class="text-center mb-3">Cadastrar Novo Usuário</h4>
                <p class="text-center text-muted small mb-4">Preencha os campos abaixo para criar um novo usuário no sistema</p>

                <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Dados Pessoais</h5>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <input type="text" class="form-control @error('matricula') is-invalid @enderror" id="matricula" name="matricula" value="{{ old('matricula') }}" required>
                                @error('matricula')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail Institucional</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <select class="form-select @error('curso') is-invalid @enderror" id="curso" name="curso" required>
                                    <option value="">Selecione um curso</option>
                                    <option value="Sistemas de Informação">Sistemas de Informação</option>
                                    <option value="Ciência da Computação">Ciência da Computação</option>
                                    <option value="Engenharia de Software">Engenharia de Software</option>
                                </select>
                                @error('curso')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Tipo de Usuário</label>
                                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                    <option value="aluno">Aluno</option>
                                    <option value="admin">Administrador</option>
                                </select>
                                @error('role')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password-confirm" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="password-confirm" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Registro Facial</h5>
                                <p class="text-muted small">Posicione o rosto do usuário para o cadastro biométrico</p>
                            </div>

                            <div class="camera-container mb-3" id="video-container">
                                <video id="video" width="150" height="150" autoplay muted style="display: none;"></video>
                                <canvas id="canvas" width="150" height="150" style="display: none;"></canvas>
                                <img src="{{ asset('images/camera-icon.png') }}" class="camera-icon" id="camera-placeholder">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" id="ativar-camera">ATIVAR CÂMERA</button>
                                <p class="text-muted small text-center mt-2 mb-4">Posicione com a cabeça no centro da imagem e clique!</p>
                            </div>

                            <input type="hidden" name="face_data" id="face_data">
                            
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Ou faça upload de uma foto</label>
                                <input type="file" class="form-control @error('profile_image') is-invalid @enderror" id="profile_image" name="profile_image">
                                @error('profile_image')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <a href="{{ route('users.index') }}" class="btn btn-light">CANCELAR</a>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn btn-primary">SALVAR USUÁRIO</button>
                        </div>
                    </div>
                </form>
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
        const faceDataInput = document.getElementById('face_data');
        
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
                ativarCameraBtn.textContent = 'CAPTURAR IMAGEM';
                ativarCameraBtn.removeEventListener('click', arguments.callee);
                ativarCameraBtn.addEventListener('click', captureImage);
            } catch (err) {
                console.error('Erro ao acessar câmera:', err);
                alert('Não foi possível acessar a câmera. Verifique as permissões do navegador.');
            }
        });
        
        // Capturar imagem
        async function captureImage() {
            if (!video.srcObject) return;
            
            // Desenhar frame do vídeo no canvas
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Detectar face
            const detections = await faceapi.detectSingleFace(
                video, 
                new faceapi.TinyFaceDetectorOptions()
            ).withFaceLandmarks().withFaceDescriptor();
            
            if (detections) {
                faceDataInput.value = JSON.stringify(Array.from(detections.descriptor));
                
                // Mostrar a imagem capturada
                video.style.display = 'none';
                canvas.style.display = 'block';
                
                // Mudar o botão
                ativarCameraBtn.textContent = 'REFAZER CAPTURA';
                ativarCameraBtn.classList.remove('btn-success');
                ativarCameraBtn.classList.add('btn-outline-secondary');
            } else {
                alert('Nenhuma face detectada. Por favor, posicione o rosto corretamente e tente novamente.');
            }
        }
        
        // Limpar stream quando a página for fechada
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    });
</script>
@endsection