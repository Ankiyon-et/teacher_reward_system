<?php  

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    public function index()
    {
        return School::with('admins.user')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name'      => 'required|string',
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'description'      => 'nullable|string',
            'address'          => 'nullable|string',
            'contact_email'    => 'nullable|email',

            // First school admin data
            'admin_name'       => 'required|string',
            'admin_email'      => 'required|email|unique:users,email',
            'admin_password'   => 'required|string|min:6',
            'admin_title'      => 'nullable|string',
        ]);

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('school_logos', 'public');
        }

        // Create school
        $school = School::create([
            'name'          => $validated['school_name'],
            'logo'          => $logoPath,
            'description'   => $validated['description'] ?? null,
            'address'       => $validated['address'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
        ]);

        // Create school admin user
        $admin = User::create([
            'name'      => $validated['admin_name'],
            'email'     => $validated['admin_email'],
            'password'  => Hash::make($validated['admin_password']),
            'role_id'   => 2, // schooladmin
        ]);

        // Attach school admin
        SchoolAdmin::create([
            'user_id'  => $admin->id,
            'school_id'=> $school->id,
            'title'    => $validated['admin_title'] ?? null,
        ]);

        return response()->json([
            'message' => 'School created successfully',
            'school'  => $school,
            'admin'   => $admin
        ], 201);
    }

    public function show($id)
    {
        return School::with('admins.user')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'school_name'   => 'required|string',
            'logo'          => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'description'   => 'nullable|string',
            'address'       => 'nullable|string',
            'contact_email' => 'nullable|email',
        ]);

        // Handle logo update
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('school_logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $school->update([
            'name'          => $validated['school_name'],
            'logo'          => $validated['logo'] ?? $school->logo,
            'description'   => $validated['description'] ?? $school->description,
            'address'       => $validated['address'] ?? $school->address,
            'contact_email' => $validated['contact_email'] ?? $school->contact_email,
        ]);

        return response()->json([
            'message' => 'School updated successfully',
            'school'  => $school
        ]);
    }

    public function destroy($id)
    {
        $school = School::with([
            'admins.user',
            'teachers.user',
            'teachers.grades',
            'grades'
        ])->findOrFail($id);

        // 1) DELETE SCHOOL LOGO
        if ($school->logo && Storage::disk('public')->exists($school->logo)) {
            Storage::disk('public')->delete($school->logo);
        }

        // 2) DELETE SCHOOL ADMIN USERS
        foreach ($school->admins as $admin) {
            $admin->user->delete();  // deletes user
            $admin->delete();        // deletes school_admin row
        }

        // 3) DELETE TEACHERS + THEIR USERS + FILES
        foreach ($school->teachers as $teacher) {

            // Delete profile picture
            if ($teacher->profile_picture && Storage::disk('public')->exists($teacher->profile_picture)) {
                Storage::disk('public')->delete($teacher->profile_picture);
            }

            // Delete user account
            $teacher->user->delete();

            // Teacher-grade pivot rows auto cascade if set, but we handle to be safe:
            $teacher->grades()->detach();

            // Delete teacher row
            $teacher->delete();
        }

        // 4) DELETE SCHOOL-GRADE PIVOT ROWS (grades table remains intact)
        $school->grades()->detach();

        // 5) DELETE SCHOOL ITSELF
        $school->delete();

        return response()->json([
            'message' => 'School and all related data deleted successfully.'
        ]);
    }

}
