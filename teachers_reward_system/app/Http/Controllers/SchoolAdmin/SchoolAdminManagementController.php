<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SchoolAdmin;
use Illuminate\Http\Request;

class SchoolAdminManagementController extends Controller
{
    // List school admins of the same school
    public function index(Request $request)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $schoolId = $request->user()->schoolAdmin->school_id;

        $admins = SchoolAdmin::with('user')
            ->where('school_id', $schoolId)
            ->get();

        return response()->json($admins);
    }

    // Delete a school admin (NOT themselves)
    public function destroy(Request $request, $adminId)
    {
        $this->authorizeRole($request->user(), 'schooladmin');

        $authUser = $request->user();
        $schoolId = $authUser->schoolAdmin->school_id;

        $admin = SchoolAdmin::with('user')->findOrFail($adminId);

        // Ensure same school
        if ($admin->school_id !== $schoolId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent deleting own account
        if ($admin->user_id === $authUser->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        // ✅ Delete both safely
        if ($admin->user) {
            $admin->user->delete();
        }

        $admin->delete();

        return response()->json([
            'message' => 'School admin deleted successfully'
        ]);
    }



    private function authorizeRole($user, $allowed)
    {
        $allowed = (array) $allowed;

        if (!in_array($user->role->role_name, $allowed)) {
            abort(403, 'Unauthorized');
        }
    }
}
