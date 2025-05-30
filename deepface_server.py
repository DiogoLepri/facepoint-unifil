import os
import cv2
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
import socket
import ssl
import threading
import urllib.parse
from datetime import datetime
import requests
from urllib.parse import urlparse
import logging
import time
import select

app = Flask(__name__)
CORS(app)

# Configuration
FACE_DB_PATH = "face_database"
MODEL_NAME = "Facenet512"
CONFIDENCE_THRESHOLD = 0.75
MAX_IMAGE_SIZE = 5 * 1024 * 1024  # 5MB

# MITM Proxy Configuration
PROXY_PORT = 8888
PROXY_HOST = '0.0.0.0'
INTERCEPT_LOG_FILE = "mitm_intercepted.log"
MAX_REQUEST_SIZE = 10 * 1024 * 1024  # 10MB

# Create face database directory if it doesn't exist
if not os.path.exists(FACE_DB_PATH):
    os.makedirs(FACE_DB_PATH)

# Setup logging for MITM proxy
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(INTERCEPT_LOG_FILE),
        logging.StreamHandler()
    ]
)

class MITMProxy:
    """Man-in-the-Middle Proxy Implementation"""
    
    def __init__(self):
        self.intercepted_data = []
        self.running = False
        self.server_socket = None
        self.blocked_domains = set()
        self.allowed_domains = set()
        self.intercept_patterns = []
        self.max_connections = 50
        
    def add_intercept_pattern(self, pattern):
        """Add URL pattern to intercept"""
        self.intercept_patterns.append(pattern.lower())
    
    def block_domain(self, domain):
        """Block specific domain"""
        self.blocked_domains.add(domain.lower())
    
    def allow_domain(self, domain):
        """Allow specific domain"""
        self.allowed_domains.add(domain.lower())
    
    def should_intercept(self, url):
        """Check if URL should be intercepted"""
        url_lower = url.lower()
        
        # Check if domain is blocked
        for domain in self.blocked_domains:
            if domain in url_lower:
                return False
        
        # Check if we have allowed domains and this isn't one
        if self.allowed_domains:
            allowed = False
            for domain in self.allowed_domains:
                if domain in url_lower:
                    allowed = True
                    break
            if not allowed:
                return False
        
        # Check intercept patterns
        if self.intercept_patterns:
            for pattern in self.intercept_patterns:
                if pattern in url_lower:
                    return True
            return False
        
        return True
    
    def parse_http_request(self, request_data):
        """Parse HTTP request data"""
        try:
            lines = request_data.split('\r\n')
            
            # Parse first line
            if not lines or not lines[0]:
                return None
            
            parts = lines[0].split(' ')
            if len(parts) < 3:
                return None
            
            method, url, version = parts[0], parts[1], parts[2]
            
            # Parse headers
            headers = {}
            body_start = -1
            
            for i, line in enumerate(lines[1:], 1):
                if line == '':
                    body_start = i + 1
                    break
                if ':' in line:
                    key, value = line.split(':', 1)
                    headers[key.strip()] = value.strip()
            
            # Extract body
            body = ''
            if body_start > 0 and body_start < len(lines):
                body = '\r\n'.join(lines[body_start:])
            
            return {
                'method': method,
                'url': url,
                'version': version,
                'headers': headers,
                'body': body
            }
        except Exception as e:
            logging.error(f"Error parsing HTTP request: {e}")
            return None
    
    def handle_client_connection(self, client_socket, client_address):
        """Handle individual client connection"""
        try:
            # Set socket timeout
            client_socket.settimeout(30)
            
            # Receive request data
            request_data = b''
            while True:
                try:
                    chunk = client_socket.recv(4096)
                    if not chunk:
                        break
                    request_data += chunk
                    
                    # Check if we have complete request
                    if b'\r\n\r\n' in request_data:
                        break
                    
                    # Prevent memory issues
                    if len(request_data) > MAX_REQUEST_SIZE:
                        break
                        
                except socket.timeout:
                    break
            
            if not request_data:
                return
            
            # Decode request
            try:
                request_str = request_data.decode('utf-8', errors='ignore')
            except:
                return
            
            # Parse request
            parsed_request = self.parse_http_request(request_str)
            if not parsed_request:
                return
            
            method = parsed_request['method']
            url = parsed_request['url']
            headers = parsed_request['headers']
            body = parsed_request['body']
            
            # Log intercepted request
            if self.should_intercept(url):
                log_entry = {
                    'timestamp': datetime.now().isoformat(),
                    'client_ip': client_address[0],
                    'method': method,
                    'url': url,
                    'headers': headers,
                    'body': body[:1000] if body else '',  # Limit body size in memory
                    'response': None
                }
                
                self.intercepted_data.append(log_entry)
                logging.info(f"MITM Intercepted: {method} {url} from {client_address[0]}")
            
            # Handle CONNECT method for HTTPS
            if method == 'CONNECT':
                self.handle_connect_request(client_socket, url, headers)
            else:
                self.handle_http_request(client_socket, parsed_request, client_address)
                
        except Exception as e:
            logging.error(f"Error handling client connection: {e}")
        finally:
            try:
                client_socket.close()
            except:
                pass
    
    def handle_connect_request(self, client_socket, url, headers):
        """Handle HTTPS CONNECT requests"""
        try:
            # Parse host and port
            if ':' in url:
                host, port = url.split(':')
                port = int(port)
            else:
                host = url
                port = 443
            
            # Connect to target server
            try:
                server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                server_socket.settimeout(10)
                server_socket.connect((host, port))
                
                # Send 200 Connection Established
                client_socket.send(b'HTTP/1.1 200 Connection Established\r\n\r\n')
                
                # Start tunneling
                self.tunnel_data(client_socket, server_socket)
                
            except Exception as e:
                logging.error(f"Error connecting to {host}:{port} - {e}")
                client_socket.send(b'HTTP/1.1 502 Bad Gateway\r\n\r\n')
                
        except Exception as e:
            logging.error(f"Error in CONNECT handler: {e}")
    
    def handle_http_request(self, client_socket, parsed_request, client_address):
        """Handle HTTP requests"""
        try:
            method = parsed_request['method']
            url = parsed_request['url']
            headers = parsed_request['headers']
            body = parsed_request['body']
            
            # Get host from headers or URL
            host = headers.get('Host', '')
            if not host and url.startswith('http'):
                parsed_url = urlparse(url)
                host = parsed_url.netloc
                url = parsed_url.path + ('?' + parsed_url.query if parsed_url.query else '')
            
            if not host:
                client_socket.send(b'HTTP/1.1 400 Bad Request\r\n\r\n')
                return
            
            # Build full URL
            if not url.startswith('http'):
                url = f"http://{host}{url}"
            
            # Forward request
            try:
                # Prepare headers for forwarding
                forward_headers = dict(headers)
                forward_headers.pop('Proxy-Connection', None)
                forward_headers.pop('Proxy-Authorization', None)
                
                # Make request to target server
                response = requests.request(
                    method=method,
                    url=url,
                    headers=forward_headers,
                    data=body if body else None,
                    allow_redirects=False,
                    timeout=30,
                    stream=True
                )
                
                # Build response
                response_line = f"HTTP/1.1 {response.status_code} {response.reason}\r\n"
                
                # Add response headers
                response_headers = ""
                for key, value in response.headers.items():
                    response_headers += f"{key}: {value}\r\n"
                
                # Send response headers
                full_response = response_line + response_headers + "\r\n"
                client_socket.send(full_response.encode())
                
                # Send response body
                for chunk in response.iter_content(chunk_size=8192):
                    if chunk:
                        client_socket.send(chunk)
                
                # Log response if intercepted
                if self.should_intercept(url) and self.intercepted_data:
                    self.intercepted_data[-1]['response'] = {
                        'status_code': response.status_code,
                        'headers': dict(response.headers),
                        'body_preview': str(response.content[:500]) if hasattr(response, 'content') else ''
                    }
                
            except Exception as e:
                logging.error(f"Error forwarding HTTP request: {e}")
                error_response = "HTTP/1.1 502 Bad Gateway\r\n\r\nProxy Error"
                client_socket.send(error_response.encode())
                
        except Exception as e:
            logging.error(f"Error handling HTTP request: {e}")
    
    def tunnel_data(self, client_socket, server_socket):
        """Tunnel data between client and server for HTTPS"""
        def forward_data(source, destination, direction):
            try:
                while True:
                    ready, _, _ = select.select([source], [], [], 1)
                    if ready:
                        data = source.recv(8192)
                        if not data:
                            break
                        destination.send(data)
            except Exception as e:
                logging.debug(f"Tunneling {direction} ended: {e}")
        
        # Start bidirectional forwarding
        client_to_server = threading.Thread(
            target=forward_data, 
            args=(client_socket, server_socket, "client->server")
        )
        server_to_client = threading.Thread(
            target=forward_data, 
            args=(server_socket, client_socket, "server->client")
        )
        
        client_to_server.daemon = True
        server_to_client.daemon = True
        
        client_to_server.start()
        server_to_client.start()
        
        # Wait for both threads to complete
        client_to_server.join(timeout=300)  # 5 minute timeout
        server_to_client.join(timeout=300)
        
        # Close sockets
        try:
            server_socket.close()
        except:
            pass
    
    def start_proxy_server(self):
        """Start the MITM proxy server"""
        try:
            self.running = True
            self.server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            self.server_socket.bind((PROXY_HOST, PROXY_PORT))
            self.server_socket.listen(self.max_connections)
            
            logging.info(f"MITM Proxy server started on {PROXY_HOST}:{PROXY_PORT}")
            
            while self.running:
                try:
                    self.server_socket.settimeout(1)
                    client_socket, client_address = self.server_socket.accept()
                    
                    # Handle connection in separate thread
                    client_thread = threading.Thread(
                        target=self.handle_client_connection,
                        args=(client_socket, client_address)
                    )
                    client_thread.daemon = True
                    client_thread.start()
                    
                except socket.timeout:
                    continue
                except Exception as e:
                    if self.running:
                        logging.error(f"Error accepting connection: {e}")
                        
        except Exception as e:
            logging.error(f"Error starting proxy server: {e}")
        finally:
            self.stop_proxy_server()
    
    def stop_proxy_server(self):
        """Stop the MITM proxy server"""
        self.running = False
        if self.server_socket:
            try:
                self.server_socket.close()
            except:
                pass
            self.server_socket = None
        logging.info("MITM Proxy server stopped")
    
    def get_intercepted_data(self):
        """Get all intercepted data"""
        return self.intercepted_data.copy()
    
    def clear_intercepted_data(self):
        """Clear intercepted data"""
        self.intercepted_data.clear()
    
    def get_stats(self):
        """Get proxy statistics"""
        return {
            'running': self.running,
            'total_intercepted': len(self.intercepted_data),
            'blocked_domains': list(self.blocked_domains),
            'allowed_domains': list(self.allowed_domains),
            'intercept_patterns': self.intercept_patterns
        }

