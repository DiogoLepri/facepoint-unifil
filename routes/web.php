<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RecognitionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas
Route::get('/', function () {
    return redirect()->route('login');
});

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', function() {
    return redirect()->route('login')->with('info', 'Para fazer logout, use o botão Sair.');
})->name('logout.get');
Route::post('/facial-login', [AuthController::class, 'facialLogin'])->name('facial.login');
Route::post('/facial-login/confirm', [AuthController::class, 'confirmFacialLogin'])->name('facial.login.confirm');
Route::post('/facial-login/reject', [AuthController::class, 'rejectFacialLogin'])->name('facial.login.reject');

// API para reconhecimento facial
Route::post('/api/attendance/verify', [AttendanceController::class, 'verify']);
Route::get('/api/attendance/status', [AttendanceController::class, 'status']);

// Rotas protegidas por autenticação
Route::middleware(['auth', \App\Http\Middleware\StudentMiddleware::class])->group(function () {
    // Dashboard do aluno
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Perfil
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [DashboardController::class, 'updateProfile'])->name('profile.update');
    
    // Registros faciais
    Route::get('/facial-registration', [RecognitionController::class, 'create'])->name('facial.registration');
    Route::post('/facial-registration', [RecognitionController::class, 'store'])->name('facial.store');
    
    // Registros de ponto
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history')->middleware(\App\Http\Middleware\CheckEmailLogin::class);
});

// Rotas administrativas  
Route::middleware(['auth'])->prefix('admin')->group(function () {
    // Dashboard admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    
    // Usuários (excluindo create, store, e show)
    Route::resource('users', UserController::class)->except(['create', 'store', 'show']);

    // Soft delete actions
    Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('/users/{id}/force', [UserController::class, 'forceDestroy'])->name('users.force-destroy');

    // Relatórios
    Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');
    Route::post('/reports/generate', [AdminDashboardController::class, 'generateReport'])->name('admin.reports.generate');
});

// Rota de registro de ponto
Route::post('/register-attendance', [App\Http\Controllers\AttendanceController::class, 'registerAttendance'])
    ->name('attendance.register')
    ->middleware(['auth', \App\Http\Middleware\StudentMiddleware::class]);