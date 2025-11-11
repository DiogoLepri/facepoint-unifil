<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DeepFace API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com a API DeepFace para reconhecimento
    | facial.
    |
    | NOTA: Atualmente usando Facenet128 (embeddings de 128 dimensões) devido
    | à indisponibilidade temporária do modelo Facenet512. O Facenet128 oferece
    | desempenho similar com menor uso de recursos computacionais.
    |
    */

    'api_url' => env('DEEPFACE_API_URL', 'http://localhost:5001'),

    'timeout' => env('DEEPFACE_TIMEOUT', 30),

    'confidence_threshold' => env('DEEPFACE_CONFIDENCE_THRESHOLD', 75),

    'max_image_size' => env('DEEPFACE_MAX_IMAGE_SIZE', 5242880), // 5MB

    'model' => env('DEEPFACE_MODEL', 'Facenet512'),

    'detector' => env('DEEPFACE_DETECTOR', 'opencv'),

    'distance_metric' => env('DEEPFACE_DISTANCE_METRIC', 'euclidean'),

    'user_prefix' => env('DEEPFACE_USER_PREFIX', 'user_'),

    'image_format' => env('DEEPFACE_IMAGE_FORMAT', 'jpg'),

    'enforce_detection' => env('DEEPFACE_ENFORCE_DETECTION', true),

    'min_face_size' => env('DEEPFACE_MIN_FACE_SIZE', 50),

    'max_faces_per_user' => env('DEEPFACE_MAX_FACES_PER_USER', 10),

    'logging' => [
        'enabled' => env('DEEPFACE_LOGGING_ENABLED', true),
        'level' => env('DEEPFACE_LOGGING_LEVEL', 'info'),
        'log_recognition' => env('DEEPFACE_LOG_RECOGNITION', true),
        'log_registration' => env('DEEPFACE_LOG_REGISTRATION', true),
    ],

    'cache' => [
        'models' => env('DEEPFACE_CACHE_MODELS', true),
    ],

    'processing' => [
        'parallel' => env('DEEPFACE_PARALLEL_PROCESSING', false),
        'batch_size' => env('DEEPFACE_BATCH_SIZE', 1),
    ],

    'retry' => [
        'attempts' => env('DEEPFACE_RETRY_ATTEMPTS', 3),
        'delay' => env('DEEPFACE_RETRY_DELAY', 1), // seconds
    ],

    'fallback' => [
        'enabled' => env('DEEPFACE_FALLBACK_ENABLED', false),
    ],

    'debug' => env('DEEPFACE_DEBUG', false),

    'save_debug_images' => env('DEEPFACE_SAVE_DEBUG_IMAGES', false),

    'mock_responses' => env('DEEPFACE_MOCK_RESPONSES', false),

    // Recognition threshold (15.0 para euclidean distance com FaceNet512)
    'recognition_threshold' => env('DEEPFACE_RECOGNITION_THRESHOLD', 20.0),

];
