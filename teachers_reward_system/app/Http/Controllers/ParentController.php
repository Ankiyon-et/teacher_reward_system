<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\Reward;

class ParentController extends Controller
{
    public function rateTeacher(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'parent_name' => 'nullable|string',
            'value' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        Rating::create($request->only(['teacher_id', 'parent_name', 'value', 'comment']));

        return response()->json(['message' => 'Rating submitted']);
    }

    // Send reward
    public function rewardTeacher(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'parent_name' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
        ]);

        Reward::create($request->only(['teacher_id', 'parent_name', 'amount']));

        return response()->json(['message' => 'Reward sent successfully']);
    }
}
