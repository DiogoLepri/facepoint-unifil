#!/bin/bash

################################################################################
# Script de Deploy - FacePoint Unifil
# Sistema: Ubuntu 20.04/22.04/24.04
# Requisitos: Laravel + DeepFace API Python
################################################################################

set -e  # Para execução em caso de erro

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variáveis de configuração
PROJECT_NAME="facepoint-unifil"
DEPLOY_USER="${DEPLOY_USER:-www-data}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/facepoint-unifil}"
DOMAIN="${DOMAIN:-facepoint.local}"
DB_NAME="${DB_NAME:-facepoint}"
DB_USER="${DB_USER:-facepoint_user}"
DB_PASSWORD="${DB_PASSWORD:-}"
DEEPFACE_PORT="${DEEPFACE_PORT:-5000}"
PHP_VERSION="8.2"

# Permitir Composer como superuser
export COMPOSER_ALLOW_SUPERUSER=1

################################################################################
# Funções auxiliares
################################################################################

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}! $1${NC}"
}

check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Este script precisa ser executado como root (sudo)"
        exit 1
    fi
}

################################################################################
# Instalação de dependências do sistema
################################################################################

install_system_dependencies() {
    print_header "Instalando dependências do sistema"

    apt-get update

    # Dependências gerais
    apt-get install -y \
        curl \
        git \
        unzip \
        supervisor \
        nginx \
        software-properties-common \
        build-essential \
        libssl-dev \
        libffi-dev \
        python3-dev \
        python3-pip \
        python3-venv

    print_success "Dependências do sistema instaladas"
}

################################################################################
# Instalação do PHP e extensões
################################################################################

install_php() {
    print_header "Instalando PHP ${PHP_VERSION}"

    # Adiciona repositório do PHP
    add-apt-repository -y ppa:ondrej/php
    apt-get update

    # Instala PHP e extensões necessárias para Laravel
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-sqlite3

    print_success "PHP ${PHP_VERSION} instalado"
}

################################################################################
# Instalação do Composer
################################################################################

install_composer() {
    print_header "Instalando Composer"

    if command -v composer &> /dev/null; then
        print_warning "Composer já está instalado"
        return
    fi

    # Download e instalação do Composer
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm /tmp/composer-setup.php

    print_success "Composer instalado"
}

################################################################################
# Instalação do Node.js e NPM
################################################################################

install_nodejs() {
    print_header "Instalando Node.js e NPM"

    # Instala Node.js 20.x LTS
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs

    print_success "Node.js $(node --version) e NPM $(npm --version) instalados"
}

################################################################################
# Instalação do MySQL
################################################################################

install_mysql() {
    print_header "Instalando MySQL"

    if command -v mysql &> /dev/null; then
        print_warning "MySQL já está instalado"
        return
    fi

    # Instala MySQL Server
    apt-get install -y mysql-server

    # Inicia MySQL
    systemctl start mysql
    systemctl enable mysql

    print_success "MySQL instalado e iniciado"
}

################################################################################
# Configuração do banco de dados
################################################################################

setup_database() {
    print_header "Configurando banco de dados"

    if [ -z "$DB_PASSWORD" ]; then
        print_error "DB_PASSWORD não foi definida"
        print_warning "Defina a variável: export DB_PASSWORD='sua_senha'"
        exit 1
    fi

    # Cria banco de dados e usuário
    mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

    print_success "Banco de dados configurado"
}

################################################################################
# Clone/atualização do projeto
################################################################################

deploy_application() {
    print_header "Fazendo deploy da aplicação"

    # Cria diretório se não existir
    mkdir -p "$DEPLOY_PATH"

    # Se o diretório não estiver vazio, assume que é uma atualização
    if [ "$(ls -A $DEPLOY_PATH)" ]; then
        print_warning "Diretório já existe - fazendo atualização"
        cd "$DEPLOY_PATH"
        git pull
    else
        print_warning "Este script não faz clone do repositório"
        print_warning "Por favor, copie os arquivos do projeto para: $DEPLOY_PATH"
        print_warning "Ou faça o clone manual: git clone <seu-repo> $DEPLOY_PATH"

        # Aguarda confirmação
        read -p "Pressione ENTER quando os arquivos estiverem no lugar..."
    fi

    cd "$DEPLOY_PATH"

    print_success "Aplicação implantada em $DEPLOY_PATH"
}

################################################################################
# Configuração do Laravel
################################################################################

