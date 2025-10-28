#!/usr/bin/env python3
"""
FacePoint UniFil - DeepFace API
API Flask para reconhecimento facial usando DeepFace com FaceNet128
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from deepface import DeepFace
import os
import base64
import logging
from datetime import datetime
import numpy as np
import cv2


app = Flask(__name__)
CORS(app)

# Configuração de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/deepface_api.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Configurações
FACES_DB_PATH = os.path.join(os.path.dirname(__file__), 'faces_db')
MODEL_NAME = 'Facenet128'  
DETECTOR_BACKEND = 'opencv'
DISTANCE_METRIC = 'cosine'
ENFORCE_DETECTION = True

# Criar diretório de faces se não existir
os.makedirs(FACES_DB_PATH, exist_ok=True)
os.makedirs('logs', exist_ok=True)

logger.info(f"DeepFace API iniciada com modelo: {MODEL_NAME}")
logger.info(f"Diretório de faces: {FACES_DB_PATH}")


def decode_base64_image(base64_string):
    """
    Decodifica imagem base64 para array numpy
    """
    try:
        # Remove o prefixo data:image se existir
        if 'base64,' in base64_string:
            base64_string = base64_string.split('base64,')[1]

        # Decodifica base64
        img_data = base64.b64decode(base64_string)

        # Converte para numpy array
        nparr = np.frombuffer(img_data, np.uint8)

        # Decodifica imagem
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None:
            raise ValueError("Falha ao decodificar imagem")

        return img
    except Exception as e:
        logger.error(f"Erro ao decodificar imagem base64: {str(e)}")
        raise


def save_face_image(user_id, image_data, image_index=1):
    """
    Salva imagem facial no diretório do usuário
    """
    try:
        user_dir = os.path.join(FACES_DB_PATH, f"user_{user_id}")
        os.makedirs(user_dir, exist_ok=True)

        # Decodifica e salva imagem
        img = decode_base64_image(image_data)

        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"face_{timestamp}_{image_index}.jpg"
        filepath = os.path.join(user_dir, filename)

        cv2.imwrite(filepath, img)

        logger.info(f"Imagem salva: {filepath}")
        return filepath
    except Exception as e:
        logger.error(f"Erro ao salvar imagem: {str(e)}")
        raise


def get_embedding(image_path_or_array):
    """
    Extrai embedding facial usando FaceNet128
    Retorna vetor de 128 dimensões
    """
    try:
        # Usar DeepFace.represent para extrair embedding
        embeddings = DeepFace.represent(
            img_path=image_path_or_array,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            enforce_detection=ENFORCE_DETECTION
        )

        # DeepFace.represent retorna lista de dicionários
        if embeddings and len(embeddings) > 0:
            embedding = embeddings[0]['embedding']
            logger.info(f"Embedding extraído com {len(embedding)} dimensões")
            return embedding
        else:
            raise ValueError("Nenhum rosto detectado na imagem")

    except Exception as e:
        logger.error(f"Erro ao extrair embedding: {str(e)}")
        raise


@app.route('/health', methods=['GET'])
def health_check():
    """
    Endpoint de health check
    """
    return jsonify({
        'status': 'healthy',
        'model': MODEL_NAME,
        'detector': DETECTOR_BACKEND,
        'distance_metric': DISTANCE_METRIC,
        'timestamp': datetime.now().isoformat()
    }), 200


@app.route('/register', methods=['POST'])
def register_face():
    """
    Registra novo rosto no sistema
    Espera: { "user_id": int, "image_data": "base64..." }
    Retorna: { "success": bool, "data": {...} }
    """
    try:
        data = request.get_json()

        if not data:
            return jsonify({
                'success': False,
                'error': 'Dados não fornecidos'
            }), 400

        user_id = data.get('user_id')
        image_data = data.get('image_data')

        if not user_id or not image_data:
            return jsonify({
                'success': False,
                'error': 'user_id e image_data são obrigatórios'
            }), 400

        logger.info(f"Iniciando registro facial para user_id: {user_id}")

        # Salva imagem
        image_path = save_face_image(user_id, image_data)

        # Extrai embedding (128 dimensões)
        embedding = get_embedding(image_path)

        # Conta total de imagens do usuário
        user_dir = os.path.join(FACES_DB_PATH, f"user_{user_id}")
        total_images = len([f for f in os.listdir(user_dir) if f.endswith('.jpg')])

        logger.info(f"Registro bem-sucedido para user_id {user_id}. Total de imagens: {total_images}")

        return jsonify({
            'success': True,
            'data': {
                'user_id': user_id,
                'image_path': image_path,
                'total_images': total_images,
                'embedding_dimensions': len(embedding),
                'model': MODEL_NAME
            }
        }), 200

    except ValueError as e:
        logger.warning(f"Erro de validação no registro: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400
    except Exception as e:
        logger.error(f"Erro no registro facial: {str(e)}", exc_info=True)
        return jsonify({
            'success': False,
            'error': f'Erro interno: {str(e)}'
        }), 500


@app.route('/verify', methods=['POST'])
def verify_face():
    """
    Verifica se um rosto corresponde a um usuário específico
    Espera: { "user_id": int, "image_data": "base64..." }
    Retorna: { "success": bool, "verified": bool, "distance": float }
    """
    try:
        data = request.get_json()

        user_id = data.get('user_id')
        image_data = data.get('image_data')

        if not user_id or not image_data:
            return jsonify({
                'success': False,
                'error': 'user_id e image_data são obrigatórios'
            }), 400

        logger.info(f"Verificando rosto para user_id: {user_id}")

        # Decodifica imagem
        img = decode_base64_image(image_data)

        # Diretório do usuário
        user_dir = os.path.join(FACES_DB_PATH, f"user_{user_id}")

        if not os.path.exists(user_dir) or not os.listdir(user_dir):
            return jsonify({
                'success': False,
                'error': f'Nenhuma face registrada para user_id: {user_id}'
            }), 404

        # Verifica contra o banco de faces do usuário
        result = DeepFace.verify(
            img1_path=img,
            img2_path=user_dir,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            distance_metric=DISTANCE_METRIC,
            enforce_detection=ENFORCE_DETECTION
        )

        verified = result['verified']
        distance = result['distance']

        logger.info(f"Verificação para user_id {user_id}: verified={verified}, distance={distance}")

        return jsonify({
            'success': True,
            'verified': verified,
            'distance': float(distance),
            'threshold': result.get('threshold', 0.4),
            'model': MODEL_NAME
        }), 200

    except Exception as e:
        logger.error(f"Erro na verificação facial: {str(e)}", exc_info=True)
        return jsonify({
            'success': False,
            'error': f'Erro interno: {str(e)}'
        }), 500


def calculate_confidence(distance, threshold=0.4):
    """
    Converte distância (distance) para confiança (confidence) em porcentagem
    FaceNet retorna distance, onde valores menores = mais similar
    Threshold padrão para FaceNet: 0.4
    """
    if distance <= threshold:
        confidence = (1 - (distance / threshold)) * 100
        return min(100, max(0, round(confidence, 2)))
    return 0


@app.route('/recognize', methods=['POST'])
def recognize_face():
    """
    Reconhece um rosto entre todos os usuários registrados
    Espera: { "image_data": "base64..." }
    Retorna: { "success": bool, "user_id": int, "distance": float, "confidence": float }
    """
    try:
        data = request.get_json()
        image_data = data.get('image_data')

        if not image_data:
            return jsonify({
                'success': False,
                'error': 'image_data é obrigatório'
            }), 400

        logger.info("Iniciando reconhecimento facial")

        # Decodifica imagem
        img = decode_base64_image(image_data)

        # Verifica se há usuários registrados
        if not os.listdir(FACES_DB_PATH):
            return jsonify({
                'success': False,
                'error': 'Nenhum usuário registrado no sistema'
            }), 404

        # Busca no banco de faces
        results = DeepFace.find(
            img_path=img,
            db_path=FACES_DB_PATH,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR_BACKEND,
            distance_metric=DISTANCE_METRIC,
            enforce_detection=ENFORCE_DETECTION
        )

        # Processa resultados
        if results and len(results) > 0 and len(results[0]) > 0:
            # Pega o melhor match
            best_match = results[0].iloc[0]
            identity_path = best_match['identity']
            distance = float(best_match[f'{MODEL_NAME}_{DISTANCE_METRIC}'])

            # Extrai user_id do caminho
            user_id = identity_path.split('user_')[1].split('/')[0]

            # Calcula confidence baseado na distance
            confidence = calculate_confidence(distance)

            logger.info(f"Rosto reconhecido: user_id={user_id}, distance={distance}, confidence={confidence}%")

            return jsonify({
                'success': True,
                'user_id': int(user_id),
                'distance': distance,
                'confidence': confidence,
                'identity_path': identity_path,
                'model': MODEL_NAME
            }), 200
        else:
            logger.info("Nenhum rosto reconhecido")
            return jsonify({
                'success': False,
                'error': 'Nenhum rosto reconhecido'
            }), 404

    except Exception as e:
        logger.error(f"Erro no reconhecimento facial: {str(e)}", exc_info=True)
        return jsonify({
            'success': False,
            'error': f'Erro interno: {str(e)}'
        }), 500


@app.route('/delete/<int:user_id>', methods=['DELETE'])
def delete_user_faces(user_id):
    """
    Remove todas as faces de um usuário
    """
    try:
        user_dir = os.path.join(FACES_DB_PATH, f"user_{user_id}")

        if not os.path.exists(user_dir):
            return jsonify({
                'success': False,
                'error': f'Usuário {user_id} não encontrado'
            }), 404

        # Remove diretório e arquivos
        import shutil
        shutil.rmtree(user_dir)

        logger.info(f"Faces do user_id {user_id} removidas")

        return jsonify({
            'success': True,
            'message': f'Faces do usuário {user_id} removidas com sucesso'
        }), 200

    except Exception as e:
        logger.error(f"Erro ao remover faces: {str(e)}")
        return jsonify({
            'success': False,
            'error': f'Erro interno: {str(e)}'
        }), 500


@app.route('/stats', methods=['GET'])
def get_statistics():
    """
    Retorna estatísticas do sistema
    """
    try:
        users = [d for d in os.listdir(FACES_DB_PATH) if d.startswith('user_')]
        total_users = len(users)

        total_images = 0
        for user_dir in users:
            user_path = os.path.join(FACES_DB_PATH, user_dir)
            total_images += len([f for f in os.listdir(user_path) if f.endswith('.jpg')])

        return jsonify({
            'total_users': total_users,
            'total_images': total_images,
            'model': MODEL_NAME,
            'detector': DETECTOR_BACKEND,
            'distance_metric': DISTANCE_METRIC,
            'embedding_dimensions': 128
        }), 200

    except Exception as e:
        logger.error(f"Erro ao obter estatísticas: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500


if __name__ == '__main__':
    logger.info("=" * 50)
    logger.info("FacePoint UniFil - DeepFace API")
    logger.info(f"Modelo: {MODEL_NAME} (128 dimensões)")
    logger.info(f"Detector: {DETECTOR_BACKEND}")
    logger.info(f"Métrica: {DISTANCE_METRIC}")
    logger.info("=" * 50)

    # Roda servidor
    app.run(
        host='0.0.0.0',
        port=5001,
        debug=False,
        threaded=True
    )
