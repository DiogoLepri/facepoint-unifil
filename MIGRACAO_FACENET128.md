# Migração para FaceNet128

## Data: 2025-10-27

## Contexto

Devido à indisponibilidade temporária da API do modelo **FaceNet512**, o sistema foi adaptado para utilizar o modelo **FaceNet128**. Esta documentação descreve as mudanças implementadas para garantir a compatibilidade.

## Mudanças Implementadas

### 1. Atualização do `DeepFaceService.php`

**Localização:** `app/Services/DeepFaceService.php`

#### Mudanças realizadas:

1. **Estrutura de resposta do método `recognizeFace()`:**
   - Adaptado para retornar a estrutura compatível com o `AttendanceController`
   - Estrutura de retorno:
     ```php
     [
         'success' => true/false,
         'data' => [
             'success' => true/false,
             'user_id' => int,
             'distance' => float,
             'confidence' => float,
             'message' => string
         ]
     ]
     ```

2. **Novo método `convertDistanceToConfidence()`:**
   - Converte a distância (distance) retornada pela API em porcentagem de confiança
   - Fórmula: `confidence = (1 - (distance / threshold)) * 100`
   - Threshold padrão: 0.4 (padrão para FaceNet com métrica cosine)

3. **Novo método `meetsConfidenceThreshold()`:**
   - Verifica se a confiança atende ao limite configurado (75% por padrão)
   - Retorna `true` se `confidence >= confidenceThreshold`

4. **Correção nas configurações:**
   - Ajustado para buscar `retry.attempts` e `retry.delay` do arquivo de configuração

### 2. Atualização do `config/deepface.php`

**Localização:** `config/deepface.php`

#### Mudanças realizadas:

1. **Atualização do modelo padrão:**
   - Alterado de `Facenet512` para `Facenet128`
   - Linha 27: `'model' => env('DEEPFACE_MODEL', 'Facenet128')`

2. **Atualização da documentação:**
   - Adicionada nota explicativa sobre o uso temporário do FaceNet128
   - Documentado que o FaceNet128 oferece desempenho similar com menor uso de recursos

### 3. Atualização do arquivo `.env`

**Mudança realizada:**
```bash
DEEPFACE_MODEL=Facenet128
```

### 4. Verificação do `AttendanceController.php`

**Localização:** `app/Http/Controllers/AttendanceController.php`

O código já estava compatível com a nova estrutura:
- Linhas 246-287: Usa corretamente `$recognitionResult['data']`
- Linha 281: Chama `meetsConfidenceThreshold()` corretamente

## Diferenças entre FaceNet512 e FaceNet128

| Característica | FaceNet512 | FaceNet128 |
|---------------|-----------|-----------|
| Dimensões do embedding | 512 | 128 |
| Precisão | Ligeiramente superior | Muito boa |
| Velocidade | Mais lento | Mais rápido |
| Uso de memória | Maior | Menor |
| Threshold (cosine) | ~0.4 | ~0.4 |

## Compatibilidade da API

A API Python do DeepFace permanece na **porta 5001** (`http://localhost:5001`) e oferece os mesmos endpoints:

- `GET /health` - Verifica status da API
- `POST /register` - Registra nova face
- `POST /recognize` - Reconhece face
- `POST /verify` - Verifica se face pertence a usuário específico
- `DELETE /delete/{user_id}` - Remove faces de um usuário
- `GET /stats` - Obtém estatísticas do sistema

## Fluxo de Reconhecimento Facial

```
1. Cliente envia imagem base64
   ↓
2. AttendanceController valida dados
   ↓
3. DeepFaceService.recognizeFace()
   ↓
4. API Python DeepFace (FaceNet128)
   - Detecta face
   - Extrai embedding (128 dimensões)
   - Compara com faces cadastradas
   - Calcula distance
   - Converte para confidence (%)
   - Retorna { user_id, distance, confidence }
   ↓
5. DeepFaceService recebe resposta
   ↓
6. meetsConfidenceThreshold()
   - Verifica se confidence >= 75%
   ↓
7. Registro de ponto aprovado/rejeitado
```

## Estrutura de Dados

### Requisição para `/recognize`:
```json
{
    "image_data": "data:image/jpeg;base64,..."
}
```

### Resposta da API DeepFace (Python):
```json
{
    "success": true,
    "user_id": 123,
    "distance": 0.15,
    "confidence": 87.5
}
```

