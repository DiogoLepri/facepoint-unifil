# Guia de Deploy - FacePoint Unifil

Este guia explica como fazer o deploy da aplicação FacePoint Unifil em um servidor Ubuntu.

## Requisitos

- Ubuntu 20.04, 22.04 ou 24.04 LTS
- Acesso root ou sudo
- Mínimo 2GB RAM
- Mínimo 20GB de espaço em disco
- Conexão com internet

## Arquitetura da Aplicação

O sistema FacePoint Unifil é composto por:

1. **Laravel 12** (Backend PHP + Frontend)
   - PHP 8.2
   - MySQL
   - Nginx
   - Queue Workers (Supervisor)

2. **DeepFace API** (Reconhecimento Facial)
   - Python 3
   - Flask
   - DeepFace library
   - Supervisor

## Instalação Rápida

### 1. Preparar o servidor

```bash
# Atualize o sistema
sudo apt update && sudo apt upgrade -y

# Clone ou copie o projeto para o servidor
git clone <seu-repositorio> /var/www/facepoint-unifil
# OU
scp -r ./facepoint-unifil usuario@servidor:/var/www/
```

### 2. Configurar variáveis de ambiente

```bash
# Defina a senha do banco de dados
export DB_PASSWORD='SuaSenhaSegura123'

# (Opcional) Personalize outras configurações
export DEPLOY_PATH='/var/www/facepoint-unifil'
export DOMAIN='facepoint.example.com'
export DB_NAME='facepoint'
export DB_USER='facepoint_user'
```

### 3. Executar o script de deploy

```bash
cd /var/www/facepoint-unifil
sudo ./deploy.sh
```

O script apresentará um menu interativo:

```
1. Instalação completa (primeira vez)
2. Atualização da aplicação
3. Instalar apenas dependências do sistema
4. Configurar apenas Laravel
5. Configurar apenas DeepFace API
6. Rebuild de assets
7. Sair
```

Escolha a opção `1` para instalação completa.

### 4. Instalação não-interativa

Para instalação automatizada (CI/CD):

```bash
sudo DB_PASSWORD='SuaSenhaSegura123' ./deploy.sh --full
```

## O que o script faz

### Instalação do Sistema

1. Instala dependências básicas (curl, git, supervisor, nginx, etc)
2. Adiciona repositório PHP e instala PHP 8.2 + extensões
3. Instala Composer
4. Instala Node.js 20.x LTS
5. Instala MySQL Server
6. Configura firewall UFW

### Configuração do Banco de Dados

1. Cria banco de dados MySQL
2. Cria usuário com privilégios
3. Configura charset UTF-8

### Deploy da Aplicação Laravel

1. Copia/atualiza código da aplicação
2. Instala dependências do Composer
3. Gera chave da aplicação (APP_KEY)
4. Configura permissões de arquivos
5. Executa migrations
6. Faz cache de configurações, rotas e views
7. Compila assets com Vite

### Configuração do DeepFace API

1. Cria ambiente virtual Python
2. Instala dependências Python (DeepFace, Flask, etc)
3. Configura Supervisor para manter API rodando
4. Cria diretórios necessários

### Configuração do Nginx

1. Cria virtual host para a aplicação
2. Configura proxy reverso para DeepFace API
3. Define timeouts apropriados para upload de imagens
4. Ativa site e reinicia Nginx

### Configuração de Serviços

1. **Laravel Queue Workers**: Processa filas em background
2. **DeepFace API**: Mantém API de reconhecimento facial ativa
3. **Laravel Scheduler**: Configura cron para tarefas agendadas

## Configuração Pós-Instalação

### 1. Configurar .env

Edite o arquivo de configuração:

```bash
sudo nano /var/www/facepoint-unifil/.env
```

Configure as variáveis importantes:

```ini
APP_NAME="FacePoint Unifil"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://seu-dominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facepoint
DB_USERNAME=facepoint_user
DB_PASSWORD=SuaSenhaSegura123

# Configuração de e-mail (opcional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@facepoint.com
MAIL_FROM_NAME="${APP_NAME}"

# URL da API DeepFace
DEEPFACE_API_URL=http://localhost:5000
```

Após editar, limpe o cache:

```bash
cd /var/www/facepoint-unifil
sudo php artisan config:cache
```

### 2. Configurar DNS

Aponte seu domínio para o IP do servidor:

