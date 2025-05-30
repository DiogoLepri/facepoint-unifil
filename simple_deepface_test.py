#!/usr/bin/env python3
"""
Simple DeepFace test server for development
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import os

app = Flask(__name__)
CORS(app)

# Simple mock responses for testing
@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'healthy',
        'model': 'Mock Facenet512',
        'confidence_threshold': 75
    })

@app.route('/register_face', methods=['POST'])
def register_face():
    data = request.get_json()
    user_id = data.get('user_id')
    
    return jsonify({
        'success': True,
        'message': 'Face registered successfully (mock)',
        'user_id': user_id,
        'image_path': f'face_database/user_{user_id}/face_1.jpg',
        'total_images': 1
    })

@app.route('/recognize_face', methods=['POST'])
def recognize_face():
    data = request.get_json()
    
    # Mock successful recognition for testing
    return jsonify({
        'success': True,
        'user_id': '1',
        'confidence': 85.5,
        'distance': 0.145,
        'identity_path': 'face_database/user_1/face_1.jpg'
    })

@app.route('/get_registered_users', methods=['GET'])
def get_registered_users():
    return jsonify({
        'success': True,
        'users': [
            {'user_id': '1', 'image_count': 1, 'directory': 'user_1'}
        ],
        'total_users': 1
    })

if __name__ == '__main__':
    print("Starting Simple DeepFace Test Server...")
    print("Server running on http://localhost:5001")
    print("This is a MOCK server for testing the Laravel integration")
    
    app.run(host='0.0.0.0', port=5001, debug=True)