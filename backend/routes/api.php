<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(["status" => "Laravel API is working!"]);
});

Route::get('/patient/dashboard', [\App\Http\Controllers\PatientDashboardController::class, 'getDashboardData']);
Route::post('/patient/dashboard', [\App\Http\Controllers\PatientDashboardController::class, 'getDashboardData']);
