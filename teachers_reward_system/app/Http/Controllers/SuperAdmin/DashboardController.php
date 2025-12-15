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
        $totalSchools = School::count();

        $totalTeachers = Teacher::count();

        $totalRewardsProcessed = Reward::sum('amount');

        $monthlyRewardTrend = Reward::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("SUM(amount) as total_amount")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->take(12)
            ->get();

        $annualRewardTrend = Reward::select(
                DB::raw("YEAR(created_at) as year"),
                DB::raw("SUM(amount) as total_amount")
            )
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

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
            'annual_reward_trend' => $annualRewardTrend,
            'top_performing_schools' => $topPerformingSchools,
            'teachers_population_per_school' => $teachersPopulation
        ]);
    }
}
