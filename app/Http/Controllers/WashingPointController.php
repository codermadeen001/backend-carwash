<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WashingPoint;

class WashingPointController extends Controller
{
    //get all
     public function index()
    {
        return response()->json(WashingPoint::all(), 200);
    }
    // Add new washing point
    public function add(Request $request)
    {
        $request->validate([
            'location_url' => 'required|string',
            'location_name' => 'required|string',
        ]);

        $washingPoint = WashingPoint::create([
            'location_url' => $request->location_url,
            'location_name' => $request->location_name,
        ]);

        return response()->json(['success' => true, 'data' => $washingPoint]);
    }

    // Delete a washing point by ID
    public function delete($id)
    {
        $washingPoint = WashingPoint::find($id);
        if (!$washingPoint) {
            return response()->json(['success' => false, 'message' => 'Washing point not found']);
        }

        $washingPoint->delete();
        return response()->json(['success' => true, 'message' => 'Washing point deleted']);
    }

    // Dynamic update
    public function update(Request $request, $id)
    {
        $washingPoint = WashingPoint::find($id);
        if (!$washingPoint) {
            return response()->json(['success' => false, 'message' => 'Washing point not found']);
        }

        $updated = $washingPoint->update($request->only(['location_url', 'location_name']));

        return response()->json([
            'success' => $updated,
            'message' => $updated ? 'Updated successfully' : 'Update failed',
            'data' => $washingPoint
        ]);
    }
}
