# Documentação Técnica Completa - Sistema FacePoint UniFil

## Sumário Executivo

O **FacePoint UniFil** é um sistema de controle de ponto baseado em reconhecimento facial desenvolvido especificamente para os cursos de Ciência da Computação e Engenharia de Software da UniFil (Universidade Filadélfia). O sistema combina autenticação tradicional (email/senha) com reconhecimento facial avançado usando face-api.js e DeepFace API para fornecer uma solução moderna e segura de controle de presença.

---

## 1. Visão Geral do Sistema

### 1.1 Objetivo
Automatizar e modernizar o processo de registro de ponto dos estudantes através de reconhecimento facial, eliminando a necessidade de sistemas manuais ou cartões de identificação, enquanto mantém um histórico detalhado de frequência e horários trabalhados.

### 1.2 Tecnologias Utilizadas

| Tecnologia | Versão/Descrição | Finalidade |
|------------|------------------|------------|
| **Laravel** | 11.x | Framework PHP para backend |
| **PHP** | 8.2+ | Linguagem de programação principal |
| **MySQL** | 8.0+ | Banco de dados relacional |
| **Bootstrap** | 5.x | Framework CSS para interface responsiva |
| **jQuery** | 3.6.x | Manipulação DOM e AJAX |
| **face-api.js** | Latest | Reconhecimento facial no cliente (128 dimensões) |
| **DeepFace API** | Python/Flask | Serviço de reconhecimento facial backend |
| **DomPDF** | Latest | Geração de relatórios em PDF |
| **Laravel Sanctum** | 4.x | Autenticação API com tokens |
| **Carbon** | 3.x | Manipulação de datas e horários |

### 1.3 Características Principais
- ✅ Dupla autenticação: Email/Senha e Reconhecimento Facial
- ✅ Sistema de agendamento flexível por dia da semana
- ✅ Registro de entrada e saída com detecção de atrasos
- ✅ Geração de relatórios em PDF (diário, semanal, mensal)
- ✅ Dashboard com estatísticas em tempo real
- ✅ Gerenciamento de usuários com soft delete
- ✅ Integração com DeepFace para reconhecimento robusto
- ✅ Interface responsiva e moderna

---

## 2. Arquitetura do Sistema

### 2.1 Arquitetura MVC (Model-View-Controller)

O sistema segue rigorosamente o padrão **MVC** do Laravel, separando as responsabilidades em três camadas principais:

#### **Model (Modelo)**
Responsável pela lógica de dados e comunicação com o banco de dados através do Eloquent ORM.

**Modelos principais:**
- `User` - Gerencia dados de usuários (alunos e administradores)
- `AttendanceRecord` - Registros de ponto (entrada/saída)
- `RecognitionRecord` - Descritores faciais armazenados

#### **View (Visão)**
Templates Blade responsáveis pela apresentação dos dados ao usuário.

**Diretórios de views:**
- `resources/views/auth/` - Telas de autenticação
- `resources/views/aluno/` - Dashboard e funcionalidades do aluno
- `resources/views/admin/` - Painel administrativo
- `resources/views/layouts/` - Layouts compartilhados

#### **Controller (Controlador)**
Processa requisições HTTP e coordena a interação entre Models e Views.

**Controladores principais:**
- `AuthController` - Autenticação (login, registro, facial)
- `DashboardController` - Dashboard do aluno
- `AttendanceController` - Registro de ponto
- `Admin\DashboardController` - Dashboard administrativo
- `Admin\UserController` - Gerenciamento de usuários

### 2.2 Estrutura de Diretórios

```
facepoint-unifil/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controladores da aplicação
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── RecognitionController.php
│   │   │   └── Admin/
│   │   │       ├── DashboardController.php
│   │   │       └── UserController.php
│   │   ├── Middleware/           # Middlewares customizados
│   │   │   ├── StudentMiddleware.php
│   │   │   ├── AdminMiddleware.php
│   │   │   └── CheckEmailLogin.php
│   │   └── Kernel.php            # Registro de middlewares
│   │
│   ├── Models/                   # Modelos Eloquent
│   │   ├── User.php
│   │   ├── AttendanceRecord.php
│   │   └── RecognitionRecord.php
│   │
│   └── Providers/                # Service Providers
│       └── AppServiceProvider.php
│
├── bootstrap/                    # Inicialização do framework
│   └── cache/                    # Cache de configuração
│
├── config/                       # Arquivos de configuração
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── dompdf.php
│   └── sanctum.php
│
├── database/
│   ├── migrations/               # 22 arquivos de migração
│   └── seeders/                  # Seeders do banco
│
├── public/                       # Arquivos públicos
│   ├── index.php                 # Ponto de entrada da aplicação
│   ├── css/
│   ├── js/
│   └── models/                   # Modelos do face-api.js
│
├── resources/
│   └── views/                    # Templates Blade (15 arquivos)
│       ├── auth/
│       ├── aluno/
│       ├── admin/
│       └── layouts/
│
├── routes/
│   ├── web.php                   # Rotas web (35+ rotas)
│   └── api.php                   # Rotas API (3 rotas)
│
├── storage/
│   ├── app/
│   │   └── public/
│   │       └── users/            # Imagens faciais
│   ├── fonts/                    # Fontes para DomPDF
│   └── logs/                     # Logs da aplicação
│
└── .env                          # Variáveis de ambiente
```

---

## 3. Camada de Dados (Models)

### 3.1 Model: User

**Localização:** `app/Models/User.php`

**Responsabilidade:** Gerencia os dados de usuários (alunos e administradores) do sistema.

