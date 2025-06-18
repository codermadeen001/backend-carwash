<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;












class ServiceController extends Controller
{
  /*  protected $cloudinary;

  public function __construct()
{
    $this->cloudinary = new Cloudinary(
        Configuration::fromParams(
            'dadcnkqbg',                      // cloud name
            '769447669581899',               // api key
            'SMXcoOapJt4KElCoVzbCJ_SzIqM'     // api secret
        )
    );
}
    

    public function __construct()
{
    $this->cloudinary = new Cloudinary();
}*/


public function __construct()
{
    $this->cloudinary = new Cloudinary([
        'cloud' => [
            'cloud_name' => config('cloudinary.cloud_name'),
            'api_key' => config('cloudinary.api_key'),
            'api_secret' => config('cloudinary.api_secret'),
        ],
        'url' => [
            'secure' => true
        ]
    ]);
}

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

        // Upload to Cloudinary
        $uploadedFile = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
        $imageUrl = $uploadedFile['secure_url'];

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
            $uploadedFile = $this->cloudinary->uploadApi()->upload($request->file('image')->getRealPath());
            $data['img_url'] = $uploadedFile['secure_url'];
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


/*

    public function priceReview()
    {
        $service = Service::findOrFail(1);
        return response()->json($service, 200);
    }
*/



    

    // Fetch prices for service IDs 1, 2, and 3
    public function priceFetch()
    {
        $services = Service::whereIn('id', [1, 2, 3])->get(['id', 'package_type', 'price']);
        
        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    // Update price for a specific service
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
//composer require cloudinary-labs/cloudinary-laravel
