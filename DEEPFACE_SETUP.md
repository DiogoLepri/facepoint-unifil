# 🚀 Setup Rápido - DeepFace API (FaceNet512)

## ⚡ Instalação em 3 Passos

### 1️⃣ Navegue até o diretório da API

```bash
cd deepface-api
```

### 2️⃣ Execute o script de inicialização

```bash
./start.sh
```

O script irá:
- ✅ Criar ambiente virtual Python
- ✅ Instalar dependências (pode levar ~5 minutos na primeira vez)
- ✅ Baixar modelo FaceNet512 (~100MB)
- ✅ Iniciar servidor na porta 5001

### 3️⃣ Teste a API

Abra no navegador: http://localhost:5001/health

Ou via terminal:
```bash
curl http://localhost:5001/health
```

Você deve ver:
```json
{
  "status": "healthy",
  "model": "Facenet512",
  ...
}
```

---

## ✅ Pronto!

A API DeepFace com **FaceNet512** (512 dimensões) está rodando!

O Laravel já está configurado para usar esta API através do `DeepFaceService.php`.

---

## 🛑 Parar o Servidor

```bash
cd deepface-api
./stop.sh
```

---

## 📖 Documentação Completa

Veja: `deepface-api/README.md`

---

## 🔍 O que mudou no projeto?

### ✅ Antes (FaceNet128):
- face-api.js (Frontend)
- 128 dimensões
- Comparação em PHP

### ✅ Agora (FaceNet512):
- DeepFace + Flask (Backend Python)
- 512 dimensões
- Maior precisão
- API RESTful completa

### 🎯 Melhor prática:
Use **ambos**!
- **FaceNet128** para preview rápido no frontend
- **FaceNet512** para verificação final no backend

---

## 🐛 Problemas?

```bash
# Ver logs
tail -f deepface-api/logs/deepface_api.log

# Reiniciar
cd deepface-api
./stop.sh
./start.sh

# Verificar porta
lsof -i :5001
```

---

## 📞 Precisa de Ajuda?

1. Leia: `deepface-api/README.md`
2. Verifique logs: `deepface-api/logs/deepface_api.log`
3. Verifique Laravel logs: `storage/logs/laravel.log`

---

**FacePoint UniFil - Powered by FaceNet512** 🎯