**Atributos principais:**
```php
protected $fillable = [
    'name',          // Nome completo do usuário
    'email',         // Email institucional (@edu.unifil.br)
    'password',      // Senha criptografada
    'matricula',     // Matrícula (9 dígitos)
    'curso',         // Ciência da Computação ou Engenharia de Software
    'role',          // 'aluno' ou 'admin'
    'schedule',      // JSON: Horários por dia da semana
    'last_login_type', // 'email' ou 'facial'
    'last_login_at',   // Timestamp do último login
];
```

**Casts (Conversões automáticas):**
```php
protected $casts = [
    'email_verified_at' => 'datetime',
    'last_login_at' => 'datetime',
    'days_of_week' => 'array',
    'schedule' => 'array',  // JSON convertido para array PHP
];
```

**Relacionamentos:**
```php
// Um usuário tem muitos registros de reconhecimento facial
public function recognitionRecords()
{
    return $this->hasMany(RecognitionRecord::class);
}

// Um usuário tem muitos registros de ponto
public function attendanceRecords()
{
    return $this->hasMany(AttendanceRecord::class);
}
```

**Traits utilizados:**
- `HasApiTokens` - Para autenticação via API Sanctum
- `HasFactory` - Para testes e seeders
- `Notifiable` - Para envio de notificações
- `SoftDeletes` - Para exclusão lógica (não remove do banco)

**Formato do schedule (JSON):**
```json
{
  "monday": {"entry": "14:00", "exit": "18:00"},
  "tuesday": {"entry": "14:00", "exit": "18:00"},
  "wednesday": {"entry": "14:00", "exit": "18:00"},
  "thursday": {"entry": "14:00", "exit": "18:00"},
  "friday": {"entry": "14:00", "exit": "18:00"}
}
```

---

### 3.2 Model: AttendanceRecord

**Localização:** `app/Models/AttendanceRecord.php`

**Responsabilidade:** Armazena registros de entrada e saída de ponto dos alunos.

**Atributos principais:**
```php
protected $fillable = [
    'user_id',            // FK para users
    'entry_time',         // Horário de entrada (datetime)
    'exit_time',          // Horário de saída (datetime)
    'status',             // Status do registro ('registered')
    'is_early',           // Boolean: chegou mais cedo?
    'is_late',            // Boolean: chegou atrasado?
    'expected_time',      // Horário esperado (time)
    'minutes_difference', // Diferença em minutos
    'punch_type',         // 'entry' ou 'exit'
];
```

**Casts:**
```php
protected $casts = [
    'entry_time' => 'datetime',
    'exit_time' => 'datetime',
    'is_early' => 'boolean',
    'is_late' => 'boolean',
];
```

**Relacionamento:**
```php
// Cada registro pertence a um usuário
public function user()
{
    return $this->belongsTo(User::class);
}
```

**Lógica de negócio:**
- **Tolerância de atraso:** 15 minutos (definido em `AttendanceController::TOLERANCE_MINUTES`)
- **Detecção de atraso:** Se `minutes_difference < -15`, marca `is_late = true`
- **Detecção de adiantamento:** Se `minutes_difference > 0`, marca `is_early = true`
- **Cálculo de diferença:** Usa Carbon para diferença em minutos entre horário esperado e real

---

### 3.3 Model: RecognitionRecord

**Localização:** `app/Models/RecognitionRecord.php`

**Responsabilidade:** Armazena descritores faciais de 128 dimensões para reconhecimento.

**Atributos principais:**
```php
protected $fillable = [
    'user_id',         // FK para users
    'image_path',      // Caminho da imagem facial (opcional)
    'face_descriptor', // Array JSON de 128 floats
    'capture_type',    // Tipo: registration_1, registration_2, registration_3, confirmed_login
];
```

**Casts:**
```php
protected $casts = [
    'face_descriptor' => 'array',  // JSON convertido automaticamente
];
```

**Relacionamento:**
```php
public function user()
{
    return $this->belongsTo(User::class);
}
```

**Tipos de captura:**
- `registration_1` - Primeira foto no cadastro
- `registration_2` - Segunda foto no cadastro
- `registration_3` - Terceira foto no cadastro
- `confirmed_login` - Login confirmado pelo usuário (analytics)

**Formato do face_descriptor:**
```json
[
  -0.0234, 0.1234, -0.5678, 0.9012, ..., 0.3456
]
// Array de 128 números float
```

---

## 4. Camada de Controle (Controllers)

### 4.1 AuthController

**Localização:** `app/Http/Controllers/AuthController.php`

**Responsabilidade:** Gerencia todo o processo de autenticação do sistema.

#### Métodos principais:

##### **showLoginForm()**
```php
public function showLoginForm()
{
    return view('auth.login');
}
```
- Exibe o formulário de login
- Permite login via email/senha ou reconhecimento facial

##### **login(Request $request)**
```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        Auth::user()->update([
            'last_login_type' => 'email',
            'last_login_at' => now(),
        ]);

        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended('dashboard');
    }

    return back()->withErrors([
        'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
    ])->onlyInput('email');
}
```

**Fluxo de execução:**
1. Valida email e senha
2. Tenta autenticar com `Auth::attempt()`
3. Se sucesso: regenera sessão, atualiza `last_login_type` e `last_login_at`
4. Redireciona admin para `/admin/dashboard`, aluno para `/dashboard`
5. Se falha: retorna erro

##### **facialLogin(Request $request)**

**Fluxo completo do reconhecimento facial:**

