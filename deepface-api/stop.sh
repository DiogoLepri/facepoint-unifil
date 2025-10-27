#!/bin/bash

# FacePoint UniFil - DeepFace API Stop Script

echo "Parando DeepFace API..."

# Encontra processos na porta 5001 e encerra
if lsof -Pi :5001 -sTCP:LISTEN -t >/dev/null ; then
    echo "Encerrando processos na porta 5001..."
    lsof -ti:5001 | xargs kill -9
    echo "✓ DeepFace API parada com sucesso"
else
    echo "ℹ️  Nenhum processo encontrado na porta 5001"
fi

# Encontra processos Python relacionados ao app.py
pkill -f "python.*app.py" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Processos Python do app.py encerrados"
fi

echo ""
echo "DeepFace API parada."
