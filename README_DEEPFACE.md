# DeepFace Integration Documentation

This document explains how to use the DeepFace face recognition system integrated into the FacePoint attendance application.

## Overview

The application now uses DeepFace library with Facenet512 model for improved face recognition accuracy. The system consists of:

1. **Python Flask API Server** - Handles face recognition using DeepFace
2. **Laravel Service Layer** - Communicates with the Python API
3. **Frontend Interface** - Captures images and sends to the API

## Installation Requirements

### Python Dependencies

```bash
# Install Python dependencies
pip install deepface==0.0.79
pip install tensorflow==2.13.0
pip install opencv-python==4.8.1.78
pip install flask==2.3.3
pip install flask-cors==4.0.0
pip install pillow==10.0.1
pip install numpy==1.24.3
```

Or use the provided requirements file:

```bash
pip install -r requirements.txt
```

### System Requirements

- Python 3.8 or higher
- At least 4GB RAM (8GB recommended)
- GPU support optional but recommended for faster processing

## Quick Start

### 1. Start the DeepFace API Server

```bash
# Option 1: Use the startup script
./start_deepface.sh

# Option 2: Manual start
python deepface_server.py
```

The server will start on `http://localhost:5000`

### 2. Configure Laravel Environment

Add these variables to your `.env` file:

```env
DEEPFACE_API_URL=http://localhost:5000
DEEPFACE_TIMEOUT=30
DEEPFACE_CONFIDENCE_THRESHOLD=75
DEEPFACE_MAX_IMAGE_SIZE=5242880
```

### 3. Test the Integration

1. Start the Laravel application: `php artisan serve`
2. Register a new user or login
3. Go to face registration page
4. Capture and register your face
5. Test attendance registration using face recognition

## API Endpoints

### DeepFace Flask Server

- `GET /health` - Health check
- `POST /register_face` - Register a new face
- `POST /recognize_face` - Recognize a face
- `GET /get_registered_users` - List registered users
- `POST /delete_user` - Delete user's face data

### Laravel API Routes

- `GET /api/deepface/health` - Check DeepFace server health
- `GET /api/deepface/users` - Get registered users
- `DELETE /api/deepface/users/{userId}` - Delete user face data
- `POST /api/deepface/validate-image` - Validate image data
- `POST /api/attendance/verify` - Verify attendance (legacy endpoint)

## Configuration

### DeepFace Settings

Key configuration options in `config/deepface.php`:

```php
'confidence_threshold' => 75, // Minimum confidence for recognition
'model' => [
    'name' => 'Facenet512',    // AI model to use
    'detector_backend' => 'opencv',
    'distance_metric' => 'cosine',
],
'timeout' => 30, // API request timeout
'max_image_size' => 5242880, // 5MB max image size
```

### Environment Variables

All DeepFace settings can be overridden with environment variables:

```env
DEEPFACE_API_URL=http://localhost:5000
DEEPFACE_CONFIDENCE_THRESHOLD=75
DEEPFACE_MODEL=Facenet512
DEEPFACE_DETECTOR=opencv
DEEPFACE_DISTANCE_METRIC=cosine
DEEPFACE_TIMEOUT=30
DEEPFACE_MAX_IMAGE_SIZE=5242880
DEEPFACE_DB_PATH=face_database
DEEPFACE_DEBUG=false
```

## Face Database Structure

The face database is organized as follows:

```
face_database/
├── user_1/
│   ├── face_1.jpg
│   ├── face_2.jpg
│   └── ...
├── user_2/
│   ├── face_1.jpg
│   └── ...
└── ...
```

Each user can have multiple face images for better recognition accuracy.

## Technical Specifications

### Model Performance

- **Model**: Facenet512
- **Accuracy**: ~99.65% on LFW dataset
- **Confidence Threshold**: 75% (configurable)
- **Processing Time**: ~1-3 seconds per recognition
- **Image Size Limit**: 5MB

### Security Features

- Image validation and sanitization
- Size limits to prevent DoS attacks
- Error handling and logging
- CSRF protection
- Input validation

## Troubleshooting

### Common Issues

1. **DeepFace server not starting**
   - Check Python installation: `python3 --version`
   - Install missing dependencies: `pip install -r requirements.txt`
   - Check port availability: `lsof -i :5000`

2. **Low recognition accuracy**
   - Ensure good lighting during registration
   - Register multiple images per user
   - Adjust confidence threshold in config
   - Check image quality (resolution, focus)

3. **Slow performance**
   - Consider using GPU acceleration
   - Reduce image size before sending
   - Enable model caching
   - Use parallel processing if available

4. **API connection errors**
   - Verify DeepFace server is running
   - Check network connectivity
   - Verify API URL in configuration
   - Check firewall settings

### Debugging

Enable debug mode:

```env
DEEPFACE_DEBUG=true
DEEPFACE_SAVE_DEBUG_IMAGES=true
```

Check logs:

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# DeepFace server logs
# Check console output where deepface_server.py is running
```

## Performance Optimization

### For Production

1. **Use GPU acceleration**:
   ```bash
   pip install tensorflow-gpu
   ```

2. **Enable model caching**:
   ```env
   DEEPFACE_CACHE_MODELS=true
   ```

3. **Optimize image processing**:
   - Resize images before sending
   - Use JPEG compression
   - Implement client-side image validation

4. **Load balancing**:
   - Run multiple DeepFace server instances
   - Use nginx or similar for load balancing

### Memory Management

- Monitor memory usage during operation
- Consider batch processing for multiple registrations
- Implement cleanup routines for temporary files

## Migration from face-api.js

The migration includes:

1. **Frontend changes**:
   - Removed face-api.js CDN dependency
   - Updated JavaScript to capture and send images
   - Added confidence score display

2. **Backend changes**:
   - Replaced Euclidean distance calculation
   - Added DeepFace service integration
   - Updated verification logic

3. **Database changes**:
   - No database schema changes required
   - Face descriptors now stored as DeepFace metadata

## Support

For issues specific to:

- **DeepFace library**: Check [DeepFace GitHub](https://github.com/serengil/deepface)
- **TensorFlow**: Check [TensorFlow documentation](https://tensorflow.org/install)
- **OpenCV**: Check [OpenCV installation guide](https://docs.opencv.org/master/d0/de3/tutorial_py_intro.html)

## License

This integration maintains compatibility with:
- DeepFace MIT License
- TensorFlow Apache 2.0 License
- OpenCV Apache 2.0 License