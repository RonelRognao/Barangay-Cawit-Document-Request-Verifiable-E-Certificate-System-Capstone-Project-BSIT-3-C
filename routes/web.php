<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\ResidentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\Admin\OfficialController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\BlotterController;
use App\Http\Controllers\Admin\ResidentRegistryController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WalkInRequestController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/verify/{code?}', [VerificationController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('verify');

Route::middleware(['auth', 'role:admin,punong_barangay,secretary,treasurer,clerk'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/print-certificate/{certificate_no}', [AdminController::class, 'printCertificate'])->name('certificates.print');

        Route::get('/manage-requests', [AdminController::class, 'manageRequests'])->name('requests.index');
        Route::post('/manage-requests/{documentRequest}', [AdminController::class, 'updateRequest'])
            ->middleware('role:admin,punong_barangay,secretary,clerk')
            ->name('requests.update');
        Route::middleware('role:admin,punong_barangay,secretary,clerk')->group(function () {
            Route::get('/walk-in', [WalkInRequestController::class, 'create'])->name('walk_in.create');
            Route::post('/walk-in', [WalkInRequestController::class, 'store'])->name('walk_in.store');
        });

        Route::post('/manage-requests/{documentRequest}/payment', [AdminController::class, 'recordPayment'])
            ->middleware('role:admin,punong_barangay,secretary,treasurer')
            ->name('requests.payment');
        Route::get('/manage-requests/{documentRequest}/attachment', [AdminController::class, 'viewRequestAttachment'])
            ->name('requests.attachment');

        Route::prefix('residents')->name('residents.')->group(function () {
            Route::get('/', [ResidentRegistryController::class, 'index'])->name('index');
            Route::get('/{resident}/document/{type}', [ResidentRegistryController::class, 'viewDocument'])->name('document');

            Route::middleware('role:admin,punong_barangay,secretary')->group(function () {
                Route::post('/puroks', [ResidentRegistryController::class, 'storePurok'])->name('puroks.store');
                Route::post('/puroks/{purok}/toggle', [ResidentRegistryController::class, 'togglePurok'])->name('puroks.toggle');
                Route::post('/{resident}/verify', [ResidentRegistryController::class, 'verifyKyc'])->name('verify');
            });
        });

        Route::prefix('blotter')->name('blotter.')->group(function () {
            Route::get('/', [BlotterController::class, 'index'])->name('index');
            Route::post('/', [BlotterController::class, 'store'])
                ->middleware('role:admin,punong_barangay,secretary,clerk')
                ->name('store');
            Route::post('/{blotter}', [BlotterController::class, 'update'])
                ->middleware('role:admin,punong_barangay,secretary')
                ->name('update');
        });

        Route::get('/issue-certificate', [AdminController::class, 'issueCertificates'])
            ->middleware('role:admin,punong_barangay,secretary')
            ->name('certificates.issue');
        Route::post('/issue-certificate/{documentRequest}', [AdminController::class, 'issueCertificate'])
            ->middleware('role:admin,punong_barangay,secretary')
            ->name('certificates.store');
        Route::post('/revoke-certificate/{certificate}', [AdminController::class, 'revokeCertificate'])
            ->middleware('role:admin,punong_barangay,secretary')
            ->name('certificates.revoke');

        Route::get('/certificate-history', [AdminController::class, 'certificateHistory'])->name('certificates.history');

        Route::middleware('role:admin,punong_barangay,secretary,treasurer')->group(function () {
            Route::get('/activity-logs', [AdminController::class, 'activityLogs'])->name('activity_logs');
            Route::get('/activity-logs/export', [AdminController::class, 'exportActivityLogs'])->name('activity_logs.export');
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
        });

        Route::middleware('role:admin,punong_barangay,secretary')->group(function () {

        Route::prefix('document-types')->name('document_types.')->group(function () {
                Route::get('/', [DocumentTypeController::class, 'index'])->name('index');
                Route::post('/', [DocumentTypeController::class, 'store'])->name('store');
                Route::post('/{documentType}', [DocumentTypeController::class, 'update'])->name('update');
                Route::post('/{documentType}/toggle', [DocumentTypeController::class, 'toggleActive'])->name('toggle');
            });

            Route::prefix('staff')->name('staff.')->group(function () {
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::post('/', [StaffController::class, 'store'])->name('store');
                Route::post('/{staff}', [StaffController::class, 'update'])->name('update');
                Route::post('/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('toggle');
            });

            Route::prefix('officials')->name('officials.')->group(function () {
                Route::get('/', [OfficialController::class, 'index'])->name('index');
                Route::post('/', [OfficialController::class, 'store'])->name('store');
                Route::post('/{official}', [OfficialController::class, 'update'])->name('update');
                Route::post('/{official}/primary', [OfficialController::class, 'setPrimary'])->name('primary');
                Route::post('/{official}/attest', [OfficialController::class, 'setAttesting'])->name('attest');
                Route::post('/{official}/toggle-active', [OfficialController::class, 'toggleActive'])->name('toggle');
                Route::get('/{official}/signature', [OfficialController::class, 'signaturePreview'])->name('signature');
            });
        });

        Route::post('/toggle-theme', [AdminController::class, 'toggleTheme'])->name('theme.toggle');
    });

Route::middleware(['auth', 'role:resident'])->prefix('resident')->name('resident.')->group(function () {
    Route::get('/dashboard', [ResidentController::class, 'dashboard'])->name('dashboard');
    Route::get('/request-document', [DocumentRequestController::class, 'create'])->name('request.create');
    Route::post('/request-document', [DocumentRequestController::class, 'store'])->name('request.store');

    Route::get('/my-requests', [ResidentController::class, 'myRequests'])->name('requests');
    Route::get('/my-requests/{documentRequest}/attachment', [DocumentRequestController::class, 'viewAttachment'])->name('requests.attachment');
    Route::get('/my-certificates', [ResidentController::class, 'myCertificates'])->name('certificates');
    Route::get('/print-certificate/{certificate_no}', [ResidentController::class, 'printCertificate'])->name('certificates.print');

    Route::get('/notifications', [ResidentController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{notification}/read', [ResidentController::class, 'markNotificationRead'])->name('notifications.read');

    Route::get('/profile', [ResidentController::class, 'profile'])->name('profile');
    Route::post('/profile', [ResidentController::class, 'updateProfile'])->name('profile.update');

    Route::get('/profile/document/{type}', [ResidentController::class, 'kycDocument'])->name('profile.document');

    Route::post('/notifications/read-all', [ResidentController::class, 'markAllNotificationsRead'])->name('notifications.read_all');
});
