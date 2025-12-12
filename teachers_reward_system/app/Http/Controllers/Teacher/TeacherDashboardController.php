<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Reward;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Get teacher record using logged-in user's ID
        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();

        $teacherId = $teacher->id;

        /*
        |--------------------------------------------------------------------------
        | 1. Average Rating
        |--------------------------------------------------------------------------
        */
        $averageRating = Rating::where('teacher_id', $teacherId)->avg('value');
        $averageRating = round($averageRating ?? 0, 2);

        /*
        |--------------------------------------------------------------------------
        | 2. Current Wallet Balance
        |--------------------------------------------------------------------------
        */
        $currentBalance = $teacher->balance;

        /*
        |--------------------------------------------------------------------------
        | 3. Total Rewards This Month
        |--------------------------------------------------------------------------
        */
        $thisMonth = Carbon::now()->month;
        $thisYear  = Carbon::now()->year;

        $totalRewardsThisMonth = Reward::where('teacher_id', $teacherId)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | 4. Monthly Reward Breakdown (12 months)
        |--------------------------------------------------------------------------
        */
        $monthlyRewards = Reward::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('teacher_id', $teacherId)
            ->whereYear('created_at', $thisYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($row) {
                return [Carbon::create()->month($row->month)->format('M') => (float)$row->total];
            });

        /*
        |--------------------------------------------------------------------------
        | Build Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'average_rating'          => $averageRating,
            'current_balance'         => (float) $currentBalance,
            'total_rewards_this_month'=> (float) $totalRewardsThisMonth,
            'monthly_rewards'         => $monthlyRewards,
        ]);
    }
}
