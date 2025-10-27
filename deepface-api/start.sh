#!/bin/bash

# FacePoint UniFil - DeepFace API Start Script
# Script para inicializar a API Flask com DeepFace (FaceNet512)

echo "=========================================="
echo "FacePoint UniFil - DeepFace API"
echo "Modelo: FaceNet512 (512 dimensões)"
echo "=========================================="
echo ""

# Navega para o diretório da API
cd "$(dirname "$0")"

# Verifica se Python 3 está instalado
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 não encontrado. Por favor, instale Python 3.8 ou superior."
    exit 1
fi

echo "✓ Python 3 encontrado: $(python3 --version)"

# Verifica se pip está instalado
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 não encontrado. Por favor, instale pip."
    exit 1
fi

echo "✓ pip3 encontrado"

# Cria ambiente virtual se não existir
if [ ! -d "venv" ]; then
    echo ""
    echo "Criando ambiente virtual Python..."
    python3 -m venv venv

    if [ $? -ne 0 ]; then
        echo "❌ Erro ao criar ambiente virtual"
        exit 1
    fi

    echo "✓ Ambiente virtual criado"
fi

# Ativa ambiente virtual
echo ""
echo "Ativando ambiente virtual..."
source venv/bin/activate

if [ $? -ne 0 ]; then
    echo "❌ Erro ao ativar ambiente virtual"
    exit 1
fi

echo "✓ Ambiente virtual ativado"

# Instala/atualiza dependências
echo ""
echo "Verificando dependências..."
pip install -q --upgrade pip

if [ -f "requirements.txt" ]; then
    echo "Instalando dependências do requirements.txt..."
    echo "(Isso pode levar alguns minutos na primeira vez...)"
    pip install -q -r requirements.txt

    if [ $? -ne 0 ]; then
        echo "❌ Erro ao instalar dependências"
        exit 1
    fi

    echo "✓ Dependências instaladas"
else
    echo "⚠️  Arquivo requirements.txt não encontrado"
fi

# Cria diretórios necessários
mkdir -p faces_db
mkdir -p logs

# Verifica se a porta 5001 está livre
if lsof -Pi :5001 -sTCP:LISTEN -t >/dev/null ; then
    echo ""
    echo "⚠️  Porta 5001 já está em uso!"
    echo "Deseja parar o processo existente? (s/n)"
    read -r response
    if [[ "$response" =~ ^([sS][iI][mM]|[sS])$ ]]; then
        lsof -ti:5001 | xargs kill -9
        echo "✓ Processo anterior encerrado"
    else
        echo "❌ Encerrando. Por favor, libere a porta 5001 manualmente."
        exit 1
    fi
fi

# Inicia o servidor
echo ""
echo "=========================================="
echo "Iniciando servidor DeepFace API..."
echo "URL: http://localhost:5001"
echo "Modelo: FaceNet512"
echo "Detector: OpenCV"
echo "=========================================="
echo ""
echo "Pressione Ctrl+C para parar o servidor"
echo ""

python3 app.py
