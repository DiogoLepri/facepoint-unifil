#!/usr/bin/env python3
"""
FacePoint UniFil - DeepFace API
Microserviço Flask para reconhecimento facial usando DeepFace com Facenet512

Endpoints:
- GET  /health: Health check
- POST /extract_embedding: Extrai embedding de uma imagem (para cadastro)
- POST /recognize_face: Reconhece rosto comparando com embeddings conhecidos
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from deepface import DeepFace
import base64
import logging
from datetime import datetime
import numpy as np
import cv2
import sys
import traceback

app = Flask(__name__)
CORS(app)

# Configuração de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - [%(levelname)s] - %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)


MODEL_NAME = 'Facenet512'  
DETECTOR_BACKEND = 'opencv'
THRESHOLD = 15.0  # Distância euclidiana threshold para Facenet512 (padrão DeepFace)

logger.info("=" * 80)
logger.info("FacePoint UniFil - DeepFace API")
logger.info(f"Modelo: {MODEL_NAME} (512 dimensões)")
logger.info(f"Detector: {DETECTOR_BACKEND}")
logger.info(f"Threshold: {THRESHOLD}")
logger.info("=" * 80)


def base64_to_image(img_b64: str) -> np.ndarray:
    """
    Converte imagem base64 para array numpy OpenCV.
    Aceita tanto "data:image/jpeg;base64,..." quanto base64 puro.

    Args:
        img_b64: String base64 da imagem

    Returns:
        np.ndarray: Imagem em formato OpenCV (BGR)

    Raises:
        ValueError: Se a decodificação falhar
    """
    try:
        # Remove prefixo "data:image/..." se existir
        if 'base64,' in img_b64:
            img_b64 = img_b64.split('base64,')[1]

        # Decodifica base64
        img_data = base64.b64decode(img_b64)

        # Converte para numpy array
        nparr = np.frombuffer(img_data, np.uint8)

        # Decodifica imagem com OpenCV
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            raise ValueError("Falha ao decodificar imagem - formato inválido")

        return img

    except Exception as e:
        logger.error(f"Erro ao decodificar imagem base64: {str(e)}")
        raise ValueError(f"Erro ao decodificar imagem: {str(e)}")


def euclidean_distance(a: list, b: list) -> float:
    """
    Calcula distância euclidiana entre dois vetores.

    Args:
        a: Primeiro vetor (embedding)
        b: Segundo vetor (embedding)

    Returns:
        float: Distância euclidiana
    """
    arr_a = np.array(a)
    arr_b = np.array(b)
    return float(np.linalg.norm(arr_a - arr_b))


@app.route('/health', methods=['GET'])
def health_check():
    """
    Health check endpoint.
    Retorna status do serviço e configurações.
    """
    return jsonify({
        'status': 'ok',
        'model': MODEL_NAME,
        'dimensions': 512,
        'threshold': THRESHOLD,
        'detector': DETECTOR_BACKEND,
        'timestamp': datetime.now().isoformat()
    }), 200


@app.route('/extract_embedding', methods=['POST'])
def extract_embedding():
    """
    Extrai embedding facial de uma imagem (usado no cadastro/enrolment).

    Input JSON:
    {
        "image_data": "data:image/jpeg;base64,..."
    }

    Output JSON:
    {
        "success": true,
        "embedding": [0.123, -0.456, ...],  // 512 floats
        "dimensions": 512,
        "model": "Facenet512"
    }
    """
    logger.info("=" * 80)
    logger.info("REQUEST: POST /extract_embedding")
    logger.info(f"Timestamp: {datetime.now().isoformat()}")

    try:
        # Validação de entrada
        data = request.get_json()

        if not data or 'image_data' not in data:
            logger.warning("Campo 'image_data' não fornecido")
            return jsonify({
                'success': False,
                'error': 'Campo "image_data" é obrigatório'
            }), 400

        image_data = data['image_data']
        logger.info(f"Recebido image_data: {len(image_data)} caracteres")

        # Converte base64 para imagem OpenCV
        logger.info("Convertendo base64 para imagem...")
        img = base64_to_image(image_data)
        logger.info(f"Imagem decodificada: {img.shape[1]}x{img.shape[0]} pixels")

        # Extrai embedding usando DeepFace
        logger.info(f"Extraindo embedding com {MODEL_NAME}...")
        embeddings = DeepFace.represent(
            img_path=img,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=True
        )

        logger.info(f"Rostos detectados: {len(embeddings)}")

        if not embeddings or len(embeddings) == 0:
            logger.warning("Nenhum rosto detectado na imagem")
            return jsonify({
                'success': False,
                'error': 'Nenhum rosto detectado na imagem'
            }), 400

        # Pega o primeiro rosto detectado
        embedding = embeddings[0]['embedding']
        logger.info(f"✓ Embedding extraído com sucesso: {len(embedding)} dimensões")
        logger.info("=" * 80)

        return jsonify({
            'success': True,
            'embedding': embedding,
            'dimensions': len(embedding),
            'model': MODEL_NAME
        }), 200

    except ValueError as e:
        logger.warning(f"Erro de validação: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

    except Exception as e:
        logger.error(f"ERRO CRÍTICO: {str(e)}")
        logger.error(f"Traceback:\n{traceback.format_exc()}")
        return jsonify({
            'success': False,
            'error': f'Erro interno: {str(e)}'
        }), 500


@app.route('/recognize_face', methods=['POST'])
def recognize_face():
    """
    Reconhece um rosto comparando com embeddings conhecidos.

    Input JSON:
    {
        "image_data": "data:image/jpeg;base64,...",
        "known_embeddings": [
            {"user_id": 1, "embedding": [0.12, -0.33, ...]},
            {"user_id": 2, "embedding": [0.55, 0.04, ...]}
        ]
    }

    Output JSON (match encontrado):
    {
        "success": true,
        "user_id": 1,
        "distance": 0.23
    }

    Output JSON (sem match):
    {
        "success": false,
        "reason": "no_match",
        "best_distance": 0.67
    }
    """
    logger.info("=" * 80)
    logger.info("REQUEST: POST /recognize_face")
    logger.info(f"Timestamp: {datetime.now().isoformat()}")

    try:
        # Validação de entrada
        data = request.get_json()

        if not data:
            logger.warning("Dados não fornecidos")
            return jsonify({'success': False, 'error': 'Dados não fornecidos'}), 400

        if 'image_data' not in data:
            logger.warning("Campo 'image_data' não fornecido")
            return jsonify({'success': False, 'error': 'Campo "image_data" é obrigatório'}), 400

        if 'known_embeddings' not in data or not isinstance(data['known_embeddings'], list):
            logger.warning("Campo 'known_embeddings' inválido")
            return jsonify({'success': False, 'error': 'Campo "known_embeddings" deve ser uma lista'}), 400

        if len(data['known_embeddings']) == 0:
            logger.warning("Nenhum embedding conhecido fornecido")
            return jsonify({'success': False, 'error': 'Nenhum embedding conhecido fornecido'}), 400

        image_data = data['image_data']
        known_embeddings = data['known_embeddings']

        logger.info(f"Recebido image_data: {len(image_data)} caracteres")
        logger.info(f"Embeddings conhecidos: {len(known_embeddings)}")

        # Converte base64 para imagem
        logger.info("Convertendo base64 para imagem...")
        img = base64_to_image(image_data)
        logger.info(f"Imagem decodificada: {img.shape[1]}x{img.shape[0]} pixels")

        # Extrai embedding da imagem atual
        logger.info(f"Extraindo embedding com {MODEL_NAME}...")
        embeddings = DeepFace.represent(
            img_path=img,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=True
        )

        logger.info(f"Rostos detectados: {len(embeddings)}")

        if not embeddings or len(embeddings) == 0:
            logger.warning("Nenhum rosto detectado na imagem")
            return jsonify({'success': False, 'error': 'Nenhum rosto detectado na imagem'}), 400

        current_embedding = embeddings[0]['embedding']
        logger.info(f"Embedding atual extraído: {len(current_embedding)} dimensões")

        # Compara com todos os embeddings conhecidos
        logger.info(f"Comparando com {len(known_embeddings)} embeddings conhecidos...")

        best_match_user_id = None
        best_distance = float('inf')

        for idx, known in enumerate(known_embeddings):
            if 'user_id' not in known or 'embedding' not in known:
                logger.warning(f"Embedding #{idx} inválido (faltando campos)")
                continue

            user_id = known['user_id']
            known_embedding = known['embedding']

            # Valida tamanho do embedding
            if len(known_embedding) != len(current_embedding):
                logger.warning(f"Embedding #{idx} com tamanho diferente: {len(known_embedding)} vs {len(current_embedding)}")
                continue

            # Calcula distância euclidiana
            distance = euclidean_distance(current_embedding, known_embedding)

            logger.debug(f"User {user_id}: distance={distance:.4f}")

            # Atualiza melhor match
            if distance < best_distance:
                best_distance = distance
                best_match_user_id = user_id

        logger.info(f"Melhor match: user_id={best_match_user_id}, distance={best_distance:.4f}, threshold={THRESHOLD}")

        # Verifica se o melhor match está abaixo do threshold
        if best_distance < THRESHOLD:
            logger.info(f"✓ MATCH ENCONTRADO! User ID: {best_match_user_id}")
            logger.info("=" * 80)

            return jsonify({
                'success': True,
                'user_id': best_match_user_id,
                'distance': float(best_distance)
            }), 200
        else:
            logger.info(f"✗ NENHUM MATCH (melhor distância: {best_distance:.4f} > threshold: {THRESHOLD})")
            logger.info("=" * 80)

            return jsonify({
                'success': False,
                'reason': 'no_match',
                'best_distance': float(best_distance)
            }), 200

    except ValueError as e:
        logger.warning(f"Erro de validação: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 400

    except Exception as e:
        logger.error(f"ERRO CRÍTICO: {str(e)}")
        logger.error(f"Traceback:\n{traceback.format_exc()}")
        return jsonify({'success': False, 'error': f'Erro interno: {str(e)}'}), 500


if __name__ == '__main__':
    logger.info("=" * 80)
    logger.info("Iniciando servidor Flask...")
    logger.info("Host: 0.0.0.0")
    logger.info("Port: 5001")
    logger.info("URL: http://0.0.0.0:5001")
    logger.info("Pressione CTRL+C para parar")
    logger.info("=" * 80)

    app.run(
        host='0.0.0.0',
        port=5001,
        debug=False,
        threaded=True
    )
