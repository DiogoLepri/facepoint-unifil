# 🔐 Sistema de Reconhecimento Facial - Guia Completo

## 📋 Visão Geral

Sistema completo de reconhecimento facial usando:
- **Backend ML:** Flask API stateless com DeepFace/Facenet512 (512 dimensões)
- **Backend Web:** Laravel com MySQL
- **Arquitetura:** Microserviços (Laravel ↔ Flask)

---

## 🏗️ Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                         FRONTEND                             │
│            (JavaScript + Webcam API + Canvas)                │
└──────────────────┬──────────────────┬───────────────────────┘
                   │                  │
                   │ POST /admin/     │ POST /attendance/
                   │ facial/enrol     │ verify
                   ↓                  ↓
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL (Backend Web)                     │
│  ┌──────────────────────┐    ┌─────────────────────────┐   │
│  │ FaceEnrolmentController│    │AttendanceVerificationCtrl│   │
│  └──────────┬───────────┘    └───────────┬─────────────┘   │
│             │                            │                  │
│             │  ┌─────────────────────┐   │                  │
│             └─→│   MySQL Database    │←──┘                  │
│                │  - users            │                      │
│                │  - recognition_records (embeddings)        │
│                │  - attendance_records (pontos)             │
│                └──────────┬──────────┘                      │
└───────────────────────────┼─────────────────────────────────┘
                            │
                            │ HTTP POST
                            │ /extract_embedding
                            │ /recognize_face
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              FLASK API (Microserviço ML)                    │
│  - Stateless (não persiste nada)                            │
│  - DeepFace + Facenet512                                    │
│  - OpenCV (processamento de imagem)                         │
│  - Numpy (cálculos vetoriais)                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Componentes do Sistema

### 1. Flask API (Microserviço ML)

**Arquivo:** `deepface-api/app.py`

**Endpoints:**

| Endpoint | Método | Função |
|----------|--------|--------|
| `/health` | GET | Health check |
| `/extract_embedding` | POST | Extrai embedding de uma imagem |
| `/recognize_face` | POST | Reconhece rosto comparando embeddings |

**Como subir:**
```bash
cd deepface-api
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
pip install -r requirements.txt
python app.py
# Roda em http://0.0.0.0:5001
```

---

### 2. Laravel Controllers

#### 2.1 FaceEnrolmentController (Cadastro Facial)

**Arquivo:** `app/Http/Controllers/Admin/FaceEnrolmentController.php`

**Rota:** `POST /admin/facial/enrol`

**Função:** Cadastra ou atualiza o embedding facial de um usuário

**Request:**
```json
{
  "user_id": 123,
  "image_data": "data:image/jpeg;base64,..."
}
```

**Response (sucesso):**
```json
{
  "ok": true,
  "message": "Embedding cadastrado/atualizado com sucesso",
  "user_id": 123
}
```

**Fluxo:**
1. Valida `user_id` e `image_data`
2. Chama Flask: `POST /extract_embedding`
3. Recebe embedding (512 floats)
4. Salva/atualiza em `recognition_records.face_descriptor`
5. Retorna sucesso

---

#### 2.2 AttendanceVerificationController (Reconhecimento + Ponto)

**Arquivo:** `app/Http/Controllers/AttendanceVerificationController.php`

**Rota:** `POST /attendance/verify`

**Função:** Reconhece rosto e registra ponto automaticamente

**Request:**
```json
{
  "image_data": "data:image/jpeg;base64,..."
}
```

**Response (match encontrado):**
```json
{
  "ok": true,
  "recognized_user_id": 123,
  "distance": 0.35,
  "attendance_id": 456,
  "user_name": "João Silva"
}
```

**Response (sem match):**
```json
{
  "ok": false,
  "reason": "no_match",
  "best_distance": 0.52
}
```

**Fluxo:**
1. Valida `image_data`
2. Busca TODOS embeddings cadastrados em `recognition_records`
3. Monta array `known_embeddings`
4. Chama Flask: `POST /recognize_face`
5. Se match encontrado (dist < 0.4):
   - Cria registro em `attendance_records`
   - Retorna `{ ok: true, recognized_user_id, ... }`
6. Se nenhum match:
   - Retorna `{ ok: false, reason: "no_match", ... }`