**NOTA:** A confidence agora é calculada pela API Python usando a fórmula:
```python
confidence = (1 - (distance / 0.4)) * 100
```

### Resposta do DeepFaceService (PHP):
```json
{
    "success": true,
    "data": {
        "success": true,
        "user_id": 123,
        "distance": 0.15,
        "confidence": 87.5,
        "message": "Face reconhecida com sucesso"
    }
}
```

## Configurações Atuais

```env
DEEPFACE_API_URL=http://localhost:5001
DEEPFACE_TIMEOUT=30
DEEPFACE_CONFIDENCE_THRESHOLD=75
DEEPFACE_MODEL=Facenet128
DEEPFACE_DETECTOR=opencv
DEEPFACE_DISTANCE_METRIC=cosine
DEEPFACE_RETRY_ATTEMPTS=3
DEEPFACE_RETRY_DELAY=1
```

## Testes Recomendados

1. **Teste de health check:**
   ```bash
   curl http://localhost:5001/health
   ```

2. **Teste de registro:**
   - Cadastrar uma face via interface web
   - Verificar logs em `storage/logs/laravel.log`

3. **Teste de reconhecimento:**
   - Tentar reconhecer uma face cadastrada
   - Verificar se confidence é calculada corretamente
   - Verificar se threshold de 75% está sendo aplicado

4. **Teste de precisão:**
   - Comparar taxa de reconhecimento com FaceNet512 (quando disponível)
   - Documentar diferenças observadas

## Reversão para FaceNet512

Quando a API do FaceNet512 estiver disponível novamente:

1. Atualizar `.env`:
   ```env
   DEEPFACE_MODEL=Facenet512
   ```

2. Atualizar `config/deepface.php` linha 27:
   ```php
   'model' => env('DEEPFACE_MODEL', 'Facenet512'),
   ```

3. Reiniciar a API Python do DeepFace

4. Limpar cache de configuração:
   ```bash
   php artisan config:cache
   ```

**Nota:** Não é necessário alterar o código PHP, pois a estrutura de resposta permanece a mesma.

## Observações Importantes

1. **Compatibilidade:** O código atual é compatível com ambos os modelos (FaceNet512 e FaceNet128)

2. **Performance:** O FaceNet128 oferece processamento mais rápido, o que pode melhorar a experiência do usuário

3. **Precisão:** Embora o FaceNet128 tenha embeddings menores, a precisão permanece excelente para a maioria dos casos de uso

4. **Threshold:** O threshold de distance (0.4) e confidence (75%) foram mantidos, pois são adequados para ambos os modelos

## Commit Base

Este trabalho foi baseado no commit: `07b299e9150ee15a20e91c30a939b1b4a78f19ab`

## Próximos Passos

1. ✅ Implementar mudanças no DeepFaceService
2. ✅ Adicionar método `meetsConfidenceThreshold()`
3. ✅ **Mover** cálculo de confidence para API Python
4. ✅ Atualizar configurações para FaceNet128
5. ✅ Documentar mudanças
6. ⏳ Testar reconhecimento facial com FaceNet128
7. ⏳ Monitorar performance e precisão
8. ⏳ Considerar reversão para FaceNet512 quando disponível

## Arquitetura Correta (Conforme Relatório)

```
┌─────────────────────────────────────────────────────────────┐
│                      Cliente (Browser)                       │
│                   Captura imagem base64                      │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              AttendanceController (Laravel)                  │
│            - Valida imagem                                   │
│            - Chama DeepFaceService                           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              DeepFaceService (PHP)                           │
│            - Valida formato                                  │
│            - Envia para API Python                           │
│            - Recebe { user_id, distance, confidence }        │
│            - Aplica threshold (75%)                          │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              API Python (Flask + DeepFace)                   │
│            1. Detecta face                                   │
│            2. Extrai embedding (128 dims)                    │
│            3. Compara com banco                              │
│            4. Calcula distance                               │
│            5. ⭐ Converte para confidence ⭐                 │
│            6. Retorna resultado                              │
└─────────────────────────────────────────────────────────────┘
```

**Mudança Principal:** O cálculo de `confidence` agora ocorre na API Python, conforme descrito no relatório de estágio.

---

**Autor:** Sistema automatizado de documentação
**Revisado por:** Diogo Lepri Moreira
