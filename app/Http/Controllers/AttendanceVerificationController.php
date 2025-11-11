<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\RecognitionRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class AttendanceVerificationController extends Controller
{

    private const FLASK_API_URL = 'http://localhost:5001';

    /**
     * Verifica identidade facial e registra ponto automaticamente.
     *
     * Este endpoint recebe uma imagem base64 do frontend (frame da câmera),
     * compara com todos os embeddings cadastrados no banco MySQL,
     * e se houver match, registra o ponto automaticamente.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * Request JSON esperado:
     * {
     *   "image_data": "data:image/jpeg;base64,/9j/4AAQ..."
     * }
     *
     * Response JSON (match encontrado - sucesso):
     * {
     *   "ok": true,
     *   "recognized_user_id": 123,
     *   "distance": 0.35,
     *   "attendance_id": 456
     * }
     *
     * Response JSON (nenhum match - não autorizado):
     * {
     *   "ok": false,
     *   "reason": "no_match",
     *   "best_distance": 0.52
     * }
     *
     * Response JSON (erro de conexão com Flask):
     * {
     *   "ok": false,
     *   "error": "flask_unreachable"
     * }
     */
    public function verify(Request $request)
    {
        // Validação do campo obrigatório
        $validated = $request->validate([
            'image_data' => 'required|string',
        ]);

        $imageData = $validated['image_data'];

        Log::info("Iniciando verificação facial (reconhecimento + registro de ponto)");

        try { //!Busca embeddings conhecidos e chama Flask API
            // ETAPA 1: Montar array de embeddings conhecidos
            // Busca TODOS os usuários que já têm embedding cadastrado em recognition_records
            Log::info("Buscando embeddings cadastrados no banco MySQL...");

            $knownEmbeddings = [];

            // Query todos os registros de reconhecimento que têm face_descriptor preenchido
            $recognitionRecords = RecognitionRecord::with('user')
                ->whereNotNull('face_descriptor')
                ->get();

            foreach ($recognitionRecords as $record) {
                // Valida se o embedding é um array válido
                // O Model já faz cast para array automaticamente
                $embedding = $record->face_descriptor;

                if (!is_array($embedding) || empty($embedding)) {
                    Log::warning("Embedding inválido para recognition_record_id={$record->id}, user_id={$record->user_id}");
                    continue;
                }

                // Adiciona ao array de embeddings conhecidos
                $knownEmbeddings[] = [
                    'user_id' => $record->user_id,
                    'embedding' => $embedding,
                ];
            }

            if (empty($knownEmbeddings)) {
                Log::warning("Nenhum embedding cadastrado no banco. Impossível fazer reconhecimento.");
                return response()->json([
                    'ok' => false,
                    'error' => 'Nenhum usuário com cadastro facial encontrado no sistema',
                ], 400);
            }

            Log::info("Total de embeddings conhecidos: " . count($knownEmbeddings));

            // ETAPA 2: Chamar API Flask para reconhecimento
            Log::info("Chamando Flask API /recognize_face...");

            $response = Http::timeout(30)->post(self::FLASK_API_URL . '/recognize_face', [
                'image_data' => $imageData,
                'known_embeddings' => $knownEmbeddings,
            ]);

            // Verifica se a requisição foi bem-sucedida
            if (!$response->successful()) {
                Log::error("Flask API retornou erro HTTP", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => 'flask_unreachable',
                    'details' => 'HTTP ' . $response->status(),
                ], 500);
            }

            $flaskResponse = $response->json();

            // ETAPA 3: Interpretar resposta do Flask

            // Caso 1: Match encontrado (success = true)
            if (isset($flaskResponse['success']) && $flaskResponse['success'] === true) {
                $recognizedUserId = $flaskResponse['user_id'] ?? null;
                $distance = $flaskResponse['distance'] ?? null;

                if (!$recognizedUserId) {
                    Log::error("Flask retornou success=true mas sem user_id", [
                        'response' => $flaskResponse,
                    ]);

                    return response()->json([
                        'ok' => false,
                        'error' => 'Resposta inválida do serviço de reconhecimento (user_id ausente)',
                    ], 500);
                }

                Log::info("Match encontrado: user_id={$recognizedUserId}, distance={$distance}");

                // Verifica se o usuário ainda existe (paranoia check)
                $user = User::find($recognizedUserId);
                if (!$user) {
                    Log::error("Usuário reconhecido não existe mais no banco", [
                        'user_id' => $recognizedUserId,
                    ]);

                    return response()->json([
                        'ok' => false,
                        'error' => 'Usuário reconhecido não encontrado no sistema',
                    ], 404);
                }

                // ETAPA 4: Registrar ponto automaticamente
                Log::info("Registrando ponto automático para user_id={$recognizedUserId}");

                $attendanceRecord = AttendanceRecord::create([
                    'user_id' => $recognizedUserId,
                    'entry_time' => now(), // Timestamp do registro de ponto
                    'status' => 'present', // ou o valor que seu sistema usa
                    'punch_type' => 'facial_auto', // Indica que foi reconhecimento facial automático
                ]);

                Log::info("Ponto registrado com sucesso", [
                    'user_id' => $recognizedUserId,
                    'attendance_id' => $attendanceRecord->id,
                    'timestamp' => $attendanceRecord->entry_time,
                ]);

                return response()->json([
                    'ok' => true,
                    'recognized_user_id' => $recognizedUserId,
                    'distance' => $distance,
                    'attendance_id' => $attendanceRecord->id,
                    'user_name' => $user->name,
                ], 200);
            }

            // Caso 2: Nenhum match encontrado (success = false, reason = no_match)
            if (isset($flaskResponse['success']) && $flaskResponse['success'] === false) {
                $reason = $flaskResponse['reason'] ?? 'unknown';
                $bestDistance = $flaskResponse['best_distance'] ?? null;

                Log::info("Nenhum match encontrado", [
                    'reason' => $reason,
                    'best_distance' => $bestDistance,
                ]);

                return response()->json([
                    'ok' => false,
                    'reason' => $reason,
                    'best_distance' => $bestDistance,
                ], 401); // Unauthorized (não reconhecido)
            }

            // Caso 3: Resposta inválida do Flask
            Log::error("Flask retornou resposta inesperada", [
                'response' => $flaskResponse,
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Resposta inválida do serviço de reconhecimento facial',
            ], 500);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Erro de conexão com Flask 
            Log::error("Erro de conexão com Flask API", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'flask_unreachable',
                'details' => 'Não foi possível conectar ao serviço de reconhecimento facial',
            ], 500);

        } catch (\Exception $e) {
            // Erro
            Log::error("Erro inesperado na verificação facial", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Erro interno ao processar verificação facial: ' . $e->getMessage(),
            ], 500);
        }
    }
}