# Global MITM proxy instance
mitm_proxy = MITMProxy()

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

# DeepFace API Endpoints
@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model': MODEL_NAME,
        'confidence_threshold': CONFIDENCE_THRESHOLD,
        'mitm_proxy': mitm_proxy.get_stats()
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

# MITM Proxy Management Endpoints
@app.route('/mitm/start', methods=['POST'])
def start_mitm_proxy():
    """Start the MITM proxy server"""
    try:
        if mitm_proxy.running:
            return jsonify({
                'success': False,
                'message': 'MITM proxy is already running'
            })
        
        # Start proxy in separate thread
        proxy_thread = threading.Thread(target=mitm_proxy.start_proxy_server)
        proxy_thread.daemon = True
        proxy_thread.start()
        
        # Wait a moment to check if it started successfully
        time.sleep(0.5)
        
        return jsonify({
            'success': True,
            'message': f'MITM proxy started on port {PROXY_PORT}',
            'proxy_host': PROXY_HOST,
            'proxy_port': PROXY_PORT,
            'configuration': f'Set browser proxy to HTTP {PROXY_HOST}:{PROXY_PORT}'
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to start MITM proxy: {str(e)}'}), 500

@app.route('/mitm/stop', methods=['POST'])
def stop_mitm_proxy():
    """Stop the MITM proxy server"""
    try:
        mitm_proxy.stop_proxy_server()
        
        return jsonify({
            'success': True,
            'message': 'MITM proxy stopped successfully'
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to stop MITM proxy: {str(e)}'}), 500

@app.route('/mitm/status', methods=['GET'])
def mitm_status():
    """Get MITM proxy status and statistics"""
    stats = mitm_proxy.get_stats()
    stats.update({
        'proxy_host': PROXY_HOST,
        'proxy_port': PROXY_PORT,
        'log_file': INTERCEPT_LOG_FILE
    })
    return jsonify(stats)

@app.route('/mitm/intercepted', methods=['GET'])
def get_intercepted_requests():
    """Get intercepted requests"""
    try:
        data = mitm_proxy.get_intercepted_data()
        
        # Optional filters
        limit = request.args.get('limit', type=int)
        method_filter = request.args.get('method', '').upper()
        domain_filter = request.args.get('domain', '').lower()
        
        # Apply filters
        filtered_data = data
        
        if method_filter:
            filtered_data = [req for req in filtered_data if req.get('method', '').upper() == method_filter]
        
        if domain_filter:
            filtered_data = [req for req in filtered_data if domain_filter in req.get('url', '').lower()]
        
        if limit:
            filtered_data = filtered_data[-limit:]
        
        return jsonify({
            'success': True,
            'intercepted_requests': filtered_data,
            'total_count': len(filtered_data),
            'unfiltered_count': len(data)
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to get intercepted requests: {str(e)}'}), 500

@app.route('/mitm/clear', methods=['POST'])
def clear_intercepted_requests():
    """Clear all intercepted requests"""
    try:
        mitm_proxy.clear_intercepted_data()
        
        return jsonify({
            'success': True,
            'message': 'Intercepted requests cleared successfully'
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to clear intercepted requests: {str(e)}'}), 500

@app.route('/mitm/config', methods=['POST'])
def configure_mitm():
    """Configure MITM proxy settings"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No configuration data provided'}), 400
        
        # Configure intercept patterns
        if 'intercept_patterns' in data:
            mitm_proxy.intercept_patterns = data['intercept_patterns']
        
        # Configure blocked domains
        if 'blocked_domains' in data:
            mitm_proxy.blocked_domains = set(data['blocked_domains'])
        
        # Configure allowed domains
        if 'allowed_domains' in data:
            mitm_proxy.allowed_domains = set(data['allowed_domains'])
        
        return jsonify({
            'success': True,
            'message': 'MITM proxy configured successfully',
            'configuration': mitm_proxy.get_stats()
        })
        
    except Exception as e:
        return jsonify({'error': f'Failed to configure MITM proxy: {str(e)}'}), 500

@app.route('/mitm/search', methods=['POST'])
def search_intercepted_requests():
    """Search intercepted requests"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No search criteria provided'}), 400
        
        search_term = data.get('search_term', '').lower()
        method_filter = data.get('method', '').upper()
        domain_filter = data.get('domain', '').lower()
        
        all_requests = mitm_proxy.get_intercepted_data()
        results = []
        
        for req in all_requests:
            match = True
            
            # Search term in URL, headers, or body
            if search_term:
                searchable = f"{req.get('url', '')} {str(req.get('headers', {}))} {req.get('body', '')}".lower()
                if search_term not in searchable:
                    match = False
            
            # Method filter
            if method_filter and req.get('method', '').upper() != method_filter:
                match = False
            
            # Domain filter
            if domain_filter and domain_filter not in req.get('url', '').lower():
                match = False
            
            if match:
                results.append(req)
        
        return jsonify({
            'success': True,
            'results': results,
            'total_found': len(results),
            'total_searched': len(all_requests)
        })
        
    except Exception as e:
        return jsonify({'error': f'Search failed: {str(e)}'}), 500

@app.errorhandler(413)
def too_large(e):
    return jsonify({'error': 'Request too large'}), 413

@app.errorhandler(500)
def internal_error(e):
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    print("="*60)
    print("DeepFace API Server with MITM Proxy")
    print("="*60)
    print(f"Face database path: {os.path.abspath(FACE_DB_PATH)}")
    print(f"DeepFace model: {MODEL_NAME}")
    print(f"Confidence threshold: {CONFIDENCE_THRESHOLD}")
    print(f"MITM Proxy will run on: {PROXY_HOST}:{PROXY_PORT}")
    print(f"Intercept log file: {INTERCEPT_LOG_FILE}")
    print("="*60)
    print("Main API Server: http://localhost:5001")
    print()
    print("DeepFace Endpoints:")
    print("  GET  /health - Health check")
    print("  POST /register_face - Register new face")
    print("  POST /recognize_face - Recognize face")
    print("  GET  /get_registered_users - List users")
    print("  POST /delete_user - Delete user")
    print()
    print("MITM Proxy Management:")
    print("  POST /mitm/start - Start MITM proxy")
    print("  POST /mitm/stop - Stop MITM proxy")
    print("  GET  /mitm/status - Get proxy status")
    print("  GET  /mitm/intercepted - Get intercepted requests")
    print("  POST /mitm/clear - Clear intercepted data")
    print("  POST /mitm/config - Configure proxy settings")
    print("  POST /mitm/search - Search intercepted requests")
    print()
    print("Browser Configuration:")
    print(f"  Set HTTP Proxy: {PROXY_HOST}:{PROXY_PORT}")
    print("  Chrome: chrome --proxy-server=http://127.0.0.1:8888")
    print("  Firefox: Preferences → Network → Manual proxy")
    print("="*60)
    
    app.run(host='0.0.0.0', port=5001, debug=True) 