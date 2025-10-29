# API de Verificação Facial + Registro de Ponto - Guia de Uso

## Endpoint

**POST** `/attendance/verify`

**Rota Laravel:** `route('attendance.verify')`

## Descrição

Este endpoint realiza reconhecimento facial e **automaticamente registra o ponto** se houver match. Compara a imagem recebida com **todos** os embeddings cadastrados no banco MySQL.

## Requisitos

1. ✅ API Flask rodando em `http://localhost:5001`
2. ✅ Pelo menos 1 usuário com embedding cadastrado na tabela `recognition_records`
3. ✅ Imagem com rosto frontal visível

## Request

### Headers
```
Content-Type: application/json
X-CSRF-TOKEN: {{ csrf_token() }} (se não for API pública)
```

### Body (JSON)
```json
{
  "image_data": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD..."
}
```

### Campos

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `image_data` | string | ✅ Sim | Imagem em base64 (aceita `data:image/...` ou base64 cru) |

## Response

### ✅ Sucesso - Match Encontrado (200 OK)
```json
{
  "ok": true,
  "recognized_user_id": 123,
  "distance": 0.35,
  "attendance_id": 456,
  "user_name": "João Silva"
}
```

**O que aconteceu:**
- ✅ Rosto foi reconhecido (distância < 0.4)
- ✅ Ponto foi registrado automaticamente na tabela `attendance_records`
- ✅ Timestamp: `now()`
- ✅ Tipo: `facial_auto`

---

### ❌ Erro - Nenhum Match (401 Unauthorized)
```json
{
  "ok": false,
  "reason": "no_match",
  "best_distance": 0.52
}
```

**Motivo:** Nenhum rosto cadastrado teve distância < 0.4 (threshold).

---

### ❌ Erro - Flask Offline (500 Internal Server Error)
```json
{
  "ok": false,
  "error": "flask_unreachable",
  "details": "Não foi possível conectar ao serviço de reconhecimento facial"
}
```

**Motivo:** API Flask não está rodando ou não responde.

---

### ❌ Erro - Nenhum Embedding Cadastrado (400 Bad Request)
```json
{
  "ok": false,
  "error": "Nenhum usuário com cadastro facial encontrado no sistema"
}
```

**Motivo:** Tabela `recognition_records` está vazia ou sem embeddings válidos.

---

## Exemplo de Uso (JavaScript/Fetch)

### Captura de Vídeo + Reconhecimento Contínuo

```html
<!DOCTYPE html>
<html>
<head>
  <title>Reconhecimento Facial - Ponto</title>
</head>
<body>
  <h1>Registrar Ponto por Reconhecimento Facial</h1>

  <video id="webcam" width="640" height="480" autoplay></video>
  <canvas id="canvas" style="display:none;"></canvas>

  <div id="status">Aguardando...</div>

  <script>
    const video = document.getElementById('webcam');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const statusDiv = document.getElementById('status');

    // Iniciar webcam
    navigator.mediaDevices.getUserMedia({ video: true })
      .then(stream => {
        video.srcObject = stream;
        video.play();

        // Tentar reconhecer a cada 2 segundos
        setInterval(tryRecognize, 2000);
      })
      .catch(err => {
        console.error('Erro ao acessar webcam:', err);
        statusDiv.textContent = '❌ Erro ao acessar câmera';
      });

    async function tryRecognize() {
      // Captura frame atual
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0);

      const imageData = canvas.toDataURL('image/jpeg', 0.95);

      statusDiv.textContent = '🔍 Buscando rosto...';

      try {
        const response = await fetch('/attendance/verify', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify({ image_data: imageData })
        });

        const result = await response.json();

        if (result.ok) {
          // ✅ RECONHECIDO E PONTO REGISTRADO!
          statusDiv.innerHTML = `
            ✅ <strong>Bem-vindo, ${result.user_name}!</strong><br>
            Ponto registrado com sucesso.<br>
            ID do Registro: ${result.attendance_id}<br>
            Distância: ${result.distance.toFixed(4)}
          `;
          statusDiv.style.color = 'green';

          // Parar reconhecimento após sucesso (opcional)
          video.srcObject.getTracks().forEach(track => track.stop());

        } else if (result.reason === 'no_match') {
          // ❌ Rosto não reconhecido
          statusDiv.textContent = `❌ Rosto não reconhecido (dist: ${result.best_distance.toFixed(4)})`;
          statusDiv.style.color = 'red';

        } else {
          // ❌ Outro erro
          statusDiv.textContent = `❌ Erro: ${result.error || 'Desconhecido'}`;
          statusDiv.style.color = 'red';
        }

      } catch (error) {
        console.error('Erro na requisição:', error);
        statusDiv.textContent = '❌ Erro de conexão';
        statusDiv.style.color = 'red';
      }
    }
  </script>
</body>
</html>
```

---

## Exemplo de Uso (Blade + JavaScript)