setup_laravel() {
    print_header "Configurando Laravel"

    cd "$DEPLOY_PATH"

    # Instala dependências do Composer
    print_warning "Instalando dependências do Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction

    # Copia .env se não existir
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            print_success "Arquivo .env criado"
        else
            print_error "Arquivo .env.example não encontrado"
            exit 1
        fi
    fi

    # Gera APP_KEY se necessário
    if ! grep -q "APP_KEY=base64:" .env; then
        php artisan key:generate --force
        print_success "APP_KEY gerada"
    fi

    # Configura permissões
    chown -R $DEPLOY_USER:$DEPLOY_USER "$DEPLOY_PATH"
    chmod -R 755 "$DEPLOY_PATH"
    chmod -R 775 "$DEPLOY_PATH/storage"
    chmod -R 775 "$DEPLOY_PATH/bootstrap/cache"

    # Otimizações
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Migrations
    php artisan migrate --force

    print_success "Laravel configurado"
}

################################################################################
# Build dos assets
################################################################################

build_assets() {
    print_header "Compilando assets"

    cd "$DEPLOY_PATH"

    # Instala dependências NPM
    npm ci --production=false

    # Build dos assets
    npm run build

    print_success "Assets compilados"
}

################################################################################
# Configuração do DeepFace API
################################################################################

setup_deepface() {
    print_header "Configurando DeepFace API"

    cd "$DEPLOY_PATH/deepface-api"

    # Cria ambiente virtual Python
    python3 -m venv venv

    # Ativa ambiente virtual e instala dependências
    source venv/bin/activate
    pip install --upgrade pip
    pip install -r requirements.txt
    deactivate

    # Cria diretórios necessários
    mkdir -p faces_db logs
    chown -R $DEPLOY_USER:$DEPLOY_USER "$DEPLOY_PATH/deepface-api"

    print_success "DeepFace API configurado"
}

################################################################################
# Configuração do Supervisor para DeepFace
################################################################################

setup_supervisor() {
    print_header "Configurando Supervisor para DeepFace API"

    cat > /etc/supervisor/conf.d/deepface-api.conf <<EOF
[program:deepface-api]
process_name=%(program_name)s
command=${DEPLOY_PATH}/deepface-api/venv/bin/python ${DEPLOY_PATH}/deepface-api/app.py
directory=${DEPLOY_PATH}/deepface-api
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${DEPLOY_USER}
numprocs=1
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/deepface-api/logs/supervisor.log
stopwaitsecs=60
environment=PATH="${DEPLOY_PATH}/deepface-api/venv/bin"
EOF

    # Recarrega supervisor
    supervisorctl reread
    supervisorctl update
    supervisorctl restart deepface-api

    print_success "Supervisor configurado"
}

################################################################################
# Configuração do Nginx
################################################################################

setup_nginx() {
    print_header "Configurando Nginx"

    cat > /etc/nginx/sites-available/${PROJECT_NAME} <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${DEPLOY_PATH}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Proxy para DeepFace API
    location /api/deepface/ {
        proxy_pass http://127.0.0.1:${DEEPFACE_PORT}/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        # Timeout maior para processamento de imagens
        proxy_connect_timeout 300s;
        proxy_send_timeout 300s;
        proxy_read_timeout 300s;
    }

    client_max_body_size 20M;
}
EOF

    # Habilita site
    ln -sf /etc/nginx/sites-available/${PROJECT_NAME} /etc/nginx/sites-enabled/

    # Remove site padrão
    rm -f /etc/nginx/sites-enabled/default

    # Testa configuração
    nginx -t

    # Reinicia Nginx
    systemctl restart nginx
    systemctl enable nginx

    print_success "Nginx configurado"
}

################################################################################
# Configuração do Laravel Queue Worker
################################################################################

setup_queue_worker() {
    print_header "Configurando Laravel Queue Worker"

    cat > /etc/supervisor/conf.d/laravel-worker.conf <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DEPLOY_PATH}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=${DEPLOY_PATH}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${DEPLOY_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/worker.log
stopwaitsecs=3600
EOF

    supervisorctl reread
    supervisorctl update
    supervisorctl restart laravel-worker:*

    print_success "Queue Worker configurado"
}

################################################################################
# Configuração do Cron para Laravel Scheduler
################################################################################

