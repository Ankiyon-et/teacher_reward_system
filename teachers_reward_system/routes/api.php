<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SuperAdmin\SchoolController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\SuperAdmin\SuperAdminController;

Route::post('/login',       [AuthController::class, 'login']);
Route::post('/forgot',      [AuthController::class, 'forgotPassword']);
Route::post('/reset',       [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // USER MANAGEMENT (superadmin + schooladmin)
    
    Route::middleware(CheckRole::class . ':superadmin')->group(function () {
        Route::apiResource('schools', SchoolController::class);
        Route::post('/users/superadmin', [UserManagementController::class, 'createSuperAdmin']);
        Route::prefix('super-admins')->group(function () {
            Route::get('/', [SuperAdminController::class, 'index']);
            Route::put('/{id}', [SuperAdminController::class, 'update']);
            Route::patch('/{id}', [SuperAdminController::class, 'update']);
            Route::delete('/{id}', [SuperAdminController::class, 'destroy']);
        });
    });

    Route::middleware(CheckRole::class . ':superadmin,schooladmin')->group(function () {
        Route::post('/users/school-admin', [UserManagementController::class, 'createSchoolAdmin']);    
    });

    Route::middleware(CheckRole::class . ':schooladmin')->group(function () {
        Route::post('/users/school-admin', [UserManagementController::class, 'createSchoolAdmin']);
        Route::post('/users/teacher', [UserManagementController::class, 'createTeacher']);    
    });
});