```php
public function facialLogin(Request $request)
{
    // 1. Recebe descritor facial de 128 dimensões do frontend
    $inputDescriptor = $request->json('face_descriptor');

    // 2. Busca todos os usuários com reconhecimento cadastrado
    $users = User::whereHas('recognitionRecords')
                 ->with('recognitionRecords')
                 ->get();

    // 3. Variáveis de controle
    $bestMatch = null;
    $bestMatchDistance = PHP_FLOAT_MAX;
    $recognitionThreshold = 0.4;  // Threshold configurável

    // 4. Loop por todos os descritores armazenados
    foreach ($users as $user) {
        foreach ($user->recognitionRecords as $record) {
            // Calcula distância euclidiana
            $distance = $this->calculateEuclideanDistance(
                $inputDescriptor,
                $record->face_descriptor
            );

            // Se distância < threshold E melhor que anterior, atualiza
            if ($distance < $recognitionThreshold && $distance < $bestMatchDistance) {
                $bestMatchDistance = $distance;
                $bestMatch = $user;
            }
        }
    }

    // 5. Se encontrou match, salva na sessão para confirmação
    if ($bestMatch) {
        session([
            'facial_match_user_id' => $bestMatch->id,
            'facial_match_distance' => $bestMatchDistance,
            'facial_match_descriptor' => $inputDescriptor,
            'facial_match_timestamp' => time()
        ]);

        return response()->json([
            'success' => true,
            'user_name' => $bestMatch->name,
            'requires_confirmation' => true
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Usuário não reconhecido'
    ]);
}
```

**Cálculo da Distância Euclidiana:**
```php
private function calculateEuclideanDistance($descriptor1, $descriptor2)
{
    $sum = 0;
    for ($i = 0; $i < count($descriptor1); $i++) {
        $diff = $descriptor1[$i] - $descriptor2[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}
```

**Fórmula matemática:**
```
distance = √(Σ(d1[i] - d2[i])²)
```

**Threshold de reconhecimento:**
- Valor padrão: **0.4**
- Quanto menor a distância, maior a similaridade
- Valores típicos: 0.0 (idêntico) até 1.0+ (muito diferente)

##### **confirmFacialLogin(Request $request)**

Confirma o login após o usuário verificar que é realmente ele:

```php
public function confirmFacialLogin(Request $request)
{
    // 1. Recupera dados da sessão
    $userId = session('facial_match_user_id');
    $matchTimestamp = session('facial_match_timestamp');

    // 2. Verifica timeout (5 minutos)
    if (time() - $matchTimestamp > 300) {
        return response()->json([
            'success' => false,
            'message' => 'Tempo limite excedido'
        ], 400);
    }

    // 3. Faz o login
    $user = User::find($userId);
    Auth::login($user);

    // 4. Atualiza tipo de login
    $user->update([
        'last_login_type' => 'facial',
        'last_login_at' => now(),
    ]);

    // 5. Registra para analytics
    RecognitionRecord::create([
        'user_id' => $user->id,
        'face_descriptor' => json_encode(session('facial_match_descriptor')),
        'capture_type' => 'confirmed_login',
    ]);

    // 6. Limpa sessão
    session()->forget(['facial_match_user_id', ...]);

    return response()->json([
        'success' => true,
        'redirect' => route('dashboard')
    ]);
}
```

---

### 4.2 AttendanceController

**Localização:** `app/Http/Controllers/AttendanceController.php`

**Responsabilidade:** Gerencia registros de ponto (entrada/saída).

#### Constantes:
```php
const DEFAULT_ENTRY_TIME = '14:00';
const DEFAULT_EXIT_TIME = '18:00';
const TOLERANCE_MINUTES = 15;
```

#### Métodos principais:

##### **registerAttendance(Request $request)**

**Fluxo completo de registro de ponto:**

```php
public function registerAttendance(Request $request)
{
    $user = Auth::user();
    $now = Carbon::now();

    // 1. Verifica se é dia permitido
    if (!$this->isAllowedDay($user, $now)) {
        return response()->json([
            'success' => false,
            'message' => 'Registros só permitidos em: segunda a sexta'
        ]);
    }

    // 2. Busca registro existente do dia
    $today = $now->format('Y-m-d');
    $existingRecord = AttendanceRecord::where('user_id', $user->id)
        ->whereDate('created_at', $today)
        ->first();

    // 3. Determina se é entrada ou saída
    $punchType = $existingRecord && $existingRecord->entry_time ? 'exit' : 'entry';

    // 4. Pega horários esperados para o dia
    $times = $this->getTimesForDay($user, $now);
    $expectedTime = $punchType === 'entry' ? $times['entry'] : $times['exit'];
    $expectedDateTime = Carbon::parse($today . ' ' . $expectedTime);

    // 5. Calcula diferença em minutos
    $minutesDifference = $now->diffInMinutes($expectedDateTime, false);
    $isEarly = $minutesDifference > 0;
    $isLate = $minutesDifference < -self::TOLERANCE_MINUTES;

    // 6. Se já existe, atualiza saída
    if ($existingRecord && $existingRecord->entry_time && !$existingRecord->exit_time) {
        $existingRecord->update([
            'exit_time' => $now,
            'punch_type' => 'exit',
            'expected_time' => $expectedDateTime,
            'minutes_difference' => $minutesDifference,
            'is_early' => $isEarly,
            'is_late' => $isLate
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saída registrada com sucesso!',
            'punch_type' => 'exit'
        ]);
    }

    // 7. Senão, cria nova entrada
    AttendanceRecord::create([
        'user_id' => $user->id,
        'entry_time' => $now,
        'status' => 'registered',
        'punch_type' => 'entry',
        'expected_time' => $expectedDateTime,
        'minutes_difference' => $minutesDifference,
        'is_early' => $isEarly,
        'is_late' => $isLate
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Entrada registrada com sucesso!',
        'punch_type' => 'entry'
    ]);
}
```

##### **Métodos auxiliares:**

**isAllowedDay()** - Verifica se o dia é permitido:
```php
private function isAllowedDay($user, Carbon $date)
{
    $dayName = strtolower($date->englishDayOfWeek);

    // Se tem schedule por dia, verifica se o dia está configurado
    if (!empty($user->schedule) && is_array($user->schedule)) {
        return isset($user->schedule[$dayName]);
    }

    // Senão, só permite dias úteis
    return !$date->isWeekend();
}
```

