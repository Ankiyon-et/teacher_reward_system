<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\Reward;
use App\Models\Teacher;
use App\Models\Withdrawal;

class TeacherRatingRewardController extends Controller
{
    public function ratings(Request $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        return Rating::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // View rewards
    public function rewards(Request $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        return Reward::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Request withdrawal
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        if ($teacher->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 422);
        }

        Withdrawal::create([
            'teacher_id' => $teacher->id,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Withdrawal request submitted']);
    }

    // Withdrawal history
    public function withdrawHistory(Request $request)
    {
        $teacher = Teacher::where('user_id', $request->user()->id)->firstOrFail();

        return Withdrawal::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
