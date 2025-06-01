<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DeepFace API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the DeepFace Python API integration
    |
    */

    // API Server Settings
    'api_url' => env('DEEPFACE_API_URL', 'http://localhost:5001'),
    
    // Request timeout in seconds
    'timeout' => env('DEEPFACE_TIMEOUT', 30),
    
    // Recognition confidence threshold (0-100)
    'confidence_threshold' => env('DEEPFACE_CONFIDENCE_THRESHOLD', 75),
    
    // Face descriptor distance threshold for recognition (lower = stricter)
    'recognition_threshold' => env('DEEPFACE_RECOGNITION_THRESHOLD', 0.4),
    
    // Maximum image size in bytes (5MB default)
    'max_image_size' => env('DEEPFACE_MAX_IMAGE_SIZE', 5242880),
    
    // Model configuration
    'model' => [
        'name' => env('DEEPFACE_MODEL', 'Facenet512'),
        'detector_backend' => env('DEEPFACE_DETECTOR', 'opencv'),
        'distance_metric' => env('DEEPFACE_DISTANCE_METRIC', 'cosine'),
    ],
    
    // Face database settings
    'database' => [
        'path' => env('DEEPFACE_DB_PATH', 'face_database'),
        'user_prefix' => env('DEEPFACE_USER_PREFIX', 'user_'),
        'image_format' => env('DEEPFACE_IMAGE_FORMAT', 'jpg'),
    ],
    
    // Security settings
    'security' => [
        'enforce_detection' => env('DEEPFACE_ENFORCE_DETECTION', true),
        'min_face_size' => env('DEEPFACE_MIN_FACE_SIZE', 50),
        'max_faces_per_user' => env('DEEPFACE_MAX_FACES_PER_USER', 10),
    ],
    
    // Logging settings
    'logging' => [
        'enabled' => env('DEEPFACE_LOGGING_ENABLED', true),
        'level' => env('DEEPFACE_LOGGING_LEVEL', 'info'),
        'log_recognition_attempts' => env('DEEPFACE_LOG_RECOGNITION', true),
        'log_registration_attempts' => env('DEEPFACE_LOG_REGISTRATION', true),
    ],
    
    // Performance settings
    'performance' => [
        'cache_models' => env('DEEPFACE_CACHE_MODELS', true),
        'parallel_processing' => env('DEEPFACE_PARALLEL_PROCESSING', false),
        'batch_size' => env('DEEPFACE_BATCH_SIZE', 1),
    ],
    
    // Error handling
    'error_handling' => [
        'retry_attempts' => env('DEEPFACE_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('DEEPFACE_RETRY_DELAY', 1), // seconds
        'fallback_enabled' => env('DEEPFACE_FALLBACK_ENABLED', false),
    ],
    
    // Development settings
    'development' => [
        'debug_mode' => env('DEEPFACE_DEBUG', false),
        'save_debug_images' => env('DEEPFACE_SAVE_DEBUG_IMAGES', false),
        'mock_responses' => env('DEEPFACE_MOCK_RESPONSES', false),
    ],
];