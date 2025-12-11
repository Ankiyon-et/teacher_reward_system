<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolTeacherManagementController extends Controller
{
    // List teachers in school
    public function index(Request $request)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $schoolId = $request->user()->schoolAdmin->school_id;

        $teachers = Teacher::with('user', 'grades')
            ->where('school_id', $schoolId)
            ->get();

        return response()->json($teachers);
    }

    // Assign selected grades to teacher
    public function assignGrades(Request $request, $teacherId)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $request->validate([
            'grade_ids' => 'required|array',
            'grade_ids.*' => 'exists:grades,id'
        ]);

        $schoolId = $request->user()->schoolAdmin->school_id;

        $teacher = Teacher::findOrFail($teacherId);

        // Ensure teacher belongs to the admin's school
        if ($teacher->school_id != $schoolId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // Must only assign grades that belong to the school
        $schoolGradeIds = DB::table('school_grade')
            ->where('school_id', $schoolId)
            ->pluck('grade_id')
            ->toArray();

        foreach ($request->grade_ids as $gradeId) {
            if (!in_array($gradeId, $schoolGradeIds)) {
                return response()->json([
                    'message' => 'One or more grades do not belong to your school'
                ], 422);
            }
        }

        // Sync teacher grades
        $teacher->grades()->sync($request->grade_ids);

        return response()->json(['message' => 'Teacher grades updated']);
    }

    // Delete teacher
    public function destroy(Request $request, $teacherId)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $schoolId = $request->user()->schoolAdmin->school_id;

        $teacher = Teacher::with('user')->findOrFail($teacherId);

        if ($teacher->school_id != $schoolId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cascade delete: teacher_grade, ratings, rewards, etc.
        $teacher->user->delete();

        return response()->json(['message' => 'Teacher deleted']);
    }

    private function authorizeRole($user, $allowed)
    {
        $allowed = (array) $allowed;

        if (!in_array($user->role->role_name, $allowed)) {
            abort(403, 'Unauthorized');
        }
    }
}
