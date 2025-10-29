<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecognitionRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Controller responsável pelo cadastro facial (enrolment) de usuários.
 *
 * Este controller se comunica com a API Flask stateless (http://localhost:5001)
 * para extrair embeddings faciais usando DeepFace/Facenet512.
 *
 * NÃO salva imagens em disco - apenas armazena o embedding (vetor de 512 floats)
 * no banco de dados MySQL na tabela recognition_records.
 */
class FaceEnrolmentController extends Controller
{
    /**
     * URL da API Flask de reconhecimento facial
     */
    private const FLASK_API_URL = 'http://localhost:5001';

    /**
     * Cadastra ou atualiza o embedding facial de um usuário.
     *
     * Este endpoint recebe uma imagem base64 do frontend, envia para a API Flask
     * extrair o embedding usando Facenet512, e armazena o embedding retornado
     * no campo face_descriptor da tabela recognition_records.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Request JSON esperado:
     * {
     *   "user_id": 123,
     *   "image_data": "data:image/jpeg;base64,/9j/4AAQ..."
     * }
     *
     * Response JSON (sucesso):
     * {
     *   "ok": true,
     *   "message": "Embedding cadastrado/atualizado com sucesso",
     *   "user_id": 123
     * }
     *
     * Response JSON (erro):
     * {
     *   "ok": false,
     *   "error": "Descrição do erro"
     * }
     */
    public function store(Request $request)
    {
        // Validação dos campos obrigatórios
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'image_data' => 'required|string',
        ]);

        $userId = (int) $validated['user_id'];
        $imageData = $validated['image_data'];

        // ========================================
        // SEGURANÇA: Verificar se o usuário logado está cadastrando a própria biometria
        // ========================================
        $loggedInId = Auth::id();

        if ($loggedInId !== $userId) {
            Log::warning('[ENROL] Tentativa de cadastro de biometria para outro usuário', [
                'logged_in_user_id' => $loggedInId,
                'target_user_id' => $userId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'forbidden_user_mismatch',
                'message' => 'Você só pode cadastrar sua própria biometria',
                'expected_user_id' => $loggedInId,
                'provided_user_id' => $userId,
            ], 403);
        }

        // ========================================
        // LOG INICIAL
        // ========================================
        Log::info('[ENROL] Iniciando envio para Flask', [
            'user_id' => $userId,
            'has_image_data' => strlen($imageData) > 30,
            'image_data_length' => strlen($imageData),
        ]);

        try {
            // Verifica se o usuário existe
            $user = User::findOrFail($userId);

            // ========================================
            // CHAMAR FLASK API
            // ========================================
            Log::info('[ENROL] Chamando Flask API /extract_embedding para user_id=' . $userId);

            $response = Http::timeout(30)->post(self::FLASK_API_URL . '/extract_embedding', [
                'image_data' => $imageData,
            ]);

            // Verifica se a requisição foi bem-sucedida
            if (!$response->successful()) {
                Log::error('[ENROL] Flask não respondeu corretamente', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'stage' => 'flask_unreachable',
                    'error' => 'Erro ao comunicar com serviço de reconhecimento facial',
                    'http_status' => $response->status(),
                ], 500);
            }

            $flaskResponse = $response->json();

            // Log da resposta do Flask
            Log::info('[ENROL] Resposta Flask', $flaskResponse ?? ['empty' => true]);

            // Valida se a resposta da API Flask contém os dados esperados
            if (!isset($flaskResponse['success']) || $flaskResponse['success'] !== true) {
                $errorMessage = $flaskResponse['error'] ?? 'Erro desconhecido ao extrair embedding';

                Log::warning('[ENROL] Flask retornou success=false', [
                    'user_id' => $userId,
                    'flask_error' => $errorMessage,
                    'flask_response' => $flaskResponse,
                ]);

                return response()->json([
                    'ok' => false,
                    'stage' => 'flask_failed',
                    'error' => $errorMessage,
                    'flask_response' => $flaskResponse,
                ], 400);
            }

            if (!isset($flaskResponse['embedding']) || !is_array($flaskResponse['embedding'])) {
                Log::error('[ENROL] Flask não retornou embedding válido', [
                    'user_id' => $userId,
                    'has_embedding' => isset($flaskResponse['embedding']),
                    'embedding_type' => isset($flaskResponse['embedding']) ? gettype($flaskResponse['embedding']) : 'null',
                ]);

                return response()->json([
                    'ok' => false,
                    'stage' => 'invalid_embedding',
                    'error' => 'Resposta inválida do serviço de reconhecimento facial',
                ], 500);
            }

            $embedding = $flaskResponse['embedding'];
            $dimensions = $flaskResponse['dimensions'] ?? count($embedding);

            Log::info('[ENROL] Embedding extraído com sucesso', [
                'user_id' => $userId,
                'dimensions' => $dimensions,
                'embedding_sample' => array_slice($embedding, 0, 3), // Primeiros 3 valores para debug
            ]);

            // ========================================
            // SALVAR NO BANCO
            // ========================================
            $recognitionRecord = RecognitionRecord::where('user_id', $userId)->first();

            if ($recognitionRecord) {
                // Atualiza o embedding existente
                $recognitionRecord->face_descriptor = $embedding; // Cast automático para JSON pelo Model
                $recognitionRecord->capture_type = 'enrolment';
                $recognitionRecord->updated_at = now();
                $recognitionRecord->save();

                Log::info('[ENROL] Embedding atualizado para user_id=' . $userId, [
                    'record_id' => $recognitionRecord->id,
                    'dimensions' => $dimensions,
                ]);
            } else {
                // Cria novo registro de reconhecimento
                $recognitionRecord = RecognitionRecord::create([
                    'user_id' => $userId,
                    'face_descriptor' => $embedding, // Cast automático para JSON pelo Model
                    'capture_type' => 'enrolment',
                ]);

                Log::info('[ENROL] Novo embedding criado para user_id=' . $userId, [
                    'record_id' => $recognitionRecord->id,
                    'dimensions' => $dimensions,
                ]);
            }

            // ========================================
            // SUCESSO!
            // ========================================
            Log::info('[ENROL] Cadastro facial concluído com sucesso', [
                'user_id' => $userId,
                'record_id' => $recognitionRecord->id,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Embedding cadastrado/atualizado com sucesso',
                'user_id' => $userId,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ENROL] Usuário não encontrado', [
                'user_id' => $userId,
            ]);

            return response()->json([
                'ok' => false,
                'stage' => 'user_not_found',
                'error' => 'Usuário não encontrado',
            ], 404);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[ENROL] Erro de conexão com Flask API', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'stage' => 'flask_connection_error',
                'error' => 'Não foi possível conectar ao serviço de reconhecimento facial. Verifique se a API Flask está rodando.',
            ], 503);

        } catch (\Exception $e) {
            Log::error('[ENROL] Erro inesperado no cadastro facial', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'stage' => 'unexpected_error',
                'error' => 'Erro interno ao processar cadastro facial: ' . $e->getMessage(),
            ], 500);
        }
    }
}