setup_cron() {
    print_header "Configurando Cron para Laravel Scheduler"

    # Adiciona cron job se não existir
    (crontab -u $DEPLOY_USER -l 2>/dev/null | grep -v "artisan schedule:run"; echo "* * * * * cd ${DEPLOY_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -u $DEPLOY_USER -

    print_success "Cron configurado"
}

################################################################################
# Configuração de firewall (opcional)
################################################################################

setup_firewall() {
    print_header "Configurando Firewall (UFW)"

    if command -v ufw &> /dev/null; then
        ufw allow 'Nginx Full'
        ufw allow OpenSSH

        # Não ativa UFW automaticamente para não bloquear acesso SSH
        print_warning "Firewall configurado mas não ativado"
        print_warning "Para ativar: sudo ufw enable"
    else
        apt-get install -y ufw
        setup_firewall
    fi

    print_success "Regras de firewall configuradas"
}

################################################################################
# Informações pós-instalação
################################################################################

post_install_info() {
    print_header "Deploy Concluído!"

    echo -e "${GREEN}Sistema instalado com sucesso!${NC}\n"

    echo -e "${YELLOW}Informações importantes:${NC}"
    echo -e "  - Caminho da aplicação: ${BLUE}$DEPLOY_PATH${NC}"
    echo -e "  - Domínio configurado: ${BLUE}$DOMAIN${NC}"
    echo -e "  - Banco de dados: ${BLUE}$DB_NAME${NC}"
    echo -e "  - Usuário do banco: ${BLUE}$DB_USER${NC}"
    echo -e "  - DeepFace API: ${BLUE}http://localhost:$DEEPFACE_PORT${NC}"

    echo -e "\n${YELLOW}Próximos passos:${NC}"
    echo -e "  1. Configure o arquivo ${BLUE}.env${NC} com suas credenciais:"
    echo -e "     ${BLUE}nano $DEPLOY_PATH/.env${NC}"
    echo -e ""
    echo -e "  2. Configure o DNS ou /etc/hosts para apontar para ${BLUE}$DOMAIN${NC}"
    echo -e ""
    echo -e "  3. (Opcional) Configure SSL com Let's Encrypt:"
    echo -e "     ${BLUE}apt-get install certbot python3-certbot-nginx${NC}"
    echo -e "     ${BLUE}certbot --nginx -d $DOMAIN${NC}"
    echo -e ""
    echo -e "  4. Verifique os serviços:"
    echo -e "     ${BLUE}supervisorctl status${NC}"
    echo -e "     ${BLUE}systemctl status nginx${NC}"
    echo -e "     ${BLUE}systemctl status php${PHP_VERSION}-fpm${NC}"
    echo -e ""
    echo -e "  5. Acesse a aplicação em: ${BLUE}http://$DOMAIN${NC}"
    echo -e ""

    echo -e "${YELLOW}Comandos úteis:${NC}"
    echo -e "  - Ver logs do Laravel: ${BLUE}tail -f $DEPLOY_PATH/storage/logs/laravel.log${NC}"
    echo -e "  - Ver logs do DeepFace: ${BLUE}tail -f $DEPLOY_PATH/deepface-api/logs/supervisor.log${NC}"
    echo -e "  - Reiniciar queue: ${BLUE}supervisorctl restart laravel-worker:*${NC}"
    echo -e "  - Reiniciar DeepFace: ${BLUE}supervisorctl restart deepface-api${NC}"
    echo -e "  - Limpar cache Laravel: ${BLUE}cd $DEPLOY_PATH && php artisan cache:clear${NC}"
    echo -e ""
}

################################################################################
# Menu interativo
################################################################################

show_menu() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}  Deploy FacePoint Unifil - Menu${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    echo "1. Instalação completa (primeira vez)"
    echo "2. Atualização da aplicação"
    echo "3. Instalar apenas dependências do sistema"
    echo "4. Configurar apenas Laravel"
    echo "5. Configurar apenas DeepFace API"
    echo "6. Rebuild de assets"
    echo "7. Sair"
    echo ""
    read -p "Escolha uma opção: " option

    case $option in
        1)
            check_root
            install_system_dependencies
            install_php
            install_composer
            install_nodejs
            install_mysql
            setup_database
            deploy_application
            setup_laravel
            build_assets
            setup_deepface
            setup_supervisor
            setup_nginx
            setup_queue_worker
            setup_cron
            setup_firewall
            post_install_info
            ;;
        2)
            check_root
            cd "$DEPLOY_PATH"
            git pull
            composer install --no-dev --optimize-autoloader --no-interaction
            npm ci
            npm run build
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            supervisorctl restart all
            systemctl restart php${PHP_VERSION}-fpm
            systemctl restart nginx
            print_success "Aplicação atualizada!"
            ;;
        3)
            check_root
            install_system_dependencies
            install_php
            install_composer
            install_nodejs
            install_mysql
            ;;
        4)
            check_root
            setup_laravel
            ;;
        5)
            check_root
            setup_deepface
            setup_supervisor
            ;;
        6)
            cd "$DEPLOY_PATH"
            npm ci
            npm run build
            print_success "Assets recompilados!"
            ;;
        7)
            exit 0
            ;;
        *)
            print_error "Opção inválida"
            show_menu
            ;;
    esac
}

################################################################################
# Execução principal
################################################################################

main() {
    clear

    # Verifica se foi passado argumento
    if [ "$1" == "--full" ]; then
        check_root
        install_system_dependencies
        install_php
        install_composer
        install_nodejs
        install_mysql
        setup_database
        deploy_application
        setup_laravel
        build_assets
        setup_deepface
        setup_supervisor
        setup_nginx
        setup_queue_worker
        setup_cron
        setup_firewall
        post_install_info
    else
        show_menu
    fi
}

# Executa o script
main "$@"
