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

        $schoolId = $request->user()->schoolAdmin->school_id;

        $admin = SchoolAdmin::with('user')->findOrFail($adminId);

        // Can only delete admins from the same school
        if ($admin->school_id != $schoolId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent deleting themselves
        if ($admin->user_id == $request->user()->id) {
            return response()->json(['message' => 'You cannot delete yourself'], 403);
        }

        // This will cascade delete user + school_admin record
        $admin->user->delete();

        return response()->json(['message' => 'School admin deleted']);
    }

    private function authorizeRole($user, $allowed)
    {
        $allowed = (array) $allowed;

        if (!in_array($user->role->role_name, $allowed)) {
            abort(403, 'Unauthorized');
        }
    }
}