```blade
@extends('layouts.app')

@section('content')
<div class="container">
  <h2>Registrar Ponto - Reconhecimento Facial</h2>

  <div class="row">
    <div class="col-md-6">
      <video id="webcam" width="100%" autoplay></video>
    </div>
    <div class="col-md-6">
      <div id="status" class="alert alert-info">
        Posicione seu rosto na câmera...
      </div>
    </div>
  </div>
</div>

<canvas id="canvas" style="display:none;"></canvas>

<script>
const video = document.getElementById('webcam');
const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');
const statusDiv = document.getElementById('status');

// Iniciar webcam
navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => {
    video.srcObject = stream;

    // Tentar reconhecer a cada 3 segundos
    setInterval(verifyFace, 3000);
  });

async function verifyFace() {
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  ctx.drawImage(video, 0, 0);

  const imageData = canvas.toDataURL('image/jpeg', 0.95);

  try {
    const response = await fetch('{{ route("attendance.verify") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ image_data: imageData })
    });

    const result = await response.json();

    if (result.ok) {
      statusDiv.className = 'alert alert-success';
      statusDiv.innerHTML = `
        <h4>✅ Ponto Registrado!</h4>
        <p><strong>${result.user_name}</strong></p>
        <p>ID do Registro: ${result.attendance_id}</p>
        <p>Distância: ${result.distance.toFixed(4)}</p>
      `;

      // Redirecionar após 3 segundos
      setTimeout(() => {
        window.location.href = '{{ route("dashboard") }}';
      }, 3000);

    } else {
      statusDiv.className = 'alert alert-warning';
      statusDiv.textContent = '❌ Rosto não reconhecido. Tente novamente.';
    }

  } catch (error) {
    statusDiv.className = 'alert alert-danger';
    statusDiv.textContent = '❌ Erro de conexão';
  }
}
</script>
@endsection
```

---

## Fluxo Interno do Endpoint

```
Frontend (POST /attendance/verify)
  ↓ { image_data }
Laravel Controller
  ↓ Busca TODOS os embeddings de recognition_records
  ↓ Monta array known_embeddings = [{ user_id, embedding }, ...]
  ↓ HTTP POST → Flask API (localhost:5001/recognize_face)
Flask API
  ↓ Extrai embedding da imagem atual (Facenet512)
  ↓ Calcula distância euclidiana com cada embedding conhecido
  ↓ Retorna match se dist < 0.4 (threshold)
Laravel Controller
  ↓ Se success=true:
      ↓ Cria registro em attendance_records:
          - user_id = recognized_user_id
          - entry_time = now()
          - punch_type = 'facial_auto'
      ↓ { ok: true, recognized_user_id, distance, attendance_id }
  ↓ Se success=false:
      ↓ { ok: false, reason: 'no_match', best_distance }
Frontend
```

---

## Schema MySQL Usado

### Tabela: `recognition_records`
```
id | user_id | face_descriptor (longText) | capture_type | created_at | updated_at
```

**Exemplo de `face_descriptor`:**
```json
[0.123, -0.456, 0.789, ..., -0.321]  // 512 floats (Facenet512)
```

### Tabela: `attendance_records`
```
id | user_id | entry_time | exit_time | status | punch_type | created_at | updated_at
```

**Registro criado:**
```
user_id: 123
entry_time: 2025-10-28 14:30:15
status: present
punch_type: facial_auto
```

---

## Verificar se Funcionou

### Via MySQL/Tinker:
```php
// Ver embeddings cadastrados
RecognitionRecord::with('user')->get();

// Ver pontos registrados hoje
AttendanceRecord::whereDate('entry_time', today())
  ->where('punch_type', 'facial_auto')
  ->with('user')
  ->get();
```

### Via Log:
```bash
tail -f storage/logs/laravel.log
```

Procure por:
```
Iniciando verificação facial (reconhecimento + registro de ponto)
Buscando embeddings cadastrados no banco MySQL...
Total de embeddings conhecidos: 5
Chamando Flask API /recognize_face...
Match encontrado: user_id=123, distance=0.35
Registrando ponto automático para user_id=123
Ponto registrado com sucesso
```

---

## Troubleshooting

### ❌ Erro: "flask_unreachable"
**Causa:** API Flask não está rodando ou não responde.

**Solução:**
```bash
cd deepface-api
source venv/bin/activate
python app.py
```

Verifique se aparece:
```
* Running on http://0.0.0.0:5001
```

---

### ❌ Erro: "Nenhum usuário com cadastro facial encontrado"
**Causa:** Tabela `recognition_records` está vazia.

**Solução:** Cadastre pelo menos 1 usuário usando:
```
POST /admin/facial/enrol
{
  "user_id": 123,
  "image_data": "data:image/jpeg;base64,..."
}
```

---

### ❌ Erro: "no_match" (best_distance muito alta)
**Causa:**
- Rosto na câmera não corresponde a nenhum cadastrado
- Iluminação diferente do cadastro
- Ângulo diferente do cadastro

**Solução:**
- Verificar se o usuário realmente tem embedding cadastrado
- Tentar com melhor iluminação
- Re-cadastrar o usuário com nova foto

---

### ❌ Erro: "Usuário reconhecido não encontrado no sistema"
**Causa:** O `user_id` em `recognition_records` aponta para um usuário deletado.

**Solução:** Limpar registros órfãos:
```php
RecognitionRecord::whereDoesntHave('user')->delete();
```

---

## Performance

- **Tempo médio de resposta:** 1-3 segundos (depende do número de embeddings)
- **Limite recomendado:** Até 1000 usuários cadastrados
- **Otimização futura:** Implementar busca vetorial (pgvector, Milvus, etc.) para > 1000 usuários

---

## Segurança

⚠️ **Este endpoint NÃO requer autenticação** (permite registro de ponto público via totem).

Se quiser restringir acesso:
```php
Route::post('/attendance/verify', [AttendanceVerificationController::class, 'verify'])
  ->middleware(['auth']);
```

---

## Diferença entre os Endpoints

| Endpoint | Função | Requer Auth | Quem Usa |
|----------|--------|-------------|----------|
| `/admin/facial/enrol` | Cadastrar embedding | ✅ Sim | Admin ao cadastrar usuário |
| `/attendance/verify` | Reconhecer + registrar ponto | ❌ Não* | Totem de ponto público |

*Pode ser protegido se necessário

---

## Controller

**Arquivo:** `app/Http/Controllers/AttendanceVerificationController.php`

**Método:** `verify(Request $request)`

**Rota:** `routes/web.php` → `Route::post('/attendance/verify', ...)`
