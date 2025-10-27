# Diagramas de Estado - FacePoint UniFil

Este diretório contém os diagramas de estado do sistema FacePoint UniFil em formato PlantUML.

## 📋 Diagramas Disponíveis

### 1. **diagrama-estado-registro-ponto.puml**
Representa o fluxo completo de registro de ponto manual.

**Estados principais:**
- Sem Registro → Entrada Registrada → Saída Registrada → Completo
- Detecção de atrasos/adiantamentos
- Validações de dia permitido

**Referência no código:** `AttendanceController::registerAttendance()` (linhas 512-584)

---

### 2. **diagrama-estado-login-facial.puml**
Representa o processo completo de autenticação via reconhecimento facial.

**Estados principais:**
- Captura Facial → Processamento → Aguardando Confirmação → Login Completo
- Cálculo de distância euclidiana
- Timeout de 5 minutos
- Confirmação obrigatória do usuário

**Referências no código:**
- `AuthController::facialLogin()` (linhas 365-422)
- `AuthController::confirmFacialLogin()` (linhas 448-492)

---

### 3. **diagrama-estado-usuario.puml**
Representa o ciclo de vida completo de um usuário no sistema.

**Estados principais:**
- Criação → Ativo → Soft Deleted → Excluído Permanentemente
- Distinção entre login via email e facial
- Permissões diferentes por tipo de login

**Referências no código:**
- `User` model (linhas 147-210)
- `Admin\UserController` (soft delete e restore)

---

### 4. **diagrama-estado-reconhecimento-facial-ponto.puml**
Representa o registro de ponto usando reconhecimento facial via DeepFace API.

**Estados principais:**
- Captura → Validação → Reconhecimento DeepFace → Registro de Ponto
- Integração com DeepFace API
- Validação de threshold de confiança (padrão: 75%)
- Health check da API

**Referência no código:** `AttendanceController::verify()` (linhas 625-682)

---

## 🛠️ Como Visualizar os Diagramas

### Opção 1: Online (PlantUML Web Server)
1. Acesse: http://www.plantuml.com/plantuml/uml/
2. Cole o conteúdo do arquivo `.puml`
3. Visualize o diagrama renderizado

### Opção 2: Visual Studio Code (Recomendado)
1. Instale a extensão **PlantUML** (jebbs.plantuml)
2. Instale Java (necessário para renderização)
3. Abra o arquivo `.puml`
4. Pressione `Alt+D` para preview

### Opção 3: IntelliJ IDEA / PHPStorm
1. Instale o plugin **PlantUML integration**
2. Abra o arquivo `.puml`
3. Clique com botão direito → "Show PlantUML Diagram"

### Opção 4: CLI (Gerar imagens)
```bash
# Instalar PlantUML
brew install plantuml  # macOS
# ou
apt-get install plantuml  # Linux

# Gerar PNG
plantuml diagrama-estado-registro-ponto.puml

# Gerar SVG (melhor qualidade)
plantuml -tsvg diagrama-estado-registro-ponto.puml

# Gerar todos os diagramas
plantuml *.puml
```

---

## 📚 Convenções Utilizadas

### Tipos de Estados
- **Estado simples:** Retângulo com cantos arredondados
- **Estado composto:** Retângulo com sub-estados internos
- **Estado de escolha (choice):** Losango para decisões

### Notações
- `[*]` → Estado inicial/final
- `-->` → Transição entre estados
- `note right/left/bottom of` → Anotações explicativas
- `state "Nome" as Alias` → Alias para estados com nomes longos

### Cores e Estilos
Os diagramas usam o tema padrão do PlantUML. Para customizar:
```plantuml
skinparam state {
  BackgroundColor LightBlue
  BorderColor DarkBlue
  FontSize 14
}
```

---

## 🔗 Relacionamento com a Documentação

Estes diagramas complementam a documentação técnica principal:
- **DOCUMENTACAO_SISTEMA.md** → Documentação textual completa
- **docs/*.puml** → Representação visual dos fluxos

### Seções relacionadas na documentação:
| Diagrama | Seção na Documentação |
|----------|----------------------|
| registro-ponto | Seção 4.2 - AttendanceController |
| login-facial | Seção 4.1 - AuthController |
| usuario | Seção 3.1 - Model User |
| reconhecimento-facial-ponto | Seção 4.2 - verify() |

---

## 📖 Legenda de Estados por Diagrama

### Registro de Ponto
- **SemRegistro:** Início do dia, nenhuma marcação ainda
- **EntradaRegistrada:** Primeira marcação realizada (entry_time)
- **SaidaRegistrada:** Segunda marcação realizada (exit_time)
- **Status:** Adiantado (is_early), No Horário, Atrasado (is_late > 15min)

### Login Facial
- **Capturando Face:** Usando face-api.js no navegador
- **Processando Reconhecimento:** Cálculo de distância euclidiana
- **Aguardando Confirmação:** Sessão temporária com timeout 5min
- **Logado:** Sessão autenticada criada

### Usuário
- **Ativo:** Pode fazer login e usar o sistema
- **Soft Deleted:** deleted_at != NULL, pode ser restaurado
- **Excluído Permanentemente:** Removido do banco (irreversível)

### Reconhecimento Facial para Ponto
- **Validações:** DeepFaceService (healthCheck, validateImageData)
- **Reconhecimento DeepFace:** API externa Python/Flask
- **Validação de Confiança:** Threshold configurável (.env)
- **Registro de Ponto:** Mesma lógica do registro manual

---

## 🔄 Atualizações

Ao modificar a lógica de negócio relacionada a estes fluxos, **atualize os diagramas correspondentes** para manter a documentação sincronizada.

**Checklist de atualização:**
- [ ] Código alterado em Controller
- [ ] Diagrama de estado atualizado
- [ ] Notas explicativas revisadas
- [ ] Referências de linha atualizadas
- [ ] README-DIAGRAMAS.md atualizado (se necessário)

---

## 📝 Notas Importantes

### Tolerância de Atraso
O sistema considera **15 minutos** de tolerância (configurável em `AttendanceController::TOLERANCE_MINUTES`).

### Threshold de Reconhecimento
- **Login Facial (face-api.js):** 0.4 (distância euclidiana)
- **Ponto Facial (DeepFace):** 75% (confiança percentual)

### Timeout de Sessão Facial
**5 minutos** para confirmação do login facial (`facial_match_timestamp`).

### Proteções de Segurança
- Admin principal não pode ser excluído (verificação via `ADMIN_EMAIL` em .env)
- Soft delete preserva todo o histórico de registros
- Confirmação obrigatória no login facial

---

**Última atualização:** 25 de outubro de 2025
**Versão dos diagramas:** 1.0
**Desenvolvido para:** UniFil - Centro Universitário Filadélfia
