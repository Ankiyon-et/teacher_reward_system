<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;
use App\Models\Reward;
use App\Models\Rating;
use App\Models\Grade;
use App\Models\SchoolAdmin;

class SchoolAdminDashboardController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $admin = SchoolAdmin::where('user_id', $user->id)->first();
    if (!$admin) {
        return response()->json(['error' => 'School admin not found'], 404);
    }

    $schoolId = $admin->school_id;

    /*
    |--------------------------------------------------------------------------
    | 1️⃣ All teachers in this school (IMPORTANT FIX)
    |--------------------------------------------------------------------------
    */
    $teacherIds = Teacher::where('school_id', $schoolId)->pluck('id');

    $totalTeachers = $teacherIds->count();

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ Teachers per grade (school-scoped)
    |--------------------------------------------------------------------------
    */
    $teachersPerGrade = Grade::whereHas('schools', function ($q) use ($schoolId) {
            $q->where('schools.id', $schoolId);
        })
        ->withCount([
            'teachers as teacher_count' => function ($q) use ($schoolId) {
                $q->where('teachers.school_id', $schoolId);
            }
        ])
        ->get(['id', 'name']);

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ Total rewards
    |--------------------------------------------------------------------------
    */
    $totalRewards = Reward::whereIn('teacher_id', $teacherIds)->sum('amount');

    /*
    |--------------------------------------------------------------------------
    | 4️⃣ Average rating
    |--------------------------------------------------------------------------
    */
    $averageRating = Rating::whereIn('teacher_id', $teacherIds)->avg('value');

    /*
    |--------------------------------------------------------------------------
    | 5️⃣ Monthly reward trend
    |--------------------------------------------------------------------------
    */
    $monthlyTrend = Reward::whereIn('teacher_id', $teacherIds)
        ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
        ->selectRaw('SUM(amount) as total_rewards')
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    return response()->json([
        'total_teachers' => $totalTeachers,
        'teachers_per_grade' => $teachersPerGrade,
        'total_rewards_received' => (float) $totalRewards,
        'average_school_rating' => round($averageRating ?? 0, 2),
        'monthly_reward_trend' => $monthlyTrend,
    ]);
}
}
