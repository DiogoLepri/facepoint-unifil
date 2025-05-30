<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DeepFaceService
{
    protected $baseUrl;
    protected $timeout;
    protected $confidenceThreshold;

    public function __construct()
    {
        $this->baseUrl = config('deepface.api_url', 'http://localhost:5000');
        $this->timeout = config('deepface.timeout', 30);
        $this->confidenceThreshold = config('deepface.confidence_threshold', 75);
    }

    /**
     * Check if DeepFace API server is healthy
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . '/health');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Health check failed',
                'status' => $response->status()
            ];
        } catch (Exception $e) {
            Log::error('DeepFace health check failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'API server unreachable: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Register a face for a user
     */
    public function registerFace(int $userId, string $imageData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/register_face', [
                    'user_id' => $userId,
                    'image_data' => $imageData
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Face registered successfully', [
                    'user_id' => $userId,
                    'total_images' => $data['total_images'] ?? 1
                ]);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::warning('Face registration failed', [
                'user_id' => $userId,
                'error' => $errorData['error'] ?? 'Unknown error',
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $errorData['error'] ?? 'Registration failed',
                'status' => $response->status()
            ];
        } catch (Exception $e) {
            Log::error('Face registration exception: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'error' => 'API request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recognize a face against registered users
     */
    public function recognizeFace(string $imageData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/recognize_face', [
                    'image_data' => $imageData
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success']) {
                    Log::info('Face recognized successfully', [
                        'user_id' => $data['user_id'] ?? null,
                        'confidence' => $data['confidence'] ?? null
                    ]);
                } else {
                    Log::info('Face recognition failed', [
                        'message' => $data['message'] ?? 'No match found',
                        'confidence' => $data['confidence'] ?? null
                    ]);
                }

                return [
                    'success' => $data['success'],
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            Log::warning('Face recognition request failed', [
                'error' => $errorData['error'] ?? 'Unknown error',
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'error' => $errorData['error'] ?? 'Recognition failed',
                'status' => $response->status()
            ];
        } catch (Exception $e) {
            Log::error('Face recognition exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'API request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all registered users
     */
    public function getRegisteredUsers(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . '/get_registered_users');

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Retrieved registered users', [
                    'total_users' => $data['total_users'] ?? 0
                ]);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['error'] ?? 'Failed to get users',
                'status' => $response->status()
            ];
        } catch (Exception $e) {
            Log::error('Get registered users exception: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'API request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a user's face data
     */
    public function deleteUser(int $userId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/delete_user', [
                    'user_id' => $userId
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('User face data deleted', [
                    'user_id' => $userId,
                    'success' => $data['success']
                ]);

                return [
                    'success' => $data['success'],
                    'data' => $data
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['error'] ?? 'Failed to delete user',
                'status' => $response->status()
            ];
        } catch (Exception $e) {
            Log::error('Delete user exception: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'error' => 'API request failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate base64 image data
     */
    public function validateImageData(string $imageData): bool
    {
        // Check if it's a valid base64 string
        if (!preg_match('/^data:image\/[a-zA-Z]+;base64,/', $imageData)) {
            return false;
        }

        // Extract base64 part
        $base64Data = substr($imageData, strpos($imageData, ',') + 1);
        
        // Validate base64
        if (!base64_decode($base64Data, true)) {
            return false;
        }

        // Check size (approximate, base64 is ~33% larger than original)
        $maxSize = config('deepface.max_image_size', 5242880); // 5MB
        if (strlen($base64Data) > ($maxSize * 1.33)) {
            return false;
        }

        return true;
    }

    /**
     * Get confidence threshold
     */
    public function getConfidenceThreshold(): float
    {
        return $this->confidenceThreshold;
    }

    /**
     * Check if confidence meets threshold
     */
    public function meetsConfidenceThreshold(float $confidence): bool
    {
        return $confidence >= $this->confidenceThreshold;
    }

    /**
     * Format error response for API
     */
    public function formatErrorResponse(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];
    }

    /**
     * Format success response for API
     */
    public function formatSuccessResponse(array $data, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
}