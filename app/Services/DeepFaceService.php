<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DeepFace Service
 *
 * Serviço para comunicação com a API DeepFace
 *
 * NOTA: Atualmente usando Facenet128 (embeddings de 128 dimensões) devido
 * à indisponibilidade temporária do modelo Facenet512.
 *
 * Fornece reconhecimento facial avançado com embeddings vetoriais
 */
class DeepFaceService
{
    private $apiUrl;
    private $timeout;
    private $confidenceThreshold;
    private $maxImageSize;
    private $retryAttempts;
    private $retryDelay;

    public function __construct()
    {
        $this->apiUrl = config('deepface.api_url', 'http://localhost:5001');
        $this->timeout = config('deepface.timeout', 30);
        $this->confidenceThreshold = config('deepface.confidence_threshold', 75);
        $this->maxImageSize = config('deepface.max_image_size', 5242880); // 5MB
        $this->retryAttempts = config('deepface.retry.attempts', 3);
        $this->retryDelay = config('deepface.retry.delay', 1);
    }

    /**
     * Verifica se a API DeepFace está funcionando
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/health");

            if ($response->successful()) {
                $data = $response->json();
                Log::info('DeepFace API health check passed', $data);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            Log::warning('DeepFace API health check failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'API não está respondendo corretamente'
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace API health check error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida dados de imagem base64
     */
    public function validateImageData($imageData)
    {
        if (empty($imageData)) {
            return false;
        }

        // Verifica se é base64 válido
        if (!preg_match('/^data:image\/(\w+);base64,/', $imageData)) {
            return false;
        }

        // Verifica tamanho
        $imageSize = strlen($imageData);
        if ($imageSize > $this->maxImageSize) {
            Log::warning('Image size exceeds limit', [
                'size' => $imageSize,
                'max_size' => $this->maxImageSize
            ]);
            return false;
        }

        return true;
    }

    /**
     * Registra uma face no sistema DeepFace
     */
    public function registerFace($userId, $imageData)
    {
        try {
            if (!$this->validateImageData($imageData)) {
                return [
                    'success' => false,
                    'error' => 'Dados de imagem inválidos'
                ];
            }

            Log::info('Registering face with DeepFace', ['user_id' => $userId]);

            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay * 1000)
                ->post("{$this->apiUrl}/register", [
                    'user_id' => $userId,
                    'image_data' => $imageData
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Face registered successfully', [
                    'user_id' => $userId,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'data' => $data['data'] ?? []
                ];
            }

            Log::warning('Face registration failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Erro ao registrar face'
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace registration error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se uma face corresponde a um usuário específico
     */
    public function verifyFace($userId, $imageData)
    {
        try {
            if (!$this->validateImageData($imageData)) {
                return [
                    'success' => false,
                    'error' => 'Dados de imagem inválidos'
                ];
            }

            Log::info('Verifying face with DeepFace', ['user_id' => $userId]);

            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay * 1000)
                ->post("{$this->apiUrl}/verify", [
                    'user_id' => $userId,
                    'image_data' => $imageData
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Face verification completed', [
                    'user_id' => $userId,
                    'verified' => $data['verified'] ?? false,
                    'distance' => $data['distance'] ?? null
                ]);

                return [
                    'success' => true,
                    'verified' => $data['verified'] ?? false,
                    'distance' => $data['distance'] ?? null,
                    'threshold' => $data['threshold'] ?? 0.4
                ];
            }

            Log::warning('Face verification failed', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Erro ao verificar face'
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace verification error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reconhece uma face entre todos os usuários registrados
     * A API Python já calcula o descritor facial e retorna confidence
     */
    public function recognizeFace($imageData)
    {
        try {
            if (!$this->validateImageData($imageData)) {
                return [
                    'success' => false,
                    'data' => [
                        'message' => 'Dados de imagem inválidos'
                    ]
                ];
            }

            Log::info('Recognizing face with DeepFace');

            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay * 1000)
                ->post("{$this->apiUrl}/recognize", [
                    'image_data' => $imageData
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    Log::info('Face recognized', [
                        'user_id' => $data['user_id'] ?? null,
                        'distance' => $data['distance'] ?? null,
                        'confidence' => $data['confidence'] ?? null
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'success' => true,
                            'user_id' => $data['user_id'] ?? null,
                            'distance' => $data['distance'] ?? null,
                            'confidence' => $data['confidence'] ?? 0,
                            'message' => 'Face reconhecida com sucesso'
                        ]
                    ];
                }
            }

            Log::info('Face not recognized');

            return [
                'success' => false,
                'data' => [
                    'success' => false,
                    'message' => 'Face não reconhecida',
                    'confidence' => 0
                ]
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace recognition error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'data' => [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'confidence' => 0
                ]
            ];
        }
    }

    /**
     * Remove todas as faces de um usuário
     */
    public function deleteFaces($userId)
    {
        try {
            Log::info('Deleting faces from DeepFace', ['user_id' => $userId]);

            $response = Http::timeout($this->timeout)
                ->delete("{$this->apiUrl}/delete/{$userId}");

            if ($response->successful()) {
                Log::info('Faces deleted successfully', ['user_id' => $userId]);

                return [
                    'success' => true
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro ao remover faces'
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace delete error', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém estatísticas do sistema DeepFace
     */
    public function getStatistics()
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("{$this->apiUrl}/stats");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Erro ao obter estatísticas'
            ];
        } catch (\Exception $e) {
            Log::error('DeepFace stats error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se a confiança atende ao threshold configurado
     * A confidence já vem calculada pela API Python
     */
    public function meetsConfidenceThreshold($confidence)
    {
        return $confidence >= $this->confidenceThreshold;
    }
}