---

## 🗄️ Schema MySQL

### Tabela: `users`
```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255),
  matricula VARCHAR(20) UNIQUE,
  curso VARCHAR(255),
  role VARCHAR(255) DEFAULT 'aluno',
  schedule JSON,
  last_login_type VARCHAR(255),
  last_login_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP  -- soft delete
);
```

---

### Tabela: `recognition_records`
```sql
CREATE TABLE recognition_records (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  face_descriptor LONGTEXT NOT NULL,  -- JSON array de 512 floats
  capture_type VARCHAR(255) NOT NULL, -- 'enrolment'
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Exemplo de `face_descriptor`:**
```json
[0.123, -0.456, 0.789, 0.012, ..., -0.321]
```
↑ Array de 512 floats (Facenet512)

**Importante:**
- Cada usuário tem **1 registro** em `recognition_records`
- Se cadastrar novamente, **atualiza** o registro existente
- O cast `'face_descriptor' => 'array'` no Model converte JSON ↔ Array automaticamente

---

### Tabela: `attendance_records`
```sql
CREATE TABLE attendance_records (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  entry_time TIMESTAMP NOT NULL,
  exit_time TIMESTAMP,
  status VARCHAR(255),           -- 'present', 'absent', etc
  punch_type VARCHAR(255),       -- 'facial_auto', 'manual', etc
  is_early BOOLEAN,
  is_late BOOLEAN,
  expected_time TIME,
  minutes_difference INT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Registro criado pelo reconhecimento facial:**
```
user_id: 123
entry_time: 2025-10-28 14:30:15
status: present
punch_type: facial_auto
```

---

## 🔄 Fluxos Completos

### Fluxo 1: Cadastro Facial (Enrolment)

```
┌──────────┐
│ Frontend │
└─────┬────┘
      │ Captura foto da webcam (canvas.toDataURL)
      │ POST /admin/facial/enrol
      │ { user_id: 123, image_data: "data:image/jpeg;base64,..." }
      ↓
┌─────────────────────────┐
│ FaceEnrolmentController │
└─────┬───────────────────┘
      │ Valida user_id (exists:users,id)
      │ Valida image_data (required|string)
      ↓
      │ HTTP POST → Flask (http://localhost:5001/extract_embedding)
      │ { image_data: "..." }
      ↓
┌──────────┐
│ Flask API│
└─────┬────┘
      │ decode_base64_image() → numpy array
      │ DeepFace.represent(img, model='Facenet512')
      │ embedding = [512 floats]
      │ { success: true, embedding: [...], dimensions: 512 }
      ↓
┌─────────────────────────┐
│ FaceEnrolmentController │
└─────┬───────────────────┘
      │ RecognitionRecord::where('user_id', 123)->first()
      │ Se existe: UPDATE face_descriptor
      │ Se não: INSERT novo registro
      │
      │ recognition_records:
      │   user_id = 123
      │   face_descriptor = json_encode([...])  // 512 floats
      │   capture_type = 'enrolment'
      │
      │ { ok: true, message: "...", user_id: 123 }
      ↓
┌──────────┐
│ Frontend │
└──────────┘
      Mostra mensagem de sucesso
```

---

### Fluxo 2: Reconhecimento Facial + Registro de Ponto

```
┌──────────┐
│ Frontend │
└─────┬────┘
      │ Loop: captura frame da webcam a cada 2s
      │ POST /attendance/verify
      │ { image_data: "data:image/jpeg;base64,..." }
      ↓
┌─────────────────────────────────┐
│ AttendanceVerificationController│
└─────┬───────────────────────────┘
      │ Valida image_data (required|string)
      │
      │ ETAPA 1: Montar known_embeddings
      │ ────────────────────────────────
      │ RecognitionRecord::whereNotNull('face_descriptor')->get()
      │ Para cada registro:
      │   known_embeddings[] = {
      │     user_id: record.user_id,
      │     embedding: json_decode(record.face_descriptor)
      │   }
      │
      │ Resultado: [
      │   { user_id: 1, embedding: [...] },
      │   { user_id: 5, embedding: [...] },
      │   { user_id: 12, embedding: [...] }
      │ ]
      ↓
      │ ETAPA 2: Chamar Flask
      │ ─────────────────────
      │ HTTP POST → Flask (http://localhost:5001/recognize_face)
      │ {
      │   image_data: "...",
      │   known_embeddings: [...]
      │ }
      ↓
┌──────────┐
│ Flask API│
└─────┬────┘
      │ decode_base64_image() → numpy array
      │ DeepFace.represent(img, model='Facenet512')
      │ current_embedding = [512 floats]
      │
      │ Para cada known_embedding:
      │   distance = euclidean_distance(current, known)
      │   Se distance < 0.4: MATCH!
      │
      │ Se encontrou match:
      │   { success: true, user_id: 5, distance: 0.35 }
      │ Se não encontrou:
      │   { success: false, reason: "no_match", best_distance: 0.52 }
      ↓
┌─────────────────────────────────┐
│ AttendanceVerificationController│
└─────┬───────────────────────────┘
      │ ETAPA 3: Interpretar resposta
      │ ─────────────────────────────
      │ Se success = true:
      │   recognized_user_id = response.user_id
      │
      │   ETAPA 4: Registrar ponto
      │   ─────────────────────────
      │   AttendanceRecord::create([
      │     user_id: recognized_user_id,
      │     entry_time: now(),
      │     status: 'present',
      │     punch_type: 'facial_auto'
      │   ])
      │
      │   { ok: true, recognized_user_id, distance, attendance_id, user_name }
      │
      │ Se success = false:
      │   { ok: false, reason: "no_match", best_distance }
      ↓
┌──────────┐
│ Frontend │
└──────────┘
      Se ok = true:
        ✅ Mostra: "Bem-vindo, João Silva! Ponto registrado."
      Se ok = false:
        ❌ Mostra: "Rosto não reconhecido"
```

---

## 🎯 Casos de Uso

### Caso 1: Admin Cadastra Novo Usuário

1. Admin acessa painel administrativo
2. Cria novo usuário no formulário
3. Após salvar, abre tela de cadastro facial
4. Usuário posiciona rosto na câmera
5. Admin clica "Capturar Face"
6. Sistema chama `POST /admin/facial/enrol`
7. Embedding é salvo em `recognition_records`
8. Usuário agora pode usar reconhecimento facial

---

### Caso 2: Aluno Registra Ponto no Totem

1. Totem exibe tela com câmera ativa
2. Loop automático captura frames a cada 2s
3. Para cada frame: `POST /attendance/verify`
4. Se rosto for reconhecido:
   - Ponto é registrado automaticamente
   - Tela mostra: "✅ Bem-vindo, João! Ponto registrado."
   - Totem volta para tela inicial após 3s
5. Se rosto não for reconhecido:
   - Tela mostra: "❌ Rosto não reconhecido"
   - Continua tentando

---

### Caso 3: Usuário Atualiza Foto Facial

1. Admin acessa edição de usuário
2. Clica "Atualizar Foto Facial"
3. Usuário captura nova foto
4. Sistema chama `POST /admin/facial/enrol` com mesmo `user_id`
5. Registro em `recognition_records` é **atualizado** (não criado novo)
6. Embedding antigo é substituído pelo novo

---

## ⚙️ Configurações Importantes

### Threshold de Reconhecimento

**Arquivo:** `deepface-api/app.py`
```python
THRESHOLD = 0.4  # Distância euclidiana máxima para match
```

**Como funciona:**
- `distance < 0.4` → ✅ Match (mesma pessoa)
- `distance >= 0.4` → ❌ No match (pessoas diferentes)

**Ajustes:**
- `THRESHOLD = 0.3` → Mais restritivo (menos falsos positivos, mais falsos negativos)
- `THRESHOLD = 0.5` → Mais permissivo (mais falsos positivos, menos falsos negativos)

---

### Modelo de Reconhecimento

**Arquivo:** `deepface-api/app.py`
```python
MODEL_NAME = 'Facenet512'  # 512 dimensões
```

**Modelos disponíveis no DeepFace:**
- `Facenet512` ✅ (recomendado - melhor acurácia, 512D)
- `Facenet` (128 dimensões - mais rápido, menos preciso)
- `VGG-Face` (2622 dimensões - muito lento)
- `ArcFace` (512 dimensões - similar ao Facenet512)

---

## 🧪 Como Testar

### 1. Testar Flask API isoladamente

```bash
# Terminal 1: Subir Flask
cd deepface-api
source venv/bin/activate
python app.py

# Terminal 2: Testar health check
curl http://localhost:5001/health

# Terminal 3: Testar extração de embedding
curl -X POST http://localhost:5001/extract_embedding \
  -H "Content-Type: application/json" \
  -d '{
    "image_data": "data:image/jpeg;base64,/9j/4AAQ..."
  }'
```

---

### 2. Testar Cadastro Facial (Laravel)

```bash
# Subir Laravel
php artisan serve

# Em outro terminal, testar endpoint
curl -X POST http://localhost:8000/admin/facial/enrol \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: seu-token-aqui" \
  -d '{
    "user_id": 1,
    "image_data": "data:image/jpeg;base64,..."
  }'
```

---

### 3. Testar Reconhecimento (Laravel)

```bash
curl -X POST http://localhost:8000/attendance/verify \
  -H "Content-Type: application/json" \
  -d '{
    "image_data": "data:image/jpeg;base64,..."
  }'
```

---

### 4. Verificar Banco de Dados

```bash
php artisan tinker
```

```php
// Ver usuários com embedding cadastrado
RecognitionRecord::with('user')->get();

// Ver último ponto registrado
AttendanceRecord::latest()->with('user')->first();

// Ver todos os pontos de hoje
AttendanceRecord::whereDate('entry_time', today())
  ->with('user')
  ->get();

// Ver embedding de um usuário específico
$user = User::find(1);
$record = $user->recognitionRecords()->first();
dump($record->face_descriptor); // Array com 512 floats
```

---

## 📊 Monitoramento e Logs

### Logs do Laravel

```bash
tail -f storage/logs/laravel.log
```

**O que procurar:**
```
[2025-10-28 14:30:15] Iniciando cadastro facial para user_id=123
[2025-10-28 14:30:16] Chamando Flask API /extract_embedding para user_id=123
[2025-10-28 14:30:18] Embedding extraído com sucesso
[2025-10-28 14:30:18] Embedding atualizado para user_id=123

[2025-10-28 14:35:20] Iniciando verificação facial (reconhecimento + registro de ponto)
[2025-10-28 14:35:20] Buscando embeddings cadastrados no banco MySQL...
[2025-10-28 14:35:20] Total de embeddings conhecidos: 5
[2025-10-28 14:35:21] Chamando Flask API /recognize_face...
[2025-10-28 14:35:23] Match encontrado: user_id=123, distance=0.35
[2025-10-28 14:35:23] Registrando ponto automático para user_id=123
[2025-10-28 14:35:23] Ponto registrado com sucesso
```

---

### Logs do Flask

```bash
# Flask imprime no stdout
python app.py
```

**O que procurar:**
```
2025-10-28 14:30:16 - INFO - Extraindo embedding facial...
2025-10-28 14:30:18 - INFO - Embedding extraído: 512 dimensões

2025-10-28 14:35:21 - INFO - Extraindo embedding da imagem para reconhecimento...
2025-10-28 14:35:22 - INFO - Comparando com 5 embeddings conhecidos...
2025-10-28 14:35:23 - INFO - Match encontrado: user_id=123, distance=0.3521
```

---

## 🚨 Troubleshooting

### Problema 1: Flask não responde

**Sintoma:**
```json
{
  "ok": false,
  "error": "flask_unreachable"
}
```

**Causa:** Flask não está rodando ou porta 5001 bloqueada

**Solução:**
```bash
cd deepface-api
source venv/bin/activate
python app.py

# Deve aparecer:
# * Running on http://0.0.0.0:5001
```

---

### Problema 2: Nenhum rosto detectado

**Sintoma:**
```json
{
  "success": false,
  "error": "Nenhum rosto detectado na imagem"
}
```

**Causas possíveis:**
- Imagem muito escura
- Rosto de perfil (precisa ser frontal)
- Rosto muito pequeno na imagem
- Imagem desfocada

**Solução:**
- Melhorar iluminação
- Posicionar rosto frontal para câmera
- Aproximar mais da câmera
- Usar câmera com melhor qualidade

---

### Problema 3: Sempre retorna "no_match"

**Sintoma:**
```json
{
  "ok": false,
  "reason": "no_match",
  "best_distance": 0.89
}
```

**Causas possíveis:**
- Usuário não tem embedding cadastrado
- Embedding foi cadastrado com foto muito diferente (ângulo, iluminação)
- Threshold muito restritivo (0.4)

**Solução:**
```bash
# Verificar se usuário tem embedding
php artisan tinker
>>> RecognitionRecord::where('user_id', 123)->first();

# Se não tiver, cadastrar:
# POST /admin/facial/enrol com user_id=123

# Se tiver, tentar aumentar threshold temporariamente:
# deepface-api/app.py: THRESHOLD = 0.5
```

---

### Problema 4: Falsos positivos (reconhece pessoa errada)

**Sintoma:** Sistema reconhece usuário A como usuário B

**Causa:** Threshold muito alto (> 0.4) ou rostos muito similares

**Solução:**
```python
# deepface-api/app.py
THRESHOLD = 0.3  # Mais restritivo
```

---

### Problema 5: Banco não atualiza embedding

**Sintoma:** Cadastro retorna sucesso mas embedding não muda no banco

**Causa:** Cast do Model não está funcionando

**Solução:**
```php
// Verificar em app/Models/RecognitionRecord.php:
protected $casts = [
    'face_descriptor' => 'array',  // Deve estar presente
];

// Limpar cache do Laravel:
php artisan cache:clear
php artisan config:clear
```

---

## 📚 Documentação Adicional

- **`ENROLMENT_USAGE.md`** - Guia de uso do endpoint de cadastro facial
- **`VERIFICATION_USAGE.md`** - Guia de uso do endpoint de reconhecimento

---

## ✅ Checklist de Implementação

### Backend (Flask API)
- [x] Endpoint `/health`
- [x] Endpoint `/extract_embedding`
- [x] Endpoint `/recognize_face`
- [x] Função `decode_base64_image()`
- [x] Função `euclidean_distance()`
- [x] Configuração CORS
- [x] Logging

### Backend (Laravel)
- [x] `FaceEnrolmentController` (cadastro facial)
- [x] `AttendanceVerificationController` (reconhecimento + ponto)
- [x] Rotas configuradas
- [x] Validação de requests
- [x] Comunicação HTTP com Flask
- [x] Persistência em MySQL
- [x] Logging

### Database (MySQL)
- [x] Tabela `recognition_records` existente
- [x] Coluna `face_descriptor` (longText)
- [x] Cast `'face_descriptor' => 'array'` no Model
- [x] Tabela `attendance_records` existente
- [x] Foreign keys configuradas

### Documentação
- [x] Guia completo (`FACIAL_RECOGNITION_COMPLETE_GUIDE.md`)
- [x] Guia de enrolment (`ENROLMENT_USAGE.md`)
- [x] Guia de verificação (`VERIFICATION_USAGE.md`)

---

## 🎓 Próximos Passos

### Para Produção:
1. **HTTPS:** Configurar SSL/TLS para comunicação segura
2. **Rate Limiting:** Limitar tentativas de reconhecimento por IP
3. **Monitoring:** Implementar Sentry/New Relic
4. **Backup:** Rotina de backup da tabela `recognition_records`
5. **Escalabilidade:** Considerar Redis para cache de embeddings

### Melhorias Futuras:
1. **Busca Vetorial:** Implementar pgvector ou Milvus para > 1000 usuários
2. **Anti-Spoofing:** Detectar fotos impressas (liveness detection)
3. **Multi-Face:** Reconhecer múltiplos rostos simultaneamente
4. **Mobile App:** Flutter/React Native com reconhecimento facial
5. **Dashboard:** Painel de analytics de reconhecimentos

---

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique os logs (`storage/logs/laravel.log`)
2. Consulte esta documentação
3. Teste Flask API isoladamente primeiro
4. Verifique se embeddings estão salvos no banco

---

**Sistema desenvolvido com:**
- 🐍 Python 3 + Flask
- 🐘 PHP 8 + Laravel
- 🗄️ MySQL 8
- 🤖 DeepFace + Facenet512
- 📷 OpenCV

**Arquitetura:** Microserviços stateless

**Performance:** ~2-3s por reconhecimento (até 1000 usuários)
