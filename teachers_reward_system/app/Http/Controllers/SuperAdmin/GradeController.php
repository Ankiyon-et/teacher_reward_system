<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    // Get all grades
    public function index()
    {
        return response()->json(Grade::all());
    }

    // Create a new grade
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:grades,name',
        ]);

        $grade = Grade::create($validated);

        return response()->json([
            'message' => 'Grade created successfully',
            'grade' => $grade
        ], 201);
    }

    // Get a single grade
    public function show($id)
    {
        $grade = Grade::findOrFail($id);

        return response()->json($grade);
    }

    // Update a grade
    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:grades,name,' . $id,
        ]);

        $grade->update($validated);

        return response()->json([
            'message' => 'Grade updated successfully',
            'grade' => $grade
        ]);
    }

    // Delete a grade
    public function destroy($id)
    {
        $grade = Grade::findOrFail($id);
        $grade->delete();

        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
