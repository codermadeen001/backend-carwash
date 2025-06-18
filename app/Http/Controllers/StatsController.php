<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\WasherRating;

class StatsController extends Controller
{
    public function getClientStats()
    {
        $totalUsers = AppUser::where('role', 'client')->count();

        $suspendedUsers = AppUser::where('role', 'client')
                                 ->where('status', true)
                                 ->count();

        $activeUsers = $totalUsers - $suspendedUsers;

        // New users created within the last 3 days
        $threeDaysAgo = Carbon::now()->subDays(3);

        $newUsers = AppUser::where('role', 'client')
                           ->where('created_at', '>=', $threeDaysAgo)
                           ->count();

        return response()->json([
            'success' => true,
            'total_clients' => $totalUsers,
            'active_clients' => $activeUsers,
            'suspended_clients' => $suspendedUsers,
            'new_clients' => $newUsers,
        ]);
    }



     public function washerStats()
    {
        try {
            $total = AppUser::where('role', 'car detailer')->count();

            $active = AppUser::where('role', 'car detailer')
                ->where('status', '!=', 'suspended')
                ->where('availability', true)
                ->count();

            $suspended = AppUser::where('role', 'car detailer')
                ->where('status', 'suspended')
                ->count();

            $offline = AppUser::where('role', 'car detailer')
                ->where('availability', false)
                ->count();

            $newBarbers = AppUser::where('role', 'car detailer')
                ->whereDate('created_at', now()->toDateString()) // Assuming new today
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_barbers' => $total,
                    'active_barbers' => $active,
                    'suspended_barbers' => $suspended,
                    'offline_barbers' => $offline,
                    'new_barbers' => $newBarbers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch barber statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function rentalStats()
{
    try {
        // Count rentals by status using the Booking model
        $activeCount = Booking::where('status', 'active')->count();
        $cancelledCount = Booking::where('status', 'cancelled')->count();
        $completedCount = Booking::where('status', 'completed')->count();

        // Get wallet balance of all admin users using AppUser model
       // $adminWalletBalance = AppUser::where('role', 'admin');//->sum('wallet');

       $admin = AppUser::where('role', 'admin')->first();
       $adminWalletBalance = $admin ? $admin->wallet : 0;


        return response()->json([
            'success' => true,
            'message' => 'Rental stats retrieved successfully',
            'data' => [
                'active_rentals' =>$activeCount,
                'cancelled_rentals' =>$cancelledCount,
                'completed_rentals' => $completedCount,
                'admin_wallet_balance' =>$adminWalletBalance
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve stats',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function userStats()
{
   $user = Auth::guard('sanctum')->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Count active bookings
    $activeBookingsCount = Booking::where('client_id', $user->id)
        ->where('status', 'active')
        ->count();

    // Total price of non-cancelled bookings
    $totalSpent = Booking::where('client_id', $user->id)
        ->where('status', '!=', 'cancelled')
        ->sum('price');

    // Count of tala bookings (excluding cancelled)
   /* $totalBookings = Booking::where('client_id', $user->id)
        ->where('status', '!=', 'cancelled')
        ->whereHas('service', function ($query) {
            $query->where('name', 'tala'); // adjust field if needed
        })
        ->count();
*/
    // Membership rank based on count of non-cancelled bookings
    $totalBookings=Booking::where('client_id', $user->id)
        ->where('status', '!=', 'cancelled')
        ->count();

    $nonCancelledCount = Booking::where('client_id', $user->id)
        ->where('status', '!=', 'cancelled')
        ->count();

    $membership = match (true) {
        $nonCancelledCount == 0 => 'Starter',
        $nonCancelledCount <= 2 => 'Bronze',
        $nonCancelledCount <= 4 => 'Silver',
        $nonCancelledCount <= 7 => 'Gold',
        $nonCancelledCount >= 8 => 'Platinum',
    };

    return response()->json([
        'activeBookings' =>$activeBookingsCount,
        'totalSpent' => $totalSpent,
        'totalBookings' => $totalBookings,
        'membership' => $membership
    ]);
}




public function washer_Stats()
{
    $user = Auth::guard('sanctum')->user();
    $washerId = $user->id;

    $now = Carbon::now();
    $today = $now->startOfDay();
    $weekStart = $now->copy()->startOfWeek();
    $weekEnd = $now->copy()->endOfWeek();

    // Today’s services
    $today_services = Booking::where('washer_id', $washerId)
        ->where('status', 'active')
        ->whereDate('time', $today)
        ->count();

    // Weekly cars
    $weekly_cars = Booking::where('washer_id', $washerId)
        ->where('status', 'active')
        ->whereBetween('time', [$weekStart, $weekEnd])
        ->count();

    // Completed services
    $completed_services = Booking::where('washer_id', $washerId)
        ->where('status', 'completed')
        ->count();

    // Average rating
    $rating = WasherRating::where('washer_id', $washerId)
        ->selectRaw('ROUND(AVG(rating),1) as avg_rating, COUNT(*) as total_ratings')
        ->first();

    return response()->json([
        'today_services' => $today_services,
        'weekly_cars' => $weekly_cars,
        'completed_services' => $completed_services,
        'average_rating' =>$rating->avg_rating ?? 0,
        'total_ratings' => $rating->total_ratings ?? 0,
    ]);
}


public function bookingstats(){
    //return count of active bookings, cancelled bookings, completed, and admin waalet balance fisr to hit whee role is admni akll inone json payload
}

}




