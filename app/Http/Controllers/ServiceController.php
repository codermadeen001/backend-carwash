<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json(Service::all(), 200);
    }




public function store(Request $request)
{
    $validated = $request->validate([
        'package_type' => 'required|string',
        'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        'price' => 'required|numeric',
        'description' => 'nullable|string',
        'duration' => 'nullable|integer'
    ]);

    // Use a public static image URL
    $imageUrl = 'https://via.placeholder.com/300x200.png?text=Default+Service';

    $service = Service::create([
        'package_type' => $validated['package_type'],
        'img_url' => $imageUrl,
        'price' => $validated['price'],
        'description' => $validated['description'] ?? null,
        'duration' => $validated['duration'] ?? 30
    ]);

    return response()->json($service, 201);
}

public function update(Request $request, $id)
{
    $service = Service::findOrFail($id);

    $data = $request->validate([
        'package_type' => 'sometimes|string',
        'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        'price' => 'sometimes|numeric',
        'description' => 'nullable|string',
        'duration' => 'nullable|integer'
    ]);

    if ($request->hasFile('image')) {
        // Use same online static image
        $data['img_url'] = 'https://via.placeholder.com/300x200.png?text=Default+Service';
    }

    $service->update($data);
    return response()->json($service, 200);
}






    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        return response()->json(['message' => 'Service deleted successfully'], 200);
    }

    public function show($id)
    {
        $service = Service::findOrFail($id);
        return response()->json($service, 200);
    }

    public function priceFetch()
    {
        $services = Service::whereIn('id', [1, 2, 3])->get(['id', 'package_type', 'price']);

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    public function priceUpdate(Request $request)
    {
        $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'price' => 'required|numeric|min:0'
        ]);

        $service = Service::findOrFail($request->service_id);
        $service->price = $request->price;
        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Service price updated successfully.',
            'data' => $service
        ]);
    }
}
