<?php

namespace App\Http\Controllers;

use App\Models\WasherRating;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WasherRatingController extends Controller
{
    public function getAllCarDetailersWithRatings()
    {
        try {
            $userId = Auth::guard('sanctum')->id();
            
            $carDetailers = AppUser::where('role', 'car detailer')
                ->withAvg('receivedRatings', 'rating')
                ->withCount('receivedRatings')
                ->get()
                ->map(function ($carDetailer) use ($userId) {
                    $userRating = WasherRating::where('user_id', $userId)
                        ->where('washer_id', $carDetailer->id)
                        ->value('rating');

                    return [
                        'id' => $carDetailer->id,
                        'name' => $carDetailer->name,
                        'profile_picture' => $carDetailer->img_url,
                        'specialties' => $carDetailer->specialties,
                        'rating' => round($carDetailer->received_ratings_avg_rating ?? 0, 1),
                        'total_ratings' => $carDetailer->received_ratings_count ?? 0,
                        'user_rating' => $userRating ?? 0
                    ];
                });

            return response()->json($carDetailers);
            
        } catch (\Exception $e) {
            Log::error("Failed to get car detailers with ratings: " . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve car detailers', 'error'=>$e], 500);
        }
    }

    public function rateCarDetailer(Request $request)
    {
        try {
           /* $validated = $request->validate([
                'car_detailer_id' => 'required|exists:app_users,id',
                'rating' => 'required|numeric|min:0|max:10'
            ]);*/

            //{ barber_id, rating }

            $userId = Auth::guard('sanctum')->id();

           /* CarDetailerRating::updateOrCreate(
                ['user_id' => $userId, 'washer_id' => $validated['car_detailer_id']],
                ['rating' => $validated['rating']]
            );*/

             WasherRating::updateOrCreate(
                ['user_id' => $userId, 'washer_id' =>$request->barber_id],
                ['rating' => $request->rating]
            );

            return response()->json(['message' => 'Rating saved successfully']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation error in rateCarDetailer: " . json_encode($e->errors()));
            return response()->json(['message' => 'Invalid input'], 422);
        } catch (\Exception $e) {
            Log::error("Error rating car detailer: " . $e->getMessage());
            return response()->json(['message' => 'Failed to save rating'], 500);
        }
    }

    public function getCarDetailerRating($car_detailer_id)
    {
        try {
            $rating = WasherRating::where('washer_id', $car_detailer_id)
                ->selectRaw('ROUND(AVG(rating),1) as avg_rating, COUNT(*) as total_ratings')
                ->first();

            return response()->json([
                'car_detailer_id' => $car_detailer_id,
                'avg_rating' => $rating->avg_rating ?? 0,
                'total_ratings' => $rating->total_ratings ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error getting car detailer rating: " . $e->getMessage());
            return response()->json(['message' => 'Failed to get rating'], 500);
        }
    }

    public function deleteRating(Request $request)
    {
        try {
          /*  $validated = $request->validate([
                'car_detailer_id' => 'required|exists:app_users,id'
            ]);*/

            $userId = Auth::guard('sanctum')->id();
            $deleted = WasherRating::where('user_id', $userId)
                ->where('washer_id',$request->barber_id)
                ->delete();

            if (!$deleted) {
                return response()->json(['message' => 'Rating not found'], 404);
            }

            return response()->json(['message' => 'Rating removed successfully']);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation error in deleteRating: " . json_encode($e->errors()));
            return response()->json(['message' => 'Invalid input'], 422);
        } catch (\Exception $e) {
            Log::error("Error deleting rating: " . $e->getMessage());
            return response()->json(['message' => 'Failed to remove rating'], 500);
        }
    }
}