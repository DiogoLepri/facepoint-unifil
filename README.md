# FacePoint UniFil

Sistema de Ponto Eletrônico com Reconhecimento Facial para UniFil

## Sobre o Projeto

O FacePoint UniFil é um sistema moderno de controle de ponto desenvolvido especificamente para estudantes dos cursos de Ciência da Computação e Engenharia de Software da UniFil. O sistema combina tecnologias tradicionais de autenticação (email/senha) com reconhecimento facial avançado usando inteligência artificial.

## Tecnologias

- Backend: Laravel 12 (PHP 8.2+)
- Banco de Dados: SQLite
- Frontend: Bootstrap 5, JavaScript
- IA: Python 3.x, DeepFace, OpenCV
- API: Flask (Python)

## Requisitos

- PHP 8.2+
- Composer
- Python 3.x
- NPM
- SQLite

## Instalação

1. Clone o repositório
2. Instale dependências PHP:
   ```bash
   composer install
   ```
3. Configure o ambiente:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Configure o banco de dados:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
5. Instale dependências Python:
   ```bash
   pip install -r requirements.txt
   ```

## Execução

Use o script de inicialização para iniciar todos os serviços:

```bash
./start_servers.sh
```

Ou inicie manualmente:

```bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - DeepFace API
python deepface_server.py
```

## Licença

Copyright © 2025 UniFil. Todos os direitos reservados.
