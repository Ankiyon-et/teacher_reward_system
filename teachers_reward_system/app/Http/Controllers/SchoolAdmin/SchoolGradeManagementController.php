<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolGradeManagementController extends Controller
{
    // List all available grades (from grades table)
    public function allGrades()
    {
        return Grade::all();
    }

    // Assign grades to the school
    public function assignGradesToSchool(Request $request)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $request->validate([
            'grade_ids' => 'required|array',
            'grade_ids.*' => 'exists:grades,id'
        ]);
        $schoolId = $request->user()->schoolAdmin->school_id;

        DB::table('school_grade')->where('school_id', $schoolId)->delete();

        
        foreach ($request->grade_ids as $gradeId) {
            DB::table('school_grade')->insert([
                'school_id' => $schoolId,
                'grade_id' => $gradeId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Grades assigned to school']);
    }

    // Get grades of the school
    public function schoolGrades(Request $request)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $schoolId = $request->user()->schoolAdmin->school_id;
        $grades = DB::table('school_grade')
            ->join('grades', 'school_grade.grade_id', '=', 'grades.id')
            ->where('school_grade.school_id', $schoolId)
            ->select('grades.*')
            ->get();

        return response()->json($grades);
    }

    public function deleteSchoolGrade(Request $request, $gradeId)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $schoolId = $request->user()->schoolAdmin->school_id;

        // Check if the grade belongs to the school
        $exists = DB::table('school_grade')
            ->where('school_id', $schoolId)
            ->where('grade_id', $gradeId)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'This grade does not belong to your school'], 404);
        }

        // STEP 1: Find teachers teaching this grade
        $teachers = DB::table('teacher_grade')
            ->join('teachers', 'teacher_grade.teacher_id', '=', 'teachers.id')
            ->where('teachers.school_id', $schoolId)
            ->where('teacher_grade.grade_id', $gradeId)
            ->select('teachers.id')
            ->get();

        foreach ($teachers as $teacher) {
            $teacherModel = \App\Models\Teacher::with('grades', 'user')->find($teacher->id);

            if (!$teacherModel) continue;

            $gradeCount = $teacherModel->grades()->count();

            // STEP 2: If teacher teaches only THIS grade → delete teacher completely
            if ($gradeCount === 1) {
                $teacherModel->user->delete(); // cascades to teacher, teacher_grade, ratings, etc.
            } 
            
            // STEP 3: If teacher teaches multiple grades → remove only this grade
            else {
                $teacherModel->grades()->detach($gradeId);
            }
        }

        // STEP 4: Remove the grade from school’s grade list
        DB::table('school_grade')
            ->where('school_id', $schoolId)
            ->where('grade_id', $gradeId)
            ->delete();

        return response()->json(['message' => 'Grade removed from school successfully']);
    }


    private function authorizeRole($user, $allowed)
    {
        $allowed = (array) $allowed;

        if (!in_array($user->role->role_name, $allowed)) {
            abort(403, 'Unauthorized');
        }
    }
}
