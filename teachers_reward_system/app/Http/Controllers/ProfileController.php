<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\SchoolAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('role');

        $profile = [
            'user' => $user,
            'role_profile' => null,
        ];

        switch ($user->role->role_name) {

            case 'schooladmin':
                $profile['role_profile'] = SchoolAdmin::where('user_id', $user->id)->first();
                break;

            case 'teacher':
                $profile['role_profile'] = Teacher::where('user_id', $user->id)->first();
                break;

            // superadmin → no extra table
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Validate common user fields
        |--------------------------------------------------------------------------
        */
        $validatedUser = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|min:6|confirmed',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update USERS table
        |--------------------------------------------------------------------------
        */
        if (isset($validatedUser['password'])) {
            $validatedUser['password'] = Hash::make($validatedUser['password']);
        }

        $user->update($validatedUser);

        /*
        |--------------------------------------------------------------------------
        | Role-specific updates
        |--------------------------------------------------------------------------
        */
        switch ($user->role->role_name) {

            case 'schooladmin':
                $this->updateSchoolAdminProfile($request, $user);
                break;

            case 'teacher':
                $this->updateTeacherProfile($request, $user);
                break;

            // superadmin has no extra table for now
        }

        return response()->json([
            'message' => 'Profile updated successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | School Admin profile update
    |--------------------------------------------------------------------------
    */
    private function updateSchoolAdminProfile(Request $request, User $user)
    {
        $schoolAdmin = SchoolAdmin::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|nullable|string|max:255'
        ]);

        $schoolAdmin->update($validated);
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher profile update
    |--------------------------------------------------------------------------
    */
    private function updateTeacherProfile(Request $request, User $user)
    {
        $teacher = Teacher::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'subject'         => 'sometimes|nullable|string|max:255',
            'profile_picture' => 'sometimes|nullable|string'
        ]);

        $teacher->update($validated);
    }
}
