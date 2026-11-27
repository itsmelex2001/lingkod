<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(["status" => "Laravel API is working!"]);
});

Route::post('/patient/login', [\App\Http\Controllers\PatientAuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/patient/verify-otp', [\App\Http\Controllers\PatientAuthController::class, 'verifyOtp'])->middleware('throttle:5,1');
Route::post('/patient/resend-otp', [\App\Http\Controllers\PatientAuthController::class, 'resendOtp'])->middleware('throttle:3,1');
Route::post('/appointments/book', [\App\Http\Controllers\AppointmentBookingController::class, 'store'])->middleware('throttle:10,1');
Route::get('/patient-records', [\App\Http\Controllers\AppointmentBookingController::class, 'patientRecords'])->middleware('admin');
Route::get('/patient/dashboard', [\App\Http\Controllers\PatientDashboardController::class, 'getDashboardData']);
Route::post('/patient/dashboard', [\App\Http\Controllers\PatientDashboardController::class, 'getDashboardData']);
Route::get('/patient/appointments', [\App\Http\Controllers\PatientDashboardController::class, 'getAppointmentsList']);
Route::post('/patient/profile', [\App\Http\Controllers\PatientDashboardController::class, 'updateProfile']);
Route::post('/patient/change-password', [\App\Http\Controllers\PatientDashboardController::class, 'changePassword']);

// Public cached Chatbot FAQ feed (Dr. Leo & Nurse Mika)
Route::get('/public/chatbot-faqs', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'publicList']);

// System Administrator Settings API
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index']);
    Route::put('/settings/general', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateGeneral']);
    Route::put('/settings/scheduling', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateScheduling']);
    Route::put('/settings/auth', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateAuth']);
    Route::post('/settings/auth/test-otp', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'testOtp'])->middleware('throttle:10,1');
    Route::put('/settings/system', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateSystem']);

    Route::get('/faqs', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'index']);
    Route::post('/faqs', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'store']);
    Route::patch('/faqs/reorder', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'reorder']);
    Route::put('/faqs/{faq}', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'update']);
    Route::delete('/faqs/{faq}', [\App\Http\Controllers\Admin\ChatbotFaqController::class, 'destroy']);

    Route::get('/system/backups', [\App\Http\Controllers\Admin\SystemBackupController::class, 'index']);
    Route::post('/system/backups', [\App\Http\Controllers\Admin\SystemBackupController::class, 'trigger'])->middleware('throttle:5,1');
    Route::get('/system/backups/{backup}/download', [\App\Http\Controllers\Admin\SystemBackupController::class, 'download']);

    // User Management (Staff & Patient Portal Accounts)
    Route::prefix('users')->group(function () {
        Route::get('/staff', [\App\Http\Controllers\Admin\UserManagementController::class, 'listStaff']);
        Route::post('/staff', [\App\Http\Controllers\Admin\UserManagementController::class, 'storeStaff']);
        Route::put('/staff/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateStaff']);
        Route::post('/staff/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetStaffPassword'])
            ->middleware('throttle:10,1');

        Route::get('/patients', [\App\Http\Controllers\Admin\UserManagementController::class, 'listPatients']);
        Route::post('/patients/{patient}/temp-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'generatePatientTempPassword'])
            ->middleware('throttle:10,1');
        Route::patch('/patients/{patient}/email', [\App\Http\Controllers\Admin\UserManagementController::class, 'updatePatientEmail']);
        Route::patch('/patients/{patient}/status', [\App\Http\Controllers\Admin\UserManagementController::class, 'togglePatientStatus']);
    });
});
