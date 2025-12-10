<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SchoolAdmin;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // =========================================================
    // SUPERADMIN → create another superadmin
    // =========================================================
    public function createSuperAdmin(Request $request)
    {
        $this->authorizeRole($request->user(), 'superadmin');

        $request->validate([
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => 1, // superadmin
        ]);

        return response()->json([
            'message' => 'Superadmin created',
            'user'    => $user
        ], 201);
    }

    // =========================================================
    // SUPERADMIN or SCHOOL ADMIN → create a school admin
    // =========================================================
    public function createSchoolAdmin(Request $request)
    {
        $this->authorizeRole($request->user(), ['superadmin', 'schooladmin']);

        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'title'     => 'nullable|string'
        ]);

        // School admin can only create admins for *their own* school
        if ($request->user()->role->role_name === 'schooladmin') {
            if ($request->user()->schoolAdmin->school_id != $request->school_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => 2, // school admin
        ]);

        // Create school_admin record
        SchoolAdmin::create([
            'user_id'  => $user->id,
            'school_id'=> $request->school_id,
            'title'    => $request->title
        ]);

        return response()->json([
            'message' => 'School admin created',
            'user'    => $user->load('schoolAdmin')
        ], 201);
    }

    // =========================================================
    // SCHOOL ADMIN → create teacher
    // =========================================================
    public function createTeacher(Request $request)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'subject'   => 'nullable|string',
            'hire_date' => 'nullable|date',
        ]);

        // The teacher MUST belong to the school admin's school
        $schoolId = $request->user()->schoolAdmin->school_id;

        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => 3, // teacher
        ]);

        // Create teacher profile
        Teacher::create([
            'user_id'         => $user->id,
            'subject'         => $request->subject,
            'profile_picture' => null,
            'balance'         => 0,
            'average_rating'  => 0,
            'total_rewards'   => 0,
            'status'          => 'active',
            'hire_date'       => $request->hire_date,
            'school_id'       => $schoolId,
        ]);

        return response()->json([
            'message' => 'Teacher created',
            'user'    => $user->load('teacher')
        ], 201);
    }

    // =========================================================
    // Helper: role authorization
    // =========================================================
    private function authorizeRole($user, $allowed)
    {
        $allowed = (array) $allowed;

        if (!in_array($user->role->role_name, $allowed)) {
            abort(403, 'Unauthorized');
        }
    }
}