```
A    @              seu-ip-servidor
A    www            seu-ip-servidor
```

Ou, para testes locais, edite `/etc/hosts`:

```bash
echo "127.0.0.1 facepoint.local" | sudo tee -a /etc/hosts
```

### 3. Configurar SSL (Recomendado)

Instale e configure Let's Encrypt:

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obter certificado SSL
sudo certbot --nginx -d seu-dominio.com -d www.seu-dominio.com

# Testar renovação automática
sudo certbot renew --dry-run
```

### 4. Criar usuário administrador

```bash
cd /var/www/facepoint-unifil
sudo -u www-data php artisan tinker
```

No console do Tinker:

```php
$user = new App\Models\User();
$user->name = 'Administrador';
$user->email = 'admin@facepoint.com';
$user->password = Hash::make('senha123');
$user->save();
```

## Estrutura de Diretórios

```
/var/www/facepoint-unifil/
├── app/                    # Código da aplicação Laravel
├── bootstrap/              # Arquivos de inicialização
├── config/                 # Arquivos de configuração
├── database/               # Migrations e seeders
├── deepface-api/           # API Python DeepFace
│   ├── app.py             # Aplicação Flask
│   ├── requirements.txt   # Dependências Python
│   ├── venv/              # Ambiente virtual Python
│   ├── faces_db/          # Banco de faces cadastradas
│   └── logs/              # Logs da API
├── public/                 # Ponto de entrada web
├── resources/              # Views, assets
├── routes/                 # Rotas da aplicação
├── storage/                # Uploads, logs, cache
│   ├── app/
│   ├── framework/
│   └── logs/
└── vendor/                 # Dependências Composer
```

## Gerenciamento de Serviços

### Verificar status

```bash
# Todos os serviços do Supervisor
sudo supervisorctl status

# Nginx
sudo systemctl status nginx

# PHP-FPM
sudo systemctl status php8.2-fpm

# MySQL
sudo systemctl status mysql
```

### Reiniciar serviços

```bash
# Laravel Queue Workers
sudo supervisorctl restart laravel-worker:*

# DeepFace API
sudo supervisorctl restart deepface-api

# Nginx
sudo systemctl restart nginx

# PHP-FPM
sudo systemctl restart php8.2-fpm

# Todos os serviços
sudo supervisorctl restart all
```

### Ver logs

```bash
# Laravel
sudo tail -f /var/www/facepoint-unifil/storage/logs/laravel.log

# DeepFace API
sudo tail -f /var/www/facepoint-unifil/deepface-api/logs/supervisor.log

# Nginx - Acesso
sudo tail -f /var/log/nginx/access.log

# Nginx - Erros
sudo tail -f /var/log/nginx/error.log

# PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Queue Workers
sudo tail -f /var/www/facepoint-unifil/storage/logs/worker.log
```

## Atualizações

### Atualizar código da aplicação

```bash
cd /var/www/facepoint-unifil

# Fazer backup do banco de dados
sudo mysqldump -u root facepoint > backup-$(date +%Y%m%d).sql

# Modo manutenção
sudo -u www-data php artisan down

# Atualizar código
sudo git pull

# Atualizar dependências
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo npm ci
sudo npm run build

# Executar migrations
sudo -u www-data php artisan migrate --force

# Limpar e recriar cache
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Reiniciar serviços
sudo supervisorctl restart all
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

# Sair do modo manutenção
sudo -u www-data php artisan up
```

Ou use o menu do script:

```bash
sudo ./deploy.sh
# Escolha opção 2 - Atualização da aplicação
```

## Backup

### Backup do banco de dados

```bash
# Backup completo
sudo mysqldump -u root facepoint > backup-facepoint-$(date +%Y%m%d-%H%M%S).sql

# Backup com compressão
sudo mysqldump -u root facepoint | gzip > backup-facepoint-$(date +%Y%m%d-%H%M%S).sql.gz
```

### Backup dos arquivos

```bash
# Backup do diretório storage (uploads, logs)
sudo tar -czf storage-backup-$(date +%Y%m%d-%H%M%S).tar.gz /var/www/facepoint-unifil/storage

# Backup das faces cadastradas
sudo tar -czf faces-backup-$(date +%Y%m%d-%H%M%S).tar.gz /var/www/facepoint-unifil/deepface-api/faces_db
```

### Restaurar backup

```bash
# Restaurar banco de dados
sudo mysql -u root facepoint < backup-facepoint-20250101-120000.sql

