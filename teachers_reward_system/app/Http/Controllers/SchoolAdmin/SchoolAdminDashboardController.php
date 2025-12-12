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
        // 1️⃣ Fetch school admin record
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $admin = SchoolAdmin::where('user_id', $user->id)->first();

        if (!$admin) {
            return response()->json(['error' => 'School admin not found'], 404);
        }

        $schoolId = $admin->school_id;

        // 2️⃣ Get all grade IDs for that school
        $gradeIds = DB::table('school_grade')
            ->where('school_id', $schoolId)
            ->pluck('grade_id');

        // 3️⃣ Get all teacher IDs linked via teacher_grade
        $teacherIds = DB::table('teacher_grade')
            ->whereIn('grade_id', $gradeIds)
            ->pluck('teacher_id');

        // 4️⃣ Total Teachers in School (through grades)
        $totalTeachers = $teacherIds->unique()->count();

        // 5️⃣ Teachers Per Grade
        $teachersPerGrade = Grade::whereIn('id', $gradeIds)
            ->select(
                'grades.id',
                'grades.name',
                DB::raw('(SELECT COUNT(DISTINCT tg.teacher_id)
                          FROM teacher_grade tg
                          WHERE tg.grade_id = grades.id) as teacher_count')
            )
            ->get();

        // 6️⃣ Total Rewards Received by School (teachers belonging via grades)
        $totalRewards = Reward::whereIn('teacher_id', $teacherIds)->sum('amount');

        // 7️⃣ Average School Rating (avg of ratings of these teachers)
        $averageRating = Rating::whereIn('teacher_id', $teacherIds)->avg('value');

        // 8️⃣ Monthly Reward Trend (last 12 months)
        $monthlyTrend = Reward::whereIn('teacher_id', $teacherIds)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_rewards')
            )
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
