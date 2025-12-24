<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reward;
use App\Models\Rating;
use App\Models\Grade;
use App\Models\SchoolAdmin;

class SchoolAdminDashboardController extends Controller
{
    public function index(Request $request)
{
    // 🔐 Authenticated user
    $user = $request->user();
    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    // 🏫 School admin
    $admin = SchoolAdmin::where('user_id', $user->id)->first();
    if (!$admin) {
        return response()->json(['error' => 'School admin not found'], 404);
    }

    $schoolId = $admin->school_id;

    // 📚 Grade IDs that belong to THIS school
    $gradeIds = DB::table('school_grade')
        ->where('school_id', $schoolId)
        ->pluck('grade_id');

    // 👩‍🏫 Teacher IDs:
    // MUST belong to the school AND be assigned to the school's grades
    $teacherIds = DB::table('teacher_grade')
        ->join('teachers', 'teachers.id', '=', 'teacher_grade.teacher_id')
        ->whereIn('teacher_grade.grade_id', $gradeIds)
        ->where('teachers.school_id', $schoolId)
        ->distinct()
        ->pluck('teachers.id');

    // 🛑 Empty school guard
    if ($teacherIds->isEmpty()) {
        return response()->json([
            'total_teachers' => 0,
            'teachers_per_grade' => [],
            'total_rewards_received' => 0,
            'average_school_rating' => 0,
            'monthly_reward_trend' => [],
        ]);
    }

    // 🔢 Total teachers (NOW 100% CORRECT)
    $totalTeachers = $teacherIds->count();

    // 📊 Teachers per grade (school-safe)
    $teachersPerGrade = DB::table('grades')
        ->join('school_grade', 'grades.id', '=', 'school_grade.grade_id')
        ->leftJoin('teacher_grade', 'grades.id', '=', 'teacher_grade.grade_id')
        ->leftJoin('teachers', 'teachers.id', '=', 'teacher_grade.teacher_id')
        ->where('school_grade.school_id', $schoolId)
        ->where(function ($q) use ($schoolId) {
            $q->whereNull('teachers.id')
              ->orWhere('teachers.school_id', $schoolId);
        })
        ->groupBy('grades.id', 'grades.name')
        ->select(
            'grades.id',
            'grades.name',
            DB::raw('COUNT(DISTINCT teachers.id) as teacher_count')
        )
        ->get();

    // 💰 Total rewards (school teachers only)
    $totalRewards = Reward::whereIn('teacher_id', $teacherIds)
        ->sum('amount');

    // ⭐ Average rating (school teachers only)
    $averageRating = Rating::whereIn('teacher_id', $teacherIds)
        ->avg('value');

    // 📈 Monthly reward trend (last 12 months)
    $monthlyTrend = Reward::whereIn('teacher_id', $teacherIds)
        ->where('created_at', '>=', now()->subMonths(12))
        ->select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total_rewards')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    // ✅ Final response
    return response()->json([
        'total_teachers' => $totalTeachers,
        'teachers_per_grade' => $teachersPerGrade,
        'total_rewards_received' => (float) $totalRewards,
        'average_school_rating' => round($averageRating ?? 0, 2),
        'monthly_reward_trend' => $monthlyTrend,
    ]);
}

}