**getTimesForDay()** - Pega horários do dia:
```php
private function getTimesForDay($user, Carbon $date)
{
    $dayName = strtolower($date->englishDayOfWeek);

    if (!empty($user->schedule) && is_array($user->schedule) && isset($user->schedule[$dayName])) {
        return [
            'entry' => $user->schedule[$dayName]['entry'] ?? self::DEFAULT_ENTRY_TIME,
            'exit' => $user->schedule[$dayName]['exit'] ?? self::DEFAULT_EXIT_TIME
        ];
    }

    return [
        'entry' => self::DEFAULT_ENTRY_TIME,
        'exit' => self::DEFAULT_EXIT_TIME
    ];
}
```

##### **verify(Request $request)** - Reconhecimento facial para ponto

Integração com DeepFace API:

```php
public function verify(Request $request)
{
    $imageData = $request->image_data;

    // 1. Valida imagem
    $deepFaceService = new \App\Services\DeepFaceService();
    if (!$deepFaceService->validateImageData($imageData)) {
        return response()->json(['success' => false, 'message' => 'Imagem inválida'], 400);
    }

    // 2. Health check da API DeepFace
    $healthCheck = $deepFaceService->healthCheck();
    if (!$healthCheck['success']) {
        return response()->json([
            'success' => false,
            'message' => 'Serviço de reconhecimento temporariamente indisponível'
        ], 503);
    }

    // 3. Reconhece face
    $recognitionResult = $deepFaceService->recognizeFace($imageData);

    if (!$recognitionResult['success']) {
        return response()->json([
            'success' => false,
            'message' => 'Falha no reconhecimento facial'
        ]);
    }

    // 4. Obtém dados do reconhecimento
    $userId = $recognitionResult['data']['user_id'];
    $confidence = $recognitionResult['data']['confidence'];

    $user = User::find($userId);

    // 5. Verifica threshold de confiança
    if (!$deepFaceService->meetsConfidenceThreshold($confidence)) {
        return response()->json([
            'success' => false,
            'message' => "Confiança insuficiente: {$confidence}%"
        ]);
    }

    // 6. Registra ponto (mesma lógica do registerAttendance)
    // ...

    return response()->json([
        'success' => true,
        'user' => ['id' => $user->id, 'name' => $user->name],
        'confidence' => $confidence,
        'type' => $type  // 'Entrada' ou 'Saída'
    ]);
}
```

---

### 4.3 DashboardController (Aluno)

**Localização:** `app/Http/Controllers/DashboardController.php`

**Responsabilidade:** Dashboard do estudante com estatísticas.

##### **index()**

```php
public function index()
{
    $user = Auth::user();

    // 1. Calcula horas registradas no mês
    $hoursRegistered = $this->calculateHoursRegistered($user->id);

    // 2. Calcula percentual de frequência
    $attendancePercentage = $this->calculateAttendancePercentage($user->id);

    // 3. Calcula próximo horário de registro
    $nextRegisterTime = $this->getNextRegisterTime($user->id);

    // 4. Busca últimos 5 registros
    $recentRecords = AttendanceRecord::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    // 5. Formata schedule para exibição
    $scheduleDisplay = $this->formatScheduleForDisplay($user->schedule);

    return view('aluno.dashboard', compact(
        'hoursRegistered',
        'attendancePercentage',
        'nextRegisterTime',
        'recentRecords',
        'scheduleDisplay'
    ));
}
```

**Cálculo de horas registradas:**
```php
private function calculateHoursRegistered($userId)
{
    $registros = AttendanceRecord::where('user_id', $userId)
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->whereNotNull('entry_time')
        ->whereNotNull('exit_time')
        ->get();

    $totalMinutos = 0;

    foreach ($registros as $registro) {
        $entrada = Carbon::parse($registro->entry_time);
        $saida = Carbon::parse($registro->exit_time);
        $totalMinutos += $entrada->diffInMinutes($saida);
    }

    $horas = floor($totalMinutos / 60);
    $minutos = $totalMinutos % 60;

    return $horas . 'h ' . sprintf('%02d', $minutos) . 'min';
}
```

**Cálculo de percentual de frequência:**
```php
private function calculateAttendancePercentage($userId)
{
    // Dias úteis do mês até hoje
    $diasUteis = $this->getBusinessDaysInMonth();

    // Dias com registro
    $diasComRegistro = AttendanceRecord::where('user_id', $userId)
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->whereNotNull('entry_time')
        ->selectRaw('DATE(created_at) as attendance_date')
        ->groupBy('attendance_date')
        ->get()
        ->count();

    $percentual = ($diasUteis > 0) ? round(($diasComRegistro / $diasUteis) * 100) : 0;

    return $percentual . '%';
}
```

---

### 4.4 Admin\DashboardController

**Localização:** `app/Http/Controllers/Admin/DashboardController.php`

**Responsabilidade:** Dashboard administrativo e geração de relatórios.

##### **generateReport(Request $request)**

**Fluxo completo de geração de PDF:**

```php
public function generateReport(Request $request)
{
    // 1. Valida parâmetros
    $request->validate([
        'report_type' => 'required|in:attendance,user',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'filter_by' => 'nullable|string',
        'report_period' => 'required|in:daily,weekly,monthly',
        'user_id' => 'nullable|exists:users,id',
    ]);

    // 2. Define datas baseadas no período
    if (!$request->start_date || !$request->end_date) {
        $dates = $this->getDateRange($request->report_period);
        $startDate = $dates['start'];
        $endDate = $dates['end'];
    }

    // 3. Busca dados
    $data = $this->getReportData(
        $request->report_type,
        $startDate,
        $endDate,
        $request->filter_by,
        $request->user_id
    );

    // 4. Gera PDF
    $pdf = $this->generatePdfByPeriod(
        $request->report_period,
        $request->report_type,
        $data,
        $startDate,
        $endDate,
        $request->filter_by,
        $request->user_id
    );

    // 5. Gera nome do arquivo
    $filename = $this->generateFilename(
        $request->report_period,
        $request->report_type,
        $startDate,
        $endDate
    );

    // 6. Retorna download
    return $pdf->download($filename);
}
```

