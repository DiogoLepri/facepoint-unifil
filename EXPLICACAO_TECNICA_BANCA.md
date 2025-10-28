# Explicação Técnica - Sistema de Reconhecimento Facial FacePoint UniFil

## Resumo para Apresentação na Banca

### Tecnologia Utilizada: **Face-API.js**

**Biblioteca**: [face-api.js](https://github.com/justadudewhohacks/face-api.js) v0.22.2
**Autor**: Vincent Mühler
**Licença**: MIT (Open Source)
**Baseada em**: TensorFlow.js (Google)

---

## 🎯 Por que Face-API.js?

### Contexto da Decisão Técnica

Durante o desenvolvimento, **inicialmente** planejamos usar:
- **DeepFace** (biblioteca Python)
- Modelos **FaceNet512** ou **FaceNet128**

### Mudança de Abordagem

**Problema identificado**:
- Incompatibilidades de versão entre bibliotecas Python
- Dificuldades com dependências (TensorFlow, Keras, etc.)
- API Python instável para ambiente de produção acadêmico
- Necessidade de manter servidor Python rodando separadamente

**Solução adotada**:
- Migração para **Face-API.js** (JavaScript puro)
- Execução **100% no navegador** (client-side)
- Sem necessidade de servidor Python
- Mais estável e confiável para ambiente acadêmico

---

## 🧠 Modelo Utilizado Atualmente

### Face Recognition Network

**Nome técnico**: `FaceRecognitionNet`
**Base**: Arquitetura **FaceNet** (Google Research - 2015)
**Dimensões do embedding**: **128 dimensões**
**Formato**: TensorFlow.js (quantizado)

### Composição da Rede Neural

O sistema utiliza **3 modelos** carregados do diretório `/public/models/`:

1. **Tiny Face Detector** (`tiny_face_detector_model`)
   - Detecta rostos na imagem
   - Baseado em SSD (Single Shot Detector)
   - Rápido e eficiente para web

2. **Face Landmark 68 Model** (`face_landmark_68_model`)
   - Detecta 68 pontos faciais
   - Olhos, nariz, boca, contorno do rosto
   - Permite alinhamento facial

3. **Face Recognition Model** (`face_recognition_model`)
   - **Este é o modelo principal de reconhecimento**
   - Gera embeddings de 128 dimensões
   - Baseado em arquitetura ResNet
   - Similar ao FaceNet original

---

## 📐 Arquitetura do Modelo

### Face Recognition Model - Estrutura

```
Input: Imagem 150x150 RGB
    ↓
[Convolutional Layers]
    conv32_down → 32 filtros
    conv32_1, conv32_2, conv32_3 → Blocos residuais
    ↓
    conv64_down → 64 filtros
    conv64_1, conv64_2, conv64_3 → Blocos residuais
    ↓
    conv128_down → 128 filtros
    conv128_1, conv128_2 → Blocos residuais
    ↓
    conv256_down → 256 filtros
    conv256_1, conv256_2, conv256_down_out → Blocos residuais
    ↓
[Fully Connected Layer]
    fc → 256 → 128 dimensões
    ↓
Output: Embedding de 128 números (vetor característico)
```

### Características Técnicas

- **Total de camadas**: ~30 camadas convolucionais
- **Parâmetros**: ~6.5 MB (comprimido)
- **Precisão**: Float32 com quantização para uint8
- **Normalização**: Batch Normalization em cada bloco
- **Ativação**: ReLU (Rectified Linear Unit)

---

## 🔬 Como Funciona o Reconhecimento

### 1. Cadastro (Registration)

```javascript
// Captura imagem da câmera
const detections = await faceapi
    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
    .withFaceLandmarks()
    .withFaceDescriptor();

// detections.descriptor = [128 números]
// Exemplo: [-0.123, 0.456, -0.789, ..., 0.321]
```

**O que acontece**:
1. Tiny Face Detector encontra o rosto
2. Face Landmark detecta 68 pontos faciais
3. Imagem é alinhada baseada nos pontos
4. Face Recognition gera **embedding de 128 dimensões**
5. Embedding é salvo no banco de dados (MySQL)

### 2. Login (Recognition)

```javascript
// Captura novo frame da câmera
const newDetection = await faceapi
    .detectSingleFace(video)
    .withFaceDescriptor();

// Compara com embeddings salvos
foreach (embeddingSalvo in bancoDeDados) {
    distancia = calcularDistanciaEuclidiana(
        newDetection.descriptor,
        embeddingSalvo
    );

    if (distancia < 0.4) {
        // MATCH! Usuário reconhecido
    }
}
```

### 3. Cálculo de Similaridade

**Distância Euclidiana**:
```
d = √(Σ(a[i] - b[i])²)

Onde:
- a = embedding do rosto capturado (128 números)
- b = embedding salvo no banco (128 números)
- d = distância (quanto menor, mais similar)
```

**Threshold utilizado**: `0.4`
- Distância < 0.4 = **MATCH** (mesma pessoa)
- Distância ≥ 0.4 = Pessoas diferentes

---

## 📊 Comparação FaceNet vs Face-API.js

| Característica | FaceNet Original | Face-API.js |
|---|---|---|
| **Linguagem** | Python | JavaScript |
| **Execução** | Servidor | Navegador |
| **Dimensões** | 128 ou 512 | 128 |
| **Base** | Inception/ResNet | ResNet |
| **Treinamento** | Google Dataset | Baseado no FaceNet |
| **Performance** | ~99.6% | ~98-99% |
| **Estabilidade** | Depende de libs | ✅ Muito estável |

---

## 💡 Explicação para a Banca

### Quando perguntarem sobre FaceNet512/128

**Resposta sugerida**:

> "Inicialmente, planejamos utilizar o modelo FaceNet128 ou FaceNet512 através da biblioteca DeepFace em Python. Porém, durante o desenvolvimento, enfrentamos problemas de compatibilidade entre as versões das dependências (TensorFlow, Keras) e instabilidade da API Python.
>
> Para garantir maior estabilidade e confiabilidade do sistema em ambiente de produção acadêmica, optamos por migrar para a biblioteca **Face-API.js**, que implementa uma arquitetura **baseada no FaceNet original**, também gerando embeddings de **128 dimensões**.
>
> O modelo utilizado (FaceRecognitionNet) usa a mesma técnica de deep learning com redes neurais convolucionais (ResNet) e produz vetores de características faciais com a mesma dimensionalidade do FaceNet128, mantendo a eficácia do reconhecimento facial."

### Vantagens da Escolha

1. **Execução Client-Side**: Todo processamento no navegador
2. **Sem dependências Python**: Apenas PHP + JavaScript
3. **Mais estável**: Sem conflitos de versões
4. **Privacidade**: Dados não saem do navegador até serem processados
5. **Performance**: TensorFlow.js otimizado para WebGL

### Desvantagens (ser honesto)

1. Performance levemente inferior ao FaceNet original
2. Limitado ao navegador (precisa de JavaScript habilitado)
3. Depende de conexão para carregar modelos (~6.5 MB)

---

## 📚 Referências Técnicas

1. **FaceNet Paper** (2015):
   - "FaceNet: A Unified Embedding for Face Recognition and Clustering"
   - Schroff, F., Kalenichenko, D., & Philbin, J.
   - Google Research

2. **Face-API.js**:
   - GitHub: https://github.com/justadudewhohacks/face-api.js
   - Baseado em papers de FaceNet e MTCNN

3. **TensorFlow.js**:
   - Google - Machine Learning para JavaScript
   - https://www.tensorflow.org/js

---

## 🎓 Conclusão

O sistema **FacePoint UniFil** utiliza tecnologia de ponta em reconhecimento facial, baseada nas mesmas técnicas do FaceNet (Google), adaptada para execução em navegadores modernos através do Face-API.js.

A escolha técnica prioriza:
- ✅ Estabilidade
- ✅ Facilidade de manutenção
- ✅ Conformidade com requisitos acadêmicos
- ✅ Eficácia no reconhecimento (threshold 0.4)

**Modelo**: FaceRecognitionNet (ResNet + 128D embeddings)
**Precisão esperada**: ~98-99% em condições controladas
**Status**: ✅ Funcional e em produção
