<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\Reward;
use App\Models\School;
use App\Models\Grade;
use App\Models\Teacher;

class ParentController extends Controller
{
    public function schools()
    {
        return response()->json(
            School::select('id', 'name', 'logo')->get()
        );
    }

    // 2. Grades by school
    public function grades(School $school)
    {
        return response()->json(
            $school->grades()->select('grades.id', 'grades.name')->get()
        );
    }

    // 3. Teachers by school + grade
    public function teachers(School $school, Grade $grade)
    {
        $teachers = Teacher::where('school_id', $school->id)
            ->whereHas('grades', function ($q) use ($grade) {
                $q->where('grades.id', $grade->id);
            })
            ->with('user:id,name')
            ->select(
                'id',
                'user_id',
                'subject',
                'profile_picture',
                'average_rating'
            )
            ->get();

        return response()->json($teachers);
    }

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