**getReportData() - Busca e processa dados:**
```php
private function getReportData($reportType, $startDate, $endDate, $filterBy, $userId = null)
{
    // 1. Query base
    $query = AttendanceRecord::with('user')
        ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

    // 2. Filtros
    if ($reportType === 'user' && $userId) {
        $query->where('user_id', $userId);
    } elseif ($filterBy) {
        $query->whereHas('user', function($q) use ($filterBy) {
            $q->where('curso', $filterBy);
        });
    }

    $attendances = $query->orderBy('created_at', 'desc')->get();

    // 3. Estatísticas
    $totalStudents = User::where('role', 'aluno')
        ->when($filterBy, function($q) use ($filterBy) {
            return $q->where('curso', $filterBy);
        })->count();

    $uniqueStudents = $attendances->pluck('user_id')->unique()->count();

    // 4. Calcula horas totais
    $totalMinutes = 0;
    foreach ($attendances as $attendance) {
        if ($attendance->entry_time && $attendance->exit_time) {
            $entrada = Carbon::parse($attendance->entry_time);
            $saida = Carbon::parse($attendance->exit_time);
            $totalMinutes += $entrada->diffInMinutes($saida);
        }
    }

    $totalHours = floor($totalMinutes / 60);
    $remainingMinutes = $totalMinutes % 60;
    $totalHoursFormatted = sprintf('%02dh%02dm', $totalHours, $remainingMinutes);

    // 5. Conta atrasos
    $lateCount = $attendances->where('is_late', true)->count();

    return [
        'attendances' => $attendances,
        'total_students' => $totalStudents,
        'unique_students' => $uniqueStudents,
        'total_records' => $attendances->count(),
        'attendance_rate' => $totalStudents > 0 ? round(($uniqueStudents / $totalStudents) * 100, 2) : 0,
        'selected_user' => $userId ? User::find($userId) : null,
        'total_hours' => $totalHoursFormatted,
        'late_count' => $lateCount,
    ];
}
```

---

## 5. Integração com DomPDF

### 5.1 Configuração

**Localização:** `config/dompdf.php`

**Parâmetros principais:**
```php
'options' => [
    'font_dir' => storage_path('fonts'),
    'font_cache' => storage_path('fonts'),
    'temp_dir' => sys_get_temp_dir(),
    'chroot' => realpath(base_path()),
    'default_paper_size' => 'a4',
    'default_paper_orientation' => 'portrait',
    'default_font' => 'serif',
    'dpi' => 96,
    'enable_php' => false,
    'enable_javascript' => true,
    'enable_remote' => false,
]
```

### 5.2 Geração de PDF

**No Controller:**
```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = PDF::loadView('admin.reports.pdf-template', $viewData)
    ->setPaper('a4', 'portrait')
    ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true
    ]);

return $pdf->download('relatorio.pdf');
```

### 5.3 Template PDF

**Localização:** `resources/views/admin/reports/pdf-template.blade.php`

**Estrutura do template:**
```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f08223;
            padding-bottom: 20px;
        }
        .statistics {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table th {
            background-color: #f08223;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="institution">UNIFIL - Centro Universitário Filadélfia</div>
        <h1>{{ $title }}</h1>
    </div>

    <div class="report-info">
        <p>{{ $description }}</p>
        <p><strong>Período:</strong> {{ $start_date }} até {{ $end_date }}</p>
        <p><strong>Curso:</strong> {{ $course }}</p>
    </div>

    <div class="statistics">
        <h3>Estatísticas do Período</h3>
        <p>Total de Estudantes: {{ $data['total_students'] }}</p>
        <p>Taxa de Presença: {{ $data['attendance_rate'] }}%</p>
        <p>Total de Horas: {{ $data['total_hours'] }}</p>
        <p>Registros com Atraso: {{ $data['late_count'] }}</p>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Nome</th>
                <th>Matrícula</th>
                <th>Entrada</th>
                <th>Saída</th>
                <th>Horas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['attendances'] as $attendance)
                <tr>
                    <td>{{ $attendance->created_at->format('d/m/Y') }}</td>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->user->matricula }}</td>
                    <td>{{ $attendance->entry_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $attendance->exit_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $horasTrabalhadas }}</td>
                    <td>{{ $status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Documento gerado automaticamente pelo Sistema de Controle de Ponto UNIFIL</p>
    </div>
</body>
</html>
```

**Fontes suportadas:**
- DejaVu Sans (padrão - suporta UTF-8)
- Helvetica
- Times Roman
- Courier

---

## 6. Rotas e API

### 6.1 Rotas Web

**Localização:** `routes/web.php`

