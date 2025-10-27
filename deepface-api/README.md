# FacePoint UniFil - DeepFace API

API Flask para reconhecimento facial usando **DeepFace** com modelo **FaceNet512**.

## 🎯 Características

- ✅ **Modelo FaceNet512**: Embeddings de **512 dimensões** (alta precisão)
- ✅ **Detector OpenCV**: Rápido e eficiente
- ✅ **Métrica Cosine**: Melhor para FaceNet512
- ✅ **API RESTful**: Endpoints simples e bem documentados
- ✅ **Logging completo**: Rastreamento de todas as operações
- ✅ **Tratamento de erros**: Respostas claras e informativas

---

## 📋 Requisitos

### Sistema

- **Python**: 3.8 ou superior
- **pip**: Gerenciador de pacotes Python
- **Sistema Operacional**: macOS, Linux ou Windows

### Dependências Python

Todas listadas em `requirements.txt`:
- Flask 3.0.0
- DeepFace 0.0.92
- TensorFlow 2.15.0
- OpenCV 4.9.0.80
- E outras...

---

## 🚀 Instalação

### 1. Navegue até o diretório da API

```bash
cd /Users/diogolepri/Desktop/Unifil/Estagio/facepoint-unifil/deepface-api
```

### 2. Execute o script de inicialização

```bash
./start.sh
```

O script irá automaticamente:
- ✅ Verificar se Python 3 está instalado
- ✅ Criar ambiente virtual (`venv/`)
- ✅ Instalar todas as dependências
- ✅ Baixar modelos do DeepFace (primeira vez)
- ✅ Iniciar o servidor na porta 5001

**Nota**: Na primeira execução, o download dos modelos pode levar alguns minutos (cerca de 100MB).

### 3. Verificar se está rodando

Abra o navegador em: http://localhost:5001/health

Você deverá ver:
```json
{
  "status": "healthy",
  "model": "Facenet512",
  "detector": "opencv",
  "distance_metric": "cosine",
  "timestamp": "2025-..."
}
```

---

## 🛑 Parar o Servidor

```bash
./stop.sh
```

---

## 📡 Endpoints da API

### 1. **Health Check**

Verifica se a API está funcionando.

```http
GET /health
```

**Resposta:**
```json
{
  "status": "healthy",
  "model": "Facenet512",
  "detector": "opencv",
  "distance_metric": "cosine",
  "timestamp": "2025-10-27T..."
}
```

---

### 2. **Registrar Face**

Registra uma nova face no sistema.

```http
POST /register
Content-Type: application/json

{
  "user_id": 123,
  "image_data": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "image_path": "faces_db/user_123/face_20251027_143022_1.jpg",
    "total_images": 1,
    "embedding_dimensions": 512,
    "model": "Facenet512"
  }
}
```

**Resposta de Erro:**
```json
{
  "success": false,
  "error": "Nenhum rosto detectado na imagem"
}
```

---

### 3. **Verificar Face**

Verifica se uma face pertence a um usuário específico.

```http
POST /verify
Content-Type: application/json

{
  "user_id": 123,
  "image_data": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Resposta:**
```json
{
  "success": true,
  "verified": true,
  "distance": 0.23,
  "threshold": 0.4,
  "model": "Facenet512"
}
```

---

### 4. **Reconhecer Face**

Identifica uma face entre todos os usuários registrados.

```http
POST /recognize
Content-Type: application/json

{
  "image_data": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "user_id": 123,
  "distance": 0.18,
  "identity_path": "faces_db/user_123/face_20251027_143022_1.jpg",
  "model": "Facenet512"
}
```

**Resposta quando não reconhece:**
```json
{
  "success": false,
  "error": "Nenhum rosto reconhecido"
}
```

---

### 5. **Remover Faces**

Remove todas as faces de um usuário.

```http
DELETE /delete/123
```

**Resposta:**
```json
{
  "success": true,
  "message": "Faces do usuário 123 removidas com sucesso"
}
```

---

### 6. **Estatísticas**

Obtém estatísticas do sistema.

```http
GET /stats
```

**Resposta:**
```json
{
  "total_users": 15,
  "total_images": 42,
  "model": "Facenet512",
  "detector": "opencv",
  "distance_metric": "cosine",
  "embedding_dimensions": 512
}
```

---

## 🔧 Integração com Laravel

O Laravel já está configurado para usar esta API através do `DeepFaceService.php`.

### Exemplo de uso no Laravel:

```php
use App\Services\DeepFaceService;

$deepFaceService = new DeepFaceService();

// Health check
$health = $deepFaceService->healthCheck();

