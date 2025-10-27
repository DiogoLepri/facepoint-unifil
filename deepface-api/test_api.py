#!/usr/bin/env python3
"""
Script de teste rápido para a DeepFace API
"""

import requests
import sys

API_URL = "http://localhost:5001"

def test_health():
    """Testa o endpoint de health check"""
    print("🔍 Testando Health Check...")
    try:
        response = requests.get(f"{API_URL}/health", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print("✅ API está funcionando!")
            print(f"   Modelo: {data.get('model')}")
            print(f"   Detector: {data.get('detector')}")
            print(f"   Métrica: {data.get('distance_metric')}")
            return True
        else:
            print(f"❌ Erro: Status {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ API não está rodando!")
        print("   Execute: ./start.sh")
        return False
    except Exception as e:
        print(f"❌ Erro: {str(e)}")
        return False

def test_stats():
    """Testa o endpoint de estatísticas"""
    print("\n📊 Testando Estatísticas...")
    try:
        response = requests.get(f"{API_URL}/stats", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print("✅ Estatísticas obtidas:")
            print(f"   Total de usuários: {data.get('total_users', 0)}")
            print(f"   Total de imagens: {data.get('total_images', 0)}")
            print(f"   Dimensões do embedding: {data.get('embedding_dimensions')}")
            return True
        else:
            print(f"❌ Erro: Status {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Erro: {str(e)}")
        return False

def main():
    print("=" * 50)
    print("FacePoint UniFil - Teste da DeepFace API")
    print("=" * 50)
    print()

    # Testa health
    if not test_health():
        sys.exit(1)

    # Testa stats
    test_stats()

    print()
    print("=" * 50)
    print("✅ Testes concluídos!")
    print("=" * 50)
    print()
    print("Para testar registro de faces, use:")
    print("  - RecognitionController do Laravel")
    print("  - AttendanceController do Laravel")
    print()

if __name__ == "__main__":
    main()
