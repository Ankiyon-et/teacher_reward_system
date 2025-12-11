<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Reward;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ================================
        // 1. TOTAL SCHOOLS
        // ================================
        $totalSchools = School::count();

        // ================================
        // 2. TOTAL TEACHERS
        // ================================
        $totalTeachers = Teacher::count();

        // ================================
        // 3. TOTAL REWARDS PROCESSED
        // ================================
        $totalRewardsProcessed = Reward::sum('amount');

        // ================================
        // 4. MONTHLY REWARD TREND (last 12 months)
        // ================================
        $monthlyRewardTrend = Reward::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("SUM(amount) as total_amount")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->take(12)
            ->get();

        // ================================
        // 5. TOP PERFORMING SCHOOLS
        // Ranking by total rewards received by their teachers
        // ================================
        $topPerformingSchools = School::select(
                'schools.id',
                'schools.name',
                DB::raw("SUM(rewards.amount) as total_rewards")
            )
            ->leftJoin('teachers', 'teachers.school_id', '=', 'schools.id')
            ->leftJoin('rewards', 'rewards.teacher_id', '=', 'teachers.id')
            ->groupBy('schools.id', 'schools.name')
            ->orderByDesc('total_rewards')
            ->get();

        // ================================
        // 6. TEACHERS POPULATION PER SCHOOL
        // ================================
        $teachersPopulation = School::select(
                'schools.id',
                'schools.name',
                DB::raw("COUNT(teachers.id) as teacher_count")
            )
            ->leftJoin('teachers', 'teachers.school_id', '=', 'schools.id')
            ->groupBy('schools.id', 'schools.name')
            ->orderBy('schools.name', 'asc')
            ->get();

        return response()->json([
            'total_schools' => $totalSchools,
            'total_teachers' => $totalTeachers,
            'total_rewards_processed' => $totalRewardsProcessed,
            'monthly_reward_trend' => $monthlyRewardTrend,
            'top_performing_schools' => $topPerformingSchools,
            'teachers_population_per_school' => $teachersPopulation
        ]);
    }
}
