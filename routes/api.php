<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Services\DeepFaceService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// DeepFace API routes
Route::prefix('deepface')->group(function () {
    
    // Health check endpoint
    Route::get('/health', function () {
        $deepFaceService = new DeepFaceService();
        $healthCheck = $deepFaceService->healthCheck();
        
        return response()->json($healthCheck, $healthCheck['success'] ? 200 : 503);
    });
    
    // Get registered users
    Route::get('/users', function () {
        $deepFaceService = new DeepFaceService();
        $result = $deepFaceService->getRegisteredUsers();
        
        return response()->json($result, $result['success'] ? 200 : 500);
    });
    
    // Delete user face data
    Route::delete('/users/{userId}', function ($userId) {
        $deepFaceService = new DeepFaceService();
        $result = $deepFaceService->deleteUser((int)$userId);
        
        return response()->json($result, $result['success'] ? 200 : 500);
    });
    
    // Test endpoint for image validation
    Route::post('/validate-image', function (Request $request) {
        $request->validate([
            'image_data' => 'required|string'
        ]);
        
        $deepFaceService = new DeepFaceService();
        $isValid = $deepFaceService->validateImageData($request->image_data);
        
        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'Image data is valid' : 'Invalid image data'
        ]);
    });
});

// Legacy attendance API routes (maintained for backwards compatibility)
Route::prefix('attendance')->group(function () {
    Route::post('/verify', [AttendanceController::class, 'verify']);
    Route::get('/status', [AttendanceController::class, 'status']);
});