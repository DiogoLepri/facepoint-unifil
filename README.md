# FacePoint UniFil

Sistema de Ponto Eletrônico com Reconhecimento Facial para UniFil

## Sobre o Projeto

O FacePoint UniFil é um sistema moderno de controle de ponto desenvolvido especificamente para estudantes dos cursos de Ciência da Computação e Engenharia de Software da UniFil. O sistema combina tecnologias tradicionais de autenticação (email/senha) com reconhecimento facial avançado usando face-api.js.

## Funcionalidades

### Para Alunos
- Login com email/senha ou reconhecimento facial
- Registro de ponto com detecção automática de entrada/saída
- Dashboard com histórico de registros
- Visualização de horas trabalhadas
- Perfil com edição de dados pessoais
- Registro de face para reconhecimento

### Para Administradores
- Dashboard completo com estatísticas
- Gerenciamento de usuários (criar, editar, inativar, reativar)
- Geração de relatórios (diário, semanal, mensal)
- Filtros por curso e aluno
- Exportação de relatórios em PDF
- Controle de soft delete (inativar/reativar usuários)

## Tecnologias

- **Backend**: Laravel 11 (PHP 8.2+)
- **Banco de Dados**: MySQL
- **Frontend**: Bootstrap 5, JavaScript
- **Reconhecimento Facial**: face-api.js (128-dimension descriptors)
- **Geração de PDF**: DomPDF

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- NPM (opcional para build de assets)

## Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/facepoint-unifil.git
   cd facepoint-unifil
   ```

2. Instale dependências PHP:
   ```bash
   composer install
   ```

3. Configure o ambiente:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure o banco de dados no arquivo `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=facepoint_unifil
   DB_USERNAME=root
   DB_PASSWORD=sua_senha
   ```

5. Execute as migrations e seeders:
   ```bash
   php artisan migrate
   php artisan db:seed --class=AdminUserSeeder
   ```

6. Configure as credenciais do administrador no `.env`:
   ```env
   ADMIN_NAME="Administrator"
   ADMIN_EMAIL="admin@facepoint.com"
   ADMIN_PASSWORD="admin123"
   ADMIN_MATRICULA="000000000"
   ```

7. Crie o link simbólico para storage:
   ```bash
   php artisan storage:link
   ```

## Execução

Inicie o servidor Laravel:

```bash
php artisan serve
```

Acesse no navegador: `http://localhost:8000`

### Credenciais Padrão

**Administrador:**
- Email: admin@facepoint.com
- Senha: admin123

## Estrutura do Projeto

```
facepoint-unifil/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php  # Relatórios e dashboard
│   │   │   └── UserController.php       # Gerenciamento de usuários
│   │   ├── AuthController.php           # Autenticação
│   │   ├── AttendanceController.php     # Registro de ponto
│   │   └── RecognitionController.php    # Reconhecimento facial
│   └── Models/
│       ├── User.php                     # Model de usuários (com SoftDeletes)
│       ├── AttendanceRecord.php         # Model de registros de ponto
│       └── RecognitionRecord.php        # Model de dados faciais
├── database/
│   ├── migrations/                      # Migrations do banco
│   └── seeders/
│       └── AdminUserSeeder.php          # Seed do admin
├── resources/
│   └── views/
│       ├── admin/                       # Views do admin
│       ├── aluno/                       # Views do aluno
│       └── auth/                        # Views de autenticação
└── routes/
    ├── web.php                          # Rotas web
    └── api.php                          # Rotas API
```

## Documentação

Para documentação completa do sistema, consulte:
- `/docs/GUIA-COMPLETO-SISTEMA.md` - Guia completo do sistema
- `/docs/diagrama-sequencia-*.md` - Diagramas de sequência dos casos de uso

## Principais Casos de Uso

1. **Autenticar Usuário** - Login com email/senha ou reconhecimento facial
2. **Registrar Ponto** - Entrada/saída automática com validação de horários
3. **Gerar Relatórios** - Relatórios filtrados por período, curso e aluno
4. **Gerenciar Usuários** - CRUD completo com soft delete

## Comandos Úteis

```bash
# Limpar caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Recriar admin
php artisan db:seed --class=AdminUserSeeder

# Ver status das migrations
php artisan migrate:status

# Ver rotas disponíveis
php artisan route:list
```

## Segurança

- Senhas criptografadas com bcrypt
- Proteção CSRF em todos os formulários
- Validação de dados em todas as requisições
- Soft delete para preservação de dados
- Controle de acesso por middleware
- Descriptores faciais armazenados de forma segura

## Troubleshooting

**Problema**: Erro ao fazer upload de imagens
**Solução**: Verifique permissões da pasta storage e execute `php artisan storage:link`

**Problema**: Credenciais de admin não funcionam
**Solução**: Execute `php artisan db:seed --class=AdminUserSeeder`

**Problema**: Reconhecimento facial não funciona
**Solução**: Verifique se os models do face-api.js estão carregando corretamente em `public/models/`

## Contribuição

Este projeto foi desenvolvido como trabalho acadêmico para a UniFil.

## Licença

Copyright © 2025 UniFil. Todos os direitos reservados.
