<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{
    public function index()
    {
        return User::where('role_id', 1)->get();
    }

    public function update(Request $request, $id)
    {
        $user = User::where('role_id', 1)->findOrFail($id);

        $validated = $request->validate([
            'name'   => 'string',
            'email'  => 'email|unique:users,email,' . $id,
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Super Admin updated', 'user' => $user]);
    }

    public function destroy($id)
    {
        // Prevent self-deletion
        if (Auth::id() == $id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        // Only allow deleting super admins (role_id = 1)
        $user = User::where('role_id', 1)->findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'Super Admin deleted successfully'
        ]);
    }
}
