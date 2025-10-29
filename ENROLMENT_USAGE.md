# API de Cadastro Facial (Enrolment) - Guia de Uso

## Endpoint

**POST** `/admin/facial/enrol`

**Rota Laravel:** `route('admin.facial.enrol')`

## Descrição

Este endpoint permite cadastrar ou atualizar o embedding facial de um usuário usando a nova API Flask stateless (Facenet512).

## Requisitos

1. ✅ Usuário autenticado (middleware `auth`)
2. ✅ API Flask rodando em `http://localhost:5001`
3. ✅ Usuário com `user_id` válido no banco MySQL

## Request

### Headers
```
Content-Type: application/json
X-CSRF-TOKEN: {{ csrf_token() }}
```

### Body (JSON)
```json
{
  "user_id": 123,
  "image_data": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD..."
}
```

### Campos

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `user_id` | integer | ✅ Sim | ID do usuário no banco (tabela `users`) |
| `image_data` | string | ✅ Sim | Imagem em base64 (aceita `data:image/...` ou base64 cru) |

## Response

### Sucesso (200 OK)
```json
{
  "ok": true,
  "message": "Embedding cadastrado/atualizado com sucesso",
  "user_id": 123
}
```

### Erro - Validação (400 Bad Request)
```json
{
  "ok": false,
  "error": "Nenhum rosto detectado na imagem"
}
```

### Erro - Usuário não encontrado (404 Not Found)
```json
{
  "ok": false,
  "error": "Usuário não encontrado"
}
```

### Erro - API Flask offline (503 Service Unavailable)
```json
{
  "ok": false,
  "error": "Não foi possível conectar ao serviço de reconhecimento facial. Verifique se a API Flask está rodando."
}
```

### Erro - Interno (500 Internal Server Error)
```json
{
  "ok": false,
  "error": "Erro interno ao processar cadastro facial: ..."
}
```

## Exemplo de Uso (JavaScript/Fetch)

```javascript
// Capturar imagem da webcam (exemplo usando canvas)
const canvas = document.getElementById('faceCanvas');
const imageData = canvas.toDataURL('image/jpeg', 0.95);

// Enviar para API
fetch('/admin/facial/enrol', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    user_id: 123,
    image_data: imageData
  })
})
.then(response => response.json())
.then(data => {
  if (data.ok) {
    console.log('✅ Cadastro facial realizado com sucesso!');
    console.log('User ID:', data.user_id);
  } else {
    console.error('❌ Erro:', data.error);
  }
})
.catch(error => {
  console.error('❌ Erro de rede:', error);
});
```

## Exemplo de Uso (Blade/Form)

```html
<form id="enrolmentForm">
  @csrf
  <input type="hidden" name="user_id" value="{{ $user->id }}">

  <video id="webcam" autoplay></video>
  <canvas id="faceCanvas" style="display:none;"></canvas>

  <button type="button" onclick="captureFace()">Capturar Face</button>
</form>

<script>
async function captureFace() {
  const video = document.getElementById('webcam');
  const canvas = document.getElementById('faceCanvas');
  const ctx = canvas.getContext('2d');

  // Captura frame da webcam
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  ctx.drawImage(video, 0, 0);

  // Converte para base64
  const imageData = canvas.toDataURL('image/jpeg', 0.95);

  // Envia para backend
  const formData = new FormData(document.getElementById('enrolmentForm'));
  formData.append('image_data', imageData);

  try {
    const response = await fetch('{{ route("admin.facial.enrol") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({
        user_id: formData.get('user_id'),
        image_data: imageData
      }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      }
    });

    const result = await response.json();

    if (result.ok) {
      alert('✅ Cadastro facial realizado com sucesso!');
    } else {
      alert('❌ Erro: ' + result.error);
    }
  } catch (error) {
    alert('❌ Erro de conexão: ' + error.message);
  }
}
</script>
```

## O que acontece internamente?

1. **Validação** → Verifica se `user_id` e `image_data` foram enviados
2. **Chamada Flask** → POST `http://localhost:5001/extract_embedding`
3. **Extração** → Flask retorna embedding de 512 dimensões (Facenet512)
4. **Persistência** → Salva/atualiza na tabela `recognition_records`:
   - Campo: `face_descriptor` (JSON com array de 512 floats)
   - Relacionamento: `user_id` → `users.id`
5. **Response** → Retorna JSON com status de sucesso/erro

## Importante

- ✅ **NÃO salva imagem** em disco (apenas embedding)
- ✅ **Stateless**: API Flask não persiste nada
- ✅ **Atualização**: Se o usuário já tem embedding cadastrado, **substitui** pelo novo
- ✅ **Um embedding por usuário**: Cada usuário tem apenas 1 registro ativo em `recognition_records`
- ⚠️ **API Flask deve estar rodando**: Certifique-se que `http://localhost:5001` está acessível

## Verificar se funcionou

### Via MySQL/Tinker:
```php
$user = User::find(123);
$record = $user->recognitionRecords()->first();
dd($record->face_descriptor); // Array com 512 floats
```

### Via Log:
```bash
tail -f storage/logs/laravel.log
```

Procure por:
```
Iniciando cadastro facial para user_id=123
Chamando Flask API /extract_embedding para user_id=123
Embedding extraído com sucesso
Embedding atualizado para user_id=123
```

## Troubleshooting

### Erro: "Não foi possível conectar ao serviço..."
➡️ Certifique-se que a API Flask está rodando:
```bash
cd deepface-api
source venv/bin/activate
python app.py
```

### Erro: "Nenhum rosto detectado na imagem"
➡️ A imagem pode estar muito escura, desfocada, ou não contém rosto frontal visível.

### Erro: "Usuário não encontrado"
➡️ O `user_id` enviado não existe na tabela `users`.

## Controller

**Arquivo:** `app/Http/Controllers/Admin/FaceEnrolmentController.php`

**Método:** `store(Request $request)`

**Rota:** `routes/web.php` → `Route::post('/admin/facial/enrol', ...)`