// Registrar face
$result = $deepFaceService->registerFace($userId, $imageData);

// Verificar face
$result = $deepFaceService->verifyFace($userId, $imageData);

// Reconhecer face
$result = $deepFaceService->recognizeFace($imageData);
```

---

## 📊 Estrutura de Diretórios

```
deepface-api/
├── app.py                  # Aplicação Flask principal
├── requirements.txt        # Dependências Python
├── start.sh               # Script de inicialização
├── stop.sh                # Script para parar o servidor
├── README.md              # Esta documentação
├── faces_db/              # Banco de dados de faces
│   └── user_123/          # Faces do usuário 123
│       ├── face_*.jpg     # Imagens faciais
│       └── ...
├── logs/                  # Logs da API
│   └── deepface_api.log
└── venv/                  # Ambiente virtual Python (criado automaticamente)
```

---

## 🐛 Troubleshooting

### Problema: "Port 5001 já está em uso"

```bash
# Parar processo na porta 5001
./stop.sh

# Ou manualmente:
lsof -ti:5001 | xargs kill -9
```

### Problema: "Python 3 não encontrado"

```bash
# macOS (Homebrew)
brew install python3

# Ubuntu/Debian
sudo apt-get install python3 python3-pip

# Verificar instalação
python3 --version
```

### Problema: "Erro ao instalar TensorFlow"

Se o TensorFlow falhar ao instalar:

```bash
# macOS Apple Silicon (M1/M2/M3)
pip install tensorflow-macos
pip install tensorflow-metal

# Outras plataformas
pip install tensorflow==2.15.0
```

### Problema: "Nenhum rosto detectado"

Certifique-se que:
- ✅ A imagem está em formato base64 válido
- ✅ A imagem contém um rosto visível
- ✅ O rosto está bem iluminado
- ✅ O rosto ocupa pelo menos 10% da imagem

### Problema: "API não responde"

```bash
# Verificar se está rodando
curl http://localhost:5001/health

# Ver logs
tail -f logs/deepface_api.log

# Reiniciar
./stop.sh
./start.sh
```

---

## 📈 Comparação: FaceNet128 vs FaceNet512

| Característica | FaceNet128 (face-api.js) | FaceNet512 (DeepFace) |
|----------------|--------------------------|------------------------|
| **Dimensões** | 128 | 512 |
| **Precisão** | ⭐⭐⭐ Boa | ⭐⭐⭐⭐⭐ Excelente |
| **Velocidade** | ⚡⚡⚡ Rápido | ⚡⚡ Moderado |
| **Onde roda** | Frontend (Browser) | Backend (Python) |
| **Tamanho modelo** | ~6MB | ~100MB |
| **Uso** | Tempo real, preview | Registro e verificação final |

**Recomendação**: Use ambos!
- **Frontend (FaceNet128)**: Preview rápido e feedback ao usuário
- **Backend (FaceNet512)**: Verificação final e armazenamento

---

## 🔐 Segurança

- ✅ As imagens são processadas e descartadas após extração do embedding
- ✅ Apenas embeddings (vetores numéricos) são comparados
- ✅ Faces são armazenadas localmente no servidor
- ✅ Não há compartilhamento externo de dados biométricos
- ⚠️  **Importante**: Configure firewall para bloquear acesso externo à porta 5001

---

## 📝 Logs

Os logs são salvos em `logs/deepface_api.log`:

```bash
# Ver logs em tempo real
tail -f logs/deepface_api.log

# Ver últimas 50 linhas
tail -n 50 logs/deepface_api.log

# Buscar por usuário específico
grep "user_id: 123" logs/deepface_api.log
```

---

## 🚀 Performance

### Tempos médios de resposta:

- **Health Check**: ~10ms
- **Registrar Face**: ~2-3 segundos (primeira vez), ~1s depois
- **Verificar Face**: ~1-2 segundos
- **Reconhecer Face**: ~2-5 segundos (depende do número de usuários)

### Otimizações:

- ✅ Embeddings são calculados uma vez e reutilizados
- ✅ Modelos são carregados na memória
- ✅ Cache de modelos habilitado por padrão

---

## 📞 Suporte

Em caso de problemas:

1. Verifique os logs: `tail -f logs/deepface_api.log`
2. Teste o health check: `curl http://localhost:5001/health`
3. Revise a documentação acima
4. Consulte os logs do Laravel: `storage/logs/laravel.log`

---

## 📄 Licença

Copyright © 2025 UniFil. Todos os direitos reservados.

---

**FacePoint UniFil - Reconhecimento Facial com FaceNet512** 🎯
