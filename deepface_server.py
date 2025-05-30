#!/usr/bin/env python3
"""
DeepFace Flask API Server
Provides face recognition services using DeepFace library with Facenet512 model
"""

import os
import cv12
import numpy as np
import base64
import json
from flask import Flask, request, jsonify
from flask_cors import CORS
from deepface import DeepFace
import tempfile
import shutil
from PIL import Image
import io

app = Flask(__name__)
CORS(app)

# Configuration
FACE_DB_PATH = "face_database"
MODEL_NAME = "Facenet512"
CONFIDENCE_THRESHOLD = 0.75
MAX_IMAGE_SIZE = 5 * 1024 * 1024  # 5MB

# Create face database directory if it doesn't exist
if not os.path.exists(FACE_DB_PATH):
    os.makedirs(FACE_DB_PATH)

def decode_base64_image(base64_string):
    """Decode base64 image string to image file"""
    try:
        # Remove data URL prefix if present
        if 'data:image' in base64_string:
            base64_string = base64_string.split(',')[1]
        
        # Decode base64
        image_data = base64.b64decode(base64_string)
        
        # Check file size
        if len(image_data) > MAX_IMAGE_SIZE:
            raise ValueError("Image too large")
        
        # Convert to PIL Image
        image = Image.open(io.BytesIO(image_data))
        
        # Convert to RGB if necessary
        if image.mode != 'RGB':
            image = image.convert('RGB')
        
        return image
    except Exception as e:
        raise ValueError(f"Invalid image data: {str(e)}")

def save_temp_image(image):
    """Save PIL image to temporary file"""
    temp_file = tempfile.NamedTemporaryFile(delete=False, suffix='.jpg')
    image.save(temp_file.name, 'JPEG')
    return temp_file.name

def ensure_user_directory(user_id):
    """Ensure user directory exists in face database"""
    user_dir = os.path.join(FACE_DB_PATH, f"user_{user_id}")
    if not os.path.exists(user_dir):
        os.makedirs(user_dir)
    return user_dir

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model': MODEL_NAME,
        'confidence_threshold': CONFIDENCE_THRESHOLD
    })