#### Rotas Públicas:
```php
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

#### Rotas de Reconhecimento Facial:
```php
Route::post('/facial-login', [AuthController::class, 'facialLogin']);
Route::post('/facial-login/confirm', [AuthController::class, 'confirmFacialLogin']);
Route::post('/facial-login/reject', [AuthController::class, 'rejectFacialLogin']);
```

#### Rotas de Aluno (Protegidas: auth + StudentMiddleware):
```php
Route::middleware(['auth', 'student'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [DashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/facial-registration', [RecognitionController::class, 'create'])->name('facial-registration');
    Route::post('/facial-registration', [RecognitionController::class, 'store']);

    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/register-attendance', [AttendanceController::class, 'registerAttendance'])->name('attendance.register');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])
        ->middleware('check.email.login')
        ->name('attendance.history');
});
```

#### Rotas de Admin:
```php
Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{id}/edit', [Admin\UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{id}', [Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{id}/restore', [Admin\UserController::class, 'restore'])->name('admin.users.restore');
    Route::delete('/users/{id}/force', [Admin\UserController::class, 'forceDestroy'])->name('admin.users.force');

    Route::get('/reports', [Admin\DashboardController::class, 'reports'])->name('admin.reports');
    Route::post('/reports/generate', [Admin\DashboardController::class, 'generateReport'])->name('admin.reports.generate');
});
```

### 6.2 Rotas API

**Localização:** `routes/api.php`

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

// Rota protegida por Sanctum
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rotas de Ponto (API pública para chamadas do frontend)
Route::prefix('attendance')->group(function () {
    Route::post('/verify', [AttendanceController::class, 'verify']);
    Route::get('/status', [AttendanceController::class, 'status']);
});
```

**Exemplo de uso da API:**

**POST /api/attendance/verify**
```javascript
fetch('/api/attendance/verify', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        image_data: base64ImageData
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Ponto registrado:', data.type);
        console.log('Confiança:', data.confidence);
    }
});
```

**GET /api/attendance/status**
```javascript
fetch('/api/attendance/status')
    .then(response => response.json())
    .then(data => {
        console.log('Próximo tipo:', data.next_punch_type);
        console.log('Horário esperado:', data.expected_time);
    });
```

---

## 7. Banco de Dados

### 7.1 Diagrama de Relacionamentos (ER)

```
┌─────────────────────────┐
│        users            │
├─────────────────────────┤
│ id (PK)                 │
│ name                    │
│ email (unique)          │
│ password                │
│ matricula (unique)      │
│ curso                   │
│ role                    │
│ schedule (JSON)         │
│ last_login_type         │
│ last_login_at           │
│ deleted_at              │
│ created_at              │
│ updated_at              │
└───────────┬─────────────┘
            │
            │ 1:N
            │
      ┌─────┴────────┬──────────────┐
      │              │              │
      ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ attendance_  │ │ recognition_ │ │ personal_    │
│ records      │ │ records      │ │ access_      │
│              │ │              │ │ tokens       │
├──────────────┤ ├──────────────┤ ├──────────────┤
│ id (PK)      │ │ id (PK)      │ │ id (PK)      │
│ user_id (FK) │ │ user_id (FK) │ │ tokenable_id │
│ entry_time   │ │ face_descrip │ │ name         │
│ exit_time    │ │ image_path   │ │ token        │
│ is_early     │ │ capture_type │ │ abilities    │
│ is_late      │ │ created_at   │ │ last_used_at │
│ expected_time│ │ updated_at   │ │ expires_at   │
│ minutes_diff │ └──────────────┘ └──────────────┘
│ punch_type   │
│ status       │
│ created_at   │
│ updated_at   │
└──────────────┘
```

### 7.2 Tabelas Principais

#### **users**
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    matricula VARCHAR(9) UNIQUE NOT NULL,
    curso VARCHAR(255) NOT NULL,
    role ENUM('aluno', 'admin') DEFAULT 'aluno',
    schedule JSON NULL,
    last_login_type VARCHAR(50) NULL,
    last_login_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_matricula (matricula),
    INDEX idx_role (role),
    INDEX idx_deleted_at (deleted_at)
);
```

#### **attendance_records**
```sql
CREATE TABLE attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    entry_time DATETIME NULL,
    exit_time DATETIME NULL,
    status VARCHAR(50) DEFAULT 'registered',
    is_early BOOLEAN DEFAULT 0,
    is_late BOOLEAN DEFAULT 0,
    expected_time TIME NULL,
    minutes_difference INT NULL,
    punch_type VARCHAR(50) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_late (is_late)
);
```

#### **recognition_records**
```sql
CREATE TABLE recognition_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    face_descriptor LONGTEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    capture_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_capture_type (capture_type)
);
```

### 7.3 Migrações

**Total:** 22 arquivos de migração

**Principais migrações:**

1. **0001_01_01_000000_create_users_table.php**
   - Cria tabela `users`, `password_reset_tokens`, `sessions`

2. **2025_04_18_134712_create_attendance_records_table.php**
   - Cria tabela inicial de registros de ponto

3. **2025_05_09_200634_create_recognition_records_table.php**
   - Cria tabela de descritores faciais

4. **2025_10_13_170513_add_schedule_to_users_table.php**
   - Adiciona campo `schedule` (JSON) para horários por dia

5. **2025_10_13_193637_add_soft_deletes_to_users_table.php**
   - Adiciona `deleted_at` para soft delete

**Executar migrações:**
```bash
php artisan migrate
```

**Rollback:**
```bash
php artisan migrate:rollback
```

**Reset completo:**
```bash
php artisan migrate:fresh --seed
```

---

## 8. Middlewares

### 8.1 StudentMiddleware

**Localização:** `app/Http/Middleware/StudentMiddleware.php`

**Propósito:** Garantir que apenas alunos acessem rotas de aluno.

```php
public function handle(Request $request, Closure $next)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    if (Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return $next($request);
}
```

### 8.2 AdminMiddleware

**Localização:** `app/Http/Middleware/AdminMiddleware.php`

**Propósito:** Garantir que apenas administradores acessem rotas admin.

```php
public function handle(Request $request, Closure $next)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    if (Auth::user()->role !== 'admin') {
        return redirect()->route('dashboard');
    }

    return $next($request);
}
```

### 8.3 CheckEmailLogin

**Localização:** `app/Http/Middleware/CheckEmailLogin.php`

**Propósito:** Bloquear acesso ao histórico para usuários que fizeram login facial.

```php
public function handle(Request $request, Closure $next)
{
    if (Auth::check() && Auth::user()->last_login_type !== 'email') {
        return redirect()->route('dashboard')->with('error',
            'O histórico só está disponível para login via email/senha.');
    }

    return $next($request);
}
```

**Registro no Kernel:**
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'student' => \App\Http\Middleware\StudentMiddleware::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'check.email.login' => \App\Http\Middleware\CheckEmailLogin::class,
];
```

