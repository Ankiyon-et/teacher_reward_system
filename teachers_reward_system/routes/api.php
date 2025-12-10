<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;

Route::post('/login',       [AuthController::class, 'login']);
Route::post('/forgot',      [AuthController::class, 'forgotPassword']);
Route::post('/reset',       [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // USER MANAGEMENT (superadmin + schooladmin)
    Route::post('/users/superadmin', [UserManagementController::class, 'createSuperAdmin']);
    Route::post('/users/school-admin', [UserManagementController::class, 'createSchoolAdmin']);
    Route::post('/users/teacher', [UserManagementController::class, 'createTeacher']);
});