@app.route('/register_face', methods=['POST'])
def register_face():
    """Register a new face for a user"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No JSON data provided'}), 400
        
        user_id = data.get('user_id')
        image_data = data.get('image_data')
        
        if not user_id or not image_data:
            return jsonify({'error': 'Missing user_id or image_data'}), 400
        
        # Decode image
        image = decode_base64_image(image_data)
        
        # Save temporary image for processing
        temp_image_path = save_temp_image(image)
        
        try:
            # Verify face exists in image
            face_objs = DeepFace.extract_faces(
                img_path=temp_image_path,
                enforce_detection=True,
                detector_backend='opencv'
            )
            
            if not face_objs:
                return jsonify({'error': 'No face detected in image'}), 400
            
            # Ensure user directory exists
            user_dir = ensure_user_directory(user_id)
            
            # Count existing images for this user
            existing_images = len([f for f in os.listdir(user_dir) if f.endswith('.jpg')])
            
            # Save image to user directory
            final_image_path = os.path.join(user_dir, f"face_{existing_images + 1}.jpg")
            shutil.copy2(temp_image_path, final_image_path)
            
            return jsonify({
                'success': True,
                'message': 'Face registered successfully',
                'user_id': user_id,
                'image_path': final_image_path,
                'total_images': existing_images + 1
            })
        
        finally:
            # Clean up temporary file
            if os.path.exists(temp_image_path):
                os.unlink(temp_image_path)
                
    except ValueError as e:
        return jsonify({'error': str(e)}), 400
    except Exception as e:
        return jsonify({'error': f'Registration failed: {str(e)}'}), 500

@app.route('/recognize_face', methods=['POST'])
def recognize_face():
    """Recognize a face against registered users"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No JSON data provided'}), 400
        
        image_data = data.get('image_data')
        
        if not image_data:
            return jsonify({'error': 'Missing image_data'}), 400
        
        # Decode image
        image = decode_base64_image(image_data)
        
        # Save temporary image for processing
        temp_image_path = save_temp_image(image)
        
        try:
            # Check if face database has any users
            if not os.path.exists(FACE_DB_PATH) or not os.listdir(FACE_DB_PATH):
                return jsonify({
                    'success': False,
                    'message': 'No registered users found'
                })
            
            # Verify face exists in image
            face_objs = DeepFace.extract_faces(
                img_path=temp_image_path,
                enforce_detection=True,
                detector_backend='opencv'
            )
            
            if not face_objs:
                return jsonify({
                    'success': False,
                    'message': 'No face detected in image'
                })
            
            # Perform face recognition
            try:
                result = DeepFace.find(
                    img_path=temp_image_path,
                    db_path=FACE_DB_PATH,
                    model_name=MODEL_NAME,
                    enforce_detection=True,
                    detector_backend='opencv',
                    distance_metric='cosine'
                )
                
                # Process results
                if len(result) > 0 and len(result[0]) > 0:
                    best_match = result[0].iloc[0]
                    
                    # Extract user ID from path
                    identity_path = best_match['identity']
                    user_id = None
                    
                    # Parse user ID from path like "face_database/user_1/face_1.jpg"
                    path_parts = identity_path.split(os.sep)
                    for part in path_parts:
                        if part.startswith('user_'):
                            user_id = part.replace('user_', '')
                            break
                    
                    # Calculate confidence (1 - distance for cosine)
                    distance = best_match['distance']
                    confidence = (1 - distance) * 100
                    
                    # Check if confidence meets threshold
                    if confidence >= (CONFIDENCE_THRESHOLD * 100):
                        return jsonify({
                            'success': True,
                            'user_id': user_id,
                            'confidence': round(confidence, 2),
                            'distance': round(distance, 4),
                            'identity_path': identity_path
                        })
                    else:
                        return jsonify({
                            'success': False,
                            'message': f'Low confidence: {round(confidence, 2)}%',
                            'confidence': round(confidence, 2)
                        })
                else:
                    return jsonify({
                        'success': False,
                        'message': 'No matching face found'
                    })
                    
            except Exception as recognition_error:
                # If DeepFace.find fails, it usually means no matches
                return jsonify({
                    'success': False,
                    'message': 'No matching face found',
                    'error_detail': str(recognition_error)
                })
        
        finally:
            # Clean up temporary file
            if os.path.exists(temp_image_path):
                os.unlink(temp_image_path)
                
    except ValueError as e:
        return jsonify({'error': str(e)}), 400
    except Exception as e:
        return jsonify({'error': f'Recognition failed: {str(e)}'}), 500

@app.route('/get_registered_users', methods=['GET'])
def get_registered_users():
    """Get list of all registered users"""
    try:
        users = []
        
        if os.path.exists(FACE_DB_PATH):
            for item in os.listdir(FACE_DB_PATH):
                if item.startswith('user_') and os.path.isdir(os.path.join(FACE_DB_PATH, item)):
                    user_id = item.replace('user_', '')
                    user_dir = os.path.join(FACE_DB_PATH, item)
                    
                    # Count images for this user
                    image_count = len([f for f in os.listdir(user_dir) if f.endswith('.jpg')])
                    
                    users.append({
                        'user_id': user_id,
                        'image_count': image_count,
                        'directory': item
                    })
        
        return jsonify({
            'success': True,
            'users': users,
            'total_users': len(users)
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to get users: {str(e)}'}), 500

@app.route('/delete_user', methods=['POST'])
def delete_user():
    """Delete a user and all their face data"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No JSON data provided'}), 400
        
        user_id = data.get('user_id')
        
        if not user_id:
            return jsonify({'error': 'Missing user_id'}), 400
        
        user_dir = os.path.join(FACE_DB_PATH, f"user_{user_id}")
        
        if os.path.exists(user_dir):
            shutil.rmtree(user_dir)
            return jsonify({
                'success': True,
                'message': f'User {user_id} deleted successfully'
            })
        else:
            return jsonify({
                'success': False,
                'message': f'User {user_id} not found'
            })
        
    except Exception as e:
        return jsonify({'error': f'Failed to delete user: {str(e)}'}), 500

@app.errorhandler(413)
def too_large(e):
    return jsonify({'error': 'File too large'}), 413

@app.errorhandler(500)
def internal_error(e):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    print("Starting DeepFace API Server...")
    print(f"Face database path: {os.path.abspath(FACE_DB_PATH)}")
    print(f"Model: {MODEL_NAME}")
    print(f"Confidence threshold: {CONFIDENCE_THRESHOLD}")
    print("Server running on http://localhost:5000")
    
    app.run(host='0.0.0.0', port=5000, debug=True)