# Sistema NPI
# Especificação de Caso de Uso: Consultar o Histórico

**Versão 1.0**

## Histórico da Revisão

| Data | Versão | Descrição | Autor |
|------|--------|-----------|-------|
| 25/10/25 | 1.0 | Criação do documento | Diogo Lepri Moreira |

---

## Índice

1. [Breve Descrição](#1-breve-descrição)
2. [Fluxo Básico de Eventos](#2-fluxo-básico-de-eventos)
3. [Fluxos Alternativos](#3-fluxos-alternativos)
4. [Subfluxos](#4-subfluxos)
5. [Cenários Chave](#5-cenários-chave)
6. [Condições Prévias](#6-condições-prévias)
7. [Condições Posteriores](#7-condições-posteriores)
8. [Pontos de Extensão](#8-pontos-de-extensão)
9. [Requisitos Especiais](#9-requisitos-especiais)

---

## 1. Breve Descrição

Este caso de uso permite que o aluno consulte seu histórico completo de registros de ponto (entrada e saída), visualize estatísticas do período e aplique filtros por data. O sistema exibe informações detalhadas sobre cada registro, incluindo horários de entrada e saída, total de horas trabalhadas, status de regularidade e indicadores de atraso ou adiantamento.

---

## 2. Fluxo Básico de Eventos

1. O aluno acessa a opção "Histórico" no menu de navegação
2. O sistema verifica a autenticação do aluno
3. O sistema verifica se o login foi realizado via email/senha (CheckEmailLogin middleware)
4. O sistema busca todos os registros de ponto do aluno no banco de dados
5. O sistema ordena os registros por data de criação (mais recentes primeiro)
6. O sistema pagina os resultados (10 registros por página)
7. O sistema calcula as estatísticas do período:
   - Total de registros
   - Dias completos (com entrada e saída)
   - Total de horas no período
   - Registros irregulares (atrasados ou adiantados)
8. O sistema exibe a interface de histórico contendo:
   - Card com estatísticas do período
   - Filtros de data (data inicial e data final)
   - Tabela com os registros de ponto
   - Controles de paginação
9. Para cada registro na tabela, o sistema exibe:
   - Data completa com dia da semana
   - Horário de entrada com indicador de regularidade
   - Horário de saída (ou traço se não registrado)
   - Total de horas trabalhadas
   - Status do registro (Normal/Irregular/Incompleto)
10. O caso de uso é encerrado com sucesso

---

## 3. Fluxos Alternativos

### 3.1 Usuário Não Autenticado

Se no passo 2 do fluxo básico o aluno não estiver autenticado:
1. O sistema redireciona para a página de login
2. O sistema exibe mensagem "Você precisa estar autenticado para acessar esta página"
3. O caso de uso é encerrado

### 3.2 Login via Reconhecimento Facial

Se no passo 3 do fluxo básico o aluno tiver realizado login via reconhecimento facial:
1. O sistema bloqueia o acesso à funcionalidade
2. O sistema exibe mensagem "Esta funcionalidade está disponível apenas para usuários que fizeram login com email e senha"
3. O sistema redireciona o aluno para o dashboard
4. O caso de uso é encerrado

### 3.3 Nenhum Registro Encontrado

Se no passo 4 do fluxo básico não houver registros de ponto para o aluno:
1. O sistema exibe as estatísticas com valores zerados
2. O sistema exibe a tabela vazia com mensagem "Nenhum registro encontrado"
3. O sistema exibe os filtros de data (habilitados)
4. O caso de uso é encerrado

### 3.4 Aplicação de Filtro por Data

Quando o aluno preenche os campos de filtro de data:
1. O aluno seleciona a data inicial no campo "Data Inicial"
2. O aluno seleciona a data final no campo "Data Final"
3. O sistema submete automaticamente o formulário (via JavaScript)
4. O sistema aplica o subfluxo "Filtrar Registros por Data"
5. O fluxo retorna ao passo 7 do fluxo básico

### 3.5 Limpar Filtros

Quando o aluno clica no botão "Limpar Filtros":
1. O sistema remove todos os parâmetros de filtro
2. O sistema redireciona para a rota sem parâmetros
3. O fluxo retorna ao passo 4 do fluxo básico

### 3.6 Navegação entre Páginas

Quando o aluno clica em um link de paginação:
1. O sistema mantém os filtros aplicados na URL
2. O sistema carrega a página solicitada
3. O fluxo retorna ao passo 4 do fluxo básico com os filtros preservados

---

## 4. Subfluxos

### 4.1 Filtrar Registros por Data

1. O sistema recebe os parâmetros `start_date` e `end_date` da requisição
2. O sistema valida se `start_date` está preenchido:
   - Se sim, aplica filtro `whereDate('created_at', '>=', start_date)`
3. O sistema valida se `end_date` está preenchido:
   - Se sim, aplica filtro `whereDate('created_at', '<=', end_date)`
4. O sistema executa a query com os filtros aplicados
5. O subfluxo retorna a coleção filtrada

### 4.2 Calcular Estatísticas do Período

1. O sistema conta o total de registros na coleção
2. O sistema filtra os registros onde `entry_time` e `exit_time` não são nulos
3. O sistema conta o total de dias completos
4. O sistema inicializa o contador de minutos totais (total_minutes = 0)
5. Para cada registro completo:
   - Calcula a diferença em minutos entre `exit_time` e `entry_time`
   - Adiciona ao total_minutes
6. O sistema converte total_minutes em formato "Xh Ymin"
7. O sistema conta registros onde `is_early` ou `is_late` é true
8. O subfluxo retorna:
   - Total de registros
   - Dias completos
   - Horas no período (formatado)
   - Registros irregulares

### 4.3 Determinar Status do Registro

1. O sistema verifica se `entry_time` é nulo:
   - Se sim, status = "Incompleto" (badge azul)
2. O sistema verifica se `exit_time` é nulo:
   - Se sim, status = "Incompleto" (badge azul)
3. O sistema verifica se `is_late` ou `is_early` é true:
   - Se sim, status = "Irregular" (badge vermelho)
4. Caso contrário, status = "Normal" (badge verde)
5. O subfluxo retorna o status e a classe CSS correspondente

### 4.4 Calcular Total de Horas do Registro

1. O sistema verifica se `entry_time` e `exit_time` não são nulos
2. Se ambos estão preenchidos:
   - Calcula a diferença em minutos usando Carbon
   - Divide os minutos totais por 60 para obter horas inteiras
   - Calcula o resto da divisão para obter minutos restantes
   - Formata como "Xh Ymin"
3. Se algum horário está nulo:
   - Retorna traço "-"
4. O subfluxo retorna o tempo formatado ou traço

---

## 5. Cenários Chave

### Cenário 1: Consulta Completa com Múltiplos Registros

Aluno com login via email acessa o histórico, possui 25 registros de ponto variados (normais, atrasados, incompletos). O sistema exibe a primeira página com 10 registros, estatísticas completas do período e controles de paginação funcionais.

### Cenário 2: Filtro por Período Específico

Aluno deseja verificar apenas os registros de setembro/2025. Seleciona data inicial (01/09/2025) e data final (30/09/2025), o sistema filtra automaticamente e exibe apenas os registros desse período com estatísticas recalculadas.

### Cenário 3: Primeiro Acesso sem Registros

Aluno recém-cadastrado acessa o histórico pela primeira vez. O sistema exibe a interface com estatísticas zeradas, tabela vazia com mensagem informativa e filtros disponíveis para uso futuro.

### Cenário 4: Tentativa de Acesso com Login Facial

Aluno realiza login via reconhecimento facial e tenta acessar o histórico. O sistema bloqueia o acesso, exibe mensagem explicativa sobre a restrição e redireciona para o dashboard.

### Cenário 5: Navegação com Filtros Ativos

Aluno aplica filtro de período (01/10 a 15/10) que retorna 15 registros. Ao navegar para a segunda página, os filtros são mantidos e a paginação exibe corretamente os registros 11-15.

---

## 6. Condições Prévias

### 6.1 Usuário Autenticado

O aluno deve estar autenticado no sistema com sessão ativa válida.

### 6.2 Login via Email/Senha

O aluno deve ter realizado login utilizando credenciais de email e senha (não via reconhecimento facial). O campo `last_login_type` do usuário deve conter o valor `'email'`.

### 6.3 Perfil de Aluno Ativo

O usuário deve possuir perfil de aluno ativo no sistema (não pode ser administrador acessando funcionalidade de aluno).

### 6.4 Banco de Dados Disponível

O sistema deve ter conexão ativa com o banco de dados para recuperação dos registros de ponto.

---

## 7. Condições Posteriores

### 7.1 Histórico Exibido com Sucesso

O sistema apresenta a interface completa do histórico contendo:
- Estatísticas precisas do período consultado
- Lista paginada de registros ordenados cronologicamente
- Filtros de data funcionais
- Indicadores visuais de status (Normal/Irregular/Incompleto)
- Controles de navegação entre páginas

### 7.2 Filtros Aplicados Corretamente

Se o aluno aplicou filtros de data, o sistema mantém esses filtros:
- Nos parâmetros da URL para compartilhamento/bookmark
- Ao navegar entre páginas da paginação
- Nos campos do formulário (valores pré-preenchidos)

### 7.3 Dados Consistentes

Todas as estatísticas apresentadas refletem com precisão os registros exibidos:
- Total de registros corresponde à contagem real
- Dias completos calculados corretamente
- Horas totais somadas com precisão
- Registros irregulares identificados corretamente

---

## 8. Pontos de Extensão

### 8.1 Após Visualização do Histórico

Após o passo 10 do fluxo básico, o aluno pode:
- Aplicar filtros de data para refinar a consulta
- Navegar entre páginas para visualizar mais registros
- Retornar ao dashboard através do menu de navegação
- Acessar outras funcionalidades do sistema

### 8.2 Durante a Consulta

Durante a visualização do histórico, se um novo registro de ponto for criado:
- O registro NÃO aparece automaticamente (sem atualização em tempo real)
- O aluno deve atualizar manualmente a página para visualizar novos registros

---

## 9. Requisitos Especiais

### 9.1 Desempenho

- O carregamento inicial da página de histórico deve ocorrer em no máximo 2 segundos
- A aplicação de filtros deve processar e exibir resultados em no máximo 1,5 segundos
- A paginação deve ser instantânea (< 1 segundo) para navegação entre páginas
- Queries ao banco de dados devem usar índices apropriados nas colunas `user_id` e `created_at`

### 9.2 Usabilidade

- A interface deve ser responsiva e funcional em dispositivos móveis (smartphones e tablets)
- Os filtros de data devem usar calendários interativos (date picker) para facilitar seleção
- As cores dos badges de status devem seguir convenções intuitivas:
  - Verde para Normal
  - Vermelho para Irregular
  - Azul para Incompleto
- A paginação deve indicar claramente a página atual e total de páginas

### 9.3 Segurança

- O sistema deve garantir que cada aluno visualize APENAS seus próprios registros
- A query de busca deve SEMPRE filtrar por `user_id` do usuário autenticado
- Nenhum parâmetro de URL deve permitir acesso a registros de outros alunos
- Tentativas de manipulação de URL para acessar dados de terceiros devem ser bloqueadas

### 9.4 Integridade dos Dados

- Cálculos de horas devem usar a biblioteca Carbon para precisão em timezone
- Datas devem ser exibidas no formato brasileiro (dd/mm/aaaa)
- Horários devem ser exibidos no formato 24h (HH:mm)
- Registros incompletos (sem saída) não devem ser incluídos no cálculo de horas totais

### 9.5 Acessibilidade

- A tabela de histórico deve ser navegável via teclado
- Campos de filtro devem ter labels apropriados para leitores de tela
- Badges de status devem incluir atributos ARIA para indicar o significado das cores
- Contraste de cores deve atender WCAG 2.1 nível AA

### 9.6 Disponibilidade

- A funcionalidade de histórico deve estar disponível 24/7 para consulta
- Em caso de manutenção do sistema, mensagem apropriada deve ser exibida
- Falhas na recuperação de dados devem exibir mensagem de erro amigável ao usuário

### 9.7 Limitações Conhecidas

- Não há funcionalidade de exportação de histórico em formato CSV/PDF para alunos (disponível apenas para administradores via relatórios)
- Não há sistema de notificação para irregularidades detectadas
- Não há suporte para edição ou exclusão de registros pelos alunos
- Usuários que fizeram login via reconhecimento facial não podem acessar o histórico por decisão de segurança
