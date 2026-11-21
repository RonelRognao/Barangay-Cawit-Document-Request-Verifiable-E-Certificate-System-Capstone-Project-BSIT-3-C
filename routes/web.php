<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\ResidentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VerificationController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/verify', [VerificationController::class, 'index'])->name('verify');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/manage-requests', [AdminController::class, 'manageRequests'])->name('requests.index');
    Route::post('/manage-requests/{documentRequest}', [AdminController::class, 'updateRequest'])->name('requests.update');

    Route::get('/issue-certificate', [AdminController::class, 'issueCertificates'])->name('certificates.issue');
    Route::post('/issue-certificate/{documentRequest}', [AdminController::class, 'issueCertificate'])->name('certificates.store');
    Route::post('/revoke-certificate/{certificate}', [AdminController::class, 'revokeCertificate'])->name('certificates.revoke');

    Route::get('/certificate-history', [AdminController::class, 'certificateHistory'])->name('certificates.history');
    Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity_logs');
    Route::post('/toggle-theme', [AdminController::class, 'toggleTheme'])->name('theme.toggle');
});

Route::middleware(['auth', 'role:resident'])->prefix('resident')->name('resident.')->group(function () {
    Route::get('/dashboard', [ResidentController::class, 'dashboard'])->name('dashboard');
    Route::get('/request-document', [DocumentRequestController::class, 'create'])->name('request.create');
    Route::post('/request-document', [DocumentRequestController::class, 'store'])->name('request.store');

    Route::get('/my-requests', [ResidentController::class, 'myRequests'])->name('requests');
    Route::get('/my-certificates', [ResidentController::class, 'myCertificates'])->name('certificates');
    Route::get('/print-certificate/{certificate_no}', [ResidentController::class, 'printCertificate'])->name('certificates.print');

    Route::get('/notifications', [ResidentController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{notification}/read', [ResidentController::class, 'markNotificationRead'])->name('notifications.read');

    Route::get('/profile', [ResidentController::class, 'profile'])->name('profile');
    Route::post('/profile', [ResidentController::class, 'updateProfile'])->name('profile.update');
});