# Restaurar arquivos
sudo tar -xzf storage-backup-20250101-120000.tar.gz -C /
sudo tar -xzf faces-backup-20250101-120000.tar.gz -C /

# Corrigir permissões
sudo chown -R www-data:www-data /var/www/facepoint-unifil/storage
sudo chown -R www-data:www-data /var/www/facepoint-unifil/deepface-api/faces_db
```

## Monitoramento

### Verificar uso de recursos

```bash
# CPU e memória
htop

# Espaço em disco
df -h

# Processos Python (DeepFace)
ps aux | grep python

# Processos PHP
ps aux | grep php
```

### Verificar conectividade

```bash
# Testar aplicação Laravel
curl -I http://localhost

# Testar DeepFace API
curl http://localhost:5000/health

# Testar banco de dados
sudo mysql -u facepoint_user -p facepoint -e "SELECT 1"
```

## Troubleshooting

### Erro 502 Bad Gateway

```bash
# Verificar se PHP-FPM está rodando
sudo systemctl status php8.2-fpm

# Verificar logs do Nginx
sudo tail -f /var/log/nginx/error.log

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

### DeepFace API não responde

```bash
# Verificar status
sudo supervisorctl status deepface-api

# Ver logs
sudo tail -f /var/www/facepoint-unifil/deepface-api/logs/supervisor.log

# Reiniciar
sudo supervisorctl restart deepface-api
```

### Erro de permissão

```bash
# Corrigir permissões
sudo chown -R www-data:www-data /var/www/facepoint-unifil
sudo chmod -R 755 /var/www/facepoint-unifil
sudo chmod -R 775 /var/www/facepoint-unifil/storage
sudo chmod -R 775 /var/www/facepoint-unifil/bootstrap/cache
```

### Queue não processa jobs

```bash
# Verificar workers
sudo supervisorctl status laravel-worker:*

# Ver logs
sudo tail -f /var/www/facepoint-unifil/storage/logs/worker.log

# Reiniciar workers
sudo supervisorctl restart laravel-worker:*
```

### Banco de dados não conecta

```bash
# Verificar se MySQL está rodando
sudo systemctl status mysql

# Testar conexão
sudo mysql -u facepoint_user -p

# Verificar .env
sudo cat /var/www/facepoint-unifil/.env | grep DB_

# Limpar cache de config
cd /var/www/facepoint-unifil
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan config:cache
```

## Segurança

### Recomendações importantes

1. **Firewall**: Ative o UFW e libere apenas portas necessárias
   ```bash
   sudo ufw enable
   sudo ufw allow 'Nginx Full'
   sudo ufw allow OpenSSH
   ```

2. **SSL**: Use sempre HTTPS em produção
3. **Senhas fortes**: Use senhas complexas para banco de dados
4. **Atualizações**: Mantenha o sistema atualizado
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

5. **Backups**: Configure backups automáticos
6. **Logs**: Monitore logs regularmente
7. **Debug**: Desative `APP_DEBUG` em produção

### Hardening adicional

```bash
# Desabilitar listagem de diretórios no Nginx
# Já está configurado no script

# Ocultar versão do PHP
sudo sed -i 's/expose_php = On/expose_php = Off/' /etc/php/8.2/fpm/php.ini

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

## Variáveis de Ambiente do Script

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `DEPLOY_USER` | `www-data` | Usuário que executa a aplicação |
| `DEPLOY_PATH` | `/var/www/facepoint-unifil` | Caminho de instalação |
| `DOMAIN` | `facepoint.local` | Domínio da aplicação |
| `DB_NAME` | `facepoint` | Nome do banco de dados |
| `DB_USER` | `facepoint_user` | Usuário do banco |
| `DB_PASSWORD` | - | Senha do banco (obrigatória) |
| `DEEPFACE_PORT` | `5000` | Porta da API DeepFace |
| `PHP_VERSION` | `8.2` | Versão do PHP |

## Suporte

Para problemas ou dúvidas:

1. Verifique os logs em `/var/www/facepoint-unifil/storage/logs/`
2. Consulte a documentação do Laravel: https://laravel.com/docs
3. Consulte a documentação do DeepFace: https://github.com/serengil/deepface

## Licença

Este projeto é privado e de propriedade da Unifil.
