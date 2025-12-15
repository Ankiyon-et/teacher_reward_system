<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SuperAdmin\SchoolController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\SuperAdmin\SuperAdminController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminManagementController;
use App\Http\Controllers\SchoolAdmin\SchoolGradeManagementController;
use App\Http\Controllers\SchoolAdmin\SchoolTeacherManagementController;
use App\Http\Controllers\SuperAdmin\GradeController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherRatingRewardController;
use App\Http\Controllers\ProfileController;


Route::post('/login',       [AuthController::class, 'login']);
Route::post('/forgot',      [AuthController::class, 'forgotPassword']);
Route::post('/reset',       [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    Route::middleware(CheckRole::class . ':superadmin')->group(function () {
        Route::apiResource('schools', SchoolController::class);
        Route::apiResource('grades', GradeController::class);
        Route::post('/users/superadmin', [UserManagementController::class, 'createSuperAdmin']);
        Route::prefix('super-admins')->group(function () {
            Route::get('/', [SuperAdminController::class, 'index']);
            Route::put('/{id}', [SuperAdminController::class, 'update']);
            Route::patch('/{id}', [SuperAdminController::class, 'update']);
            Route::delete('/{id}', [SuperAdminController::class, 'destroy']);
        }); 
        Route::get('/superadmin/dashboard', [DashboardController::class, 'index']);
    });

    Route::middleware(CheckRole::class . ':schooladmin')->group(function () {
        Route::post('/create/school-admins', [UserManagementController::class, 'createSchoolAdmin']);
        Route::get('/list/school-admins', [SchoolAdminManagementController::class, 'index']);
        Route::delete('/delete/school-admins/{adminId}', [SchoolAdminManagementController::class, 'destroy']);

        Route::post('/create/teachers', [UserManagementController::class, 'createTeacher']);
        Route::get('/list/teachers', [SchoolTeacherManagementController::class, 'index']);
        Route::post('/teachers/{teacherId}/assign-grades', [SchoolTeacherManagementController::class, 'assignGrades']);
        Route::delete('/delete/teachers/{teacherId}', [SchoolTeacherManagementController::class, 'destroy']);

        Route::get('/allgrades', [SchoolGradeManagementController::class, 'allGrades']);
        Route::post('/grades/assign', [SchoolGradeManagementController::class, 'assignGradesToSchool']);
        Route::get('/school/grades', [SchoolGradeManagementController::class, 'schoolGrades']);
        Route::delete('/delete/grades/{gradeId}', [SchoolGradeManagementController::class, 'deleteSchoolGrade']);

        Route::get('/schooladmin/dashboard', [SchoolAdminDashboardController::class, 'index']);
    });
    Route::middleware(CheckRole::class . ':teacher')->group(function () {
        Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index']);
        Route::get('/teacher/ratings', [TeacherRatingRewardController::class, 'ratings']);
        Route::get('/teacher/rewards', [TeacherRatingRewardController::class, 'rewards']);
        Route::post('/teacher/withdraw', [TeacherRatingRewardController::class, 'requestWithdrawal']);
    });

    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::get('/profile', [ProfileController::class, 'show']);

});

Route::post('/rate', [ParentController::class, 'rateTeacher']);
Route::post('/reward', [ParentController::class, 'rewardTeacher']);