---

## 9. Funcionalidades Principais

### 9.1 Cadastro de Usuário

**Fluxo:**
1. Usuário acessa `/register`
2. Preenche: nome, email (@edu.unifil.br), matrícula (9 dígitos), curso, senha
3. Sistema captura 3 fotos faciais usando face-api.js
4. Frontend extrai 3 descritores de 128 dimensões
5. Backend valida dados e cria usuário
6. Backend salva 3 `RecognitionRecord` com `capture_type` = `registration_1`, `registration_2`, `registration_3`
7. Usuário é autenticado automaticamente

**Validações:**
- Email: deve terminar com `@edu.unifil.br`
- Matrícula: exatamente 9 dígitos numéricos
- Curso: deve ser "Ciencia da Computacao" ou "Engenharia de Software"
- Senha: mínimo 8 caracteres, confirmação obrigatória
- Face: 3 descritores obrigatórios

### 9.2 Login via Email/Senha

**Fluxo:**
1. Usuário acessa `/login`
2. Insere email e senha
3. Laravel valida com `Auth::attempt()`
4. Se válido: atualiza `last_login_type = 'email'` e `last_login_at = now()`
5. Redireciona para dashboard apropriado (admin ou aluno)

### 9.3 Login via Reconhecimento Facial

**Fluxo completo:**

```
┌─────────────────┐
│  1. Usuário     │
│  clica em       │
│  "Login Facial" │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│  2. Camera ativa            │
│  face-api.js carrega models │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│  3. Detecta rosto           │
│  Extrai descritor (128-dim) │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│  4. POST /facial-login      │
│  Envia {face_descriptor: []}│
└────────┬────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  5. AuthController::facialLogin()    │
│  - Busca todos users com recognition │
│  - Calcula distância euclidiana      │
│  - Threshold: 0.4                    │
│  - Encontra melhor match             │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  6. Se match encontrado:             │
│  - Salva na sessão (timeout 5min)    │
│  - Retorna nome do usuário           │
│  - Exibe tela de confirmação         │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  7. Usuário confirma "Sim, sou eu"   │
│  POST /facial-login/confirm          │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  8. AuthController::confirmFacialLogin│
│  - Valida sessão                     │
│  - Auth::login($user)                │
│  - Atualiza last_login_type='facial' │
│  - Cria RecognitionRecord analytics  │
│  - Redireciona para dashboard        │
└──────────────────────────────────────┘
```

**Segurança:**
- Confirmação obrigatória do usuário
- Timeout de 5 minutos na sessão
- Logging de todas as tentativas
- Opção de rejeitar match incorreto

### 9.4 Registro de Ponto Manual

**Fluxo:**
1. Aluno autenticado acessa `/attendance/create`
2. Clica em botão "Registrar Ponto"
3. JavaScript chama `POST /register-attendance`
4. `AttendanceController::registerAttendance()`:
   - Verifica se é dia permitido
   - Busca registro existente do dia
   - Se não existe: cria entrada
   - Se existe entrada sem saída: atualiza saída
   - Se existe entrada e saída: retorna erro
   - Calcula `is_early`, `is_late`, `minutes_difference`
5. Retorna JSON com sucesso/erro
6. Frontend atualiza interface

**Exemplo de resposta:**
```json
{
  "success": true,
  "message": "Entrada registrada com sucesso!",
  "punch_type": "entry",
  "time": "14:05",
  "is_early": false,
  "is_late": false
}
```

### 9.5 Registro de Ponto via Reconhecimento Facial

**Fluxo:**
1. Aluno acessa `/attendance/create`
2. Clica em "Registrar via Reconhecimento Facial"
3. Camera ativa, captura imagem
4. Envia `POST /api/attendance/verify` com `image_data` (base64)
5. `AttendanceController::verify()`:
   - Valida imagem
   - Chama DeepFace API
   - Verifica confiança (threshold configurável)
   - Identifica usuário
   - Registra ponto (mesma lógica do manual)
6. Retorna sucesso com nome do usuário e tipo (Entrada/Saída)

**Integração DeepFace:**
```php
$deepFaceService = new \App\Services\DeepFaceService();

// Health check
$health = $deepFaceService->healthCheck();

// Reconhecer face
$result = $deepFaceService->recognizeFace($imageData);

// Verificar confiança
if ($deepFaceService->meetsConfidenceThreshold($result['confidence'])) {
    // Registra ponto
}
```

### 9.6 Histórico de Ponto

**Funcionalidade:**
- Acesso restrito a login via email (`CheckEmailLogin` middleware)
- Filtro por data (início e fim)
- Paginação (10 registros por página)
- Exibe: data, entrada, saída, horas trabalhadas, status

**View:** `resources/views/aluno/historico.blade.php`

**Controller:**
```php
public function history(Request $request)
{
    $user = Auth::user();

    $query = AttendanceRecord::where('user_id', $user->id);

    if ($request->start_date) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    if ($request->end_date) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    $attendances = $query->orderBy('created_at', 'desc')->paginate(10);

    return view('aluno.historico', compact('attendances'));
}
```

### 9.7 Geração de Relatórios (Admin)

**Tipos de relatório:**
- **Por período:** Diário, Semanal, Mensal
- **Por curso:** Todos, Ciência da Computação, Engenharia de Software
- **Por aluno:** Relatório individual

**Dados incluídos:**
- Total de estudantes
- Estudantes presentes
- Total de registros
- Taxa de presença (%)
- Total de horas trabalhadas
- Registros com atraso
- Tabela detalhada: data, nome, matrícula, entrada, saída, horas, status

**Formato:** PDF gerado com DomPDF

**Processo:**
1. Admin acessa `/admin/reports`
2. Seleciona parâmetros (período, curso, aluno)
3. Clica "Gerar Relatório"
4. `POST /admin/reports/generate`
5. Backend busca dados, processa estatísticas
6. Renderiza template Blade com dados
7. DomPDF converte HTML para PDF
8. Download automático do arquivo

---

## 10. Segurança

### 10.1 Autenticação
- Senhas criptografadas com bcrypt (12 rounds)
- Sessões regeneradas a cada login
- CSRF protection em todos os formulários
- Sanctum para API tokens

### 10.2 Autorização
- Middleware para controle de acesso
- Soft delete para preservar dados
- Admin principal protegido contra edição/exclusão

### 10.3 Validação de Dados

**Email:**
```php
'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@edu\.unifil\.br$/'
```

**Matrícula:**
```php
'matricula' => 'required|string|size:9|regex:/^[0-9]{9}$/|unique:users'
```

**Senha:**
```php
'password' => 'required|string|min:8|confirmed'
```

### 10.4 Proteção de Reconhecimento Facial
- Threshold ajustável (padrão 0.4)
- Confirmação obrigatória do usuário
- Timeout de sessão (5 minutos)
- Logging de tentativas
- Validação de descritores (128 dimensões, valores numéricos)

### 10.5 Proteção DomPDF
```php
'enable_php' => false,           // Desabilita PHP embarcado
'enable_remote' => false,        // Bloqueia recursos remotos
'chroot' => realpath(base_path()), // Restringe acesso a arquivos
```

---

## 11. Variáveis de Ambiente (.env)

```env
# Aplicação
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=America/Sao_Paulo

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facial_recognition
DB_USERNAME=root
DB_PASSWORD=975386

# Cache e Sessão
SESSION_DRIVER=file
CACHE_STORE=database
QUEUE_CONNECTION=database

# DeepFace API
DEEPFACE_API_URL=http://localhost:5001
DEEPFACE_TIMEOUT=30
DEEPFACE_CONFIDENCE_THRESHOLD=75
DEEPFACE_MODEL=Facenet512
DEEPFACE_DETECTOR=opencv

# Credenciais Admin
ADMIN_EMAIL=joao.andrade@unifil.br
ADMIN_PASSWORD=Admin@2025!UniFil
ADMIN_MATRICULA=000000001
```

---

## 12. Deployment e Instalação

### 12.1 Requisitos
- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- Node.js 18+ (para assets)
- Python 3.9+ (para DeepFace API)

### 12.2 Instalação Passo a Passo

**1. Clone o repositório:**
```bash
git clone <repository-url>
cd facepoint-unifil
```

**2. Instale dependências PHP:**
```bash
composer install
```

**3. Configure .env:**
```bash
cp .env.example .env
php artisan key:generate
```

**4. Configure banco de dados:**
Edite `.env` com suas credenciais MySQL:
```env
DB_DATABASE=facial_recognition
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

**5. Execute migrações:**
```bash
php artisan migrate
```

**6. Execute seeders (opcional):**
```bash
php artisan db:seed --class=AdminUserSeeder
```

**7. Configure storage:**
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

**8. Instale DeepFace API (em outro terminal):**
```bash
pip install deepface flask flask-cors
# Configure e inicie o serviço na porta 5001
```

**9. Inicie servidor de desenvolvimento:**
```bash
php artisan serve
```

**10. Acesse:**
```
http://localhost:8000
```

### 12.3 Produção

**1. Otimizações:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

**2. Permissões:**
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**3. Configuração Apache/Nginx:**
```nginx
server {
    listen 80;
    server_name facepoint.unifil.br;
    root /var/www/facepoint-unifil/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## 13. Manutenção e Troubleshooting

### 13.1 Logs
```bash
# Logs da aplicação
tail -f storage/logs/laravel.log

# Limpar logs
> storage/logs/laravel.log
```

### 13.2 Cache
```bash
# Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 13.3 Problemas Comuns

**Erro: "No face detected"**
- Verificar iluminação
- Verificar modelos do face-api.js em `public/models/`
- Verificar permissão de camera no navegador

**Erro: "DeepFace API unavailable"**
- Verificar se serviço está rodando: `curl http://localhost:5001/health`
- Verificar configuração em `.env`

**Erro de permissão em storage/**
```bash
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

**PDF não gera corretamente:**
- Verificar fontes em `storage/fonts/`
- Verificar permissões
- Desabilitar `enable_remote` se houver problemas de segurança

---

## 14. Conclusão

O **FacePoint UniFil** é um sistema completo e moderno de controle de ponto que combina tecnologias web tradicionais (Laravel, MySQL) com inovações em reconhecimento facial (face-api.js, DeepFace), oferecendo:

✅ **Segurança robusta** - Autenticação dupla, validações rigorosas, soft delete
✅ **Flexibilidade** - Horários por dia, múltiplos cursos, relatórios customizados
✅ **Usabilidade** - Interface responsiva, reconhecimento facial rápido, dashboard intuitivo
✅ **Rastreabilidade** - Logs completos, histórico detalhado, estatísticas precisas
✅ **Escalabilidade** - Arquitetura MVC, API REST, cache configurável

O sistema está pronto para produção e pode ser expandido com:
- Notificações por email
- Integração com sistemas acadêmicos
- Dashboard com gráficos interativos
- Aplicativo mobile
- Exportação de dados em múltiplos formatos

---

**Documento gerado em:** 24 de outubro de 2025
**Versão do sistema:** 1.0
**Framework:** Laravel 11.x
**Desenvolvido para:** UniFil - Centro Universitário Filadélfia
