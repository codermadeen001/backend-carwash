<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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


    public function getReportData()
    {
        try {
            // 1. Fetch KPI data
            $walletBalance = DB::table('app_users')
                ->where('role', 'admin')
                ->sum('wallet');

            $totalBarbers = DB::table('app_users')
                ->where('role', 'car detailer')
                ->count();

            $totalClients = DB::table('app_users')
                ->where('role', 'client')
                ->count();

            $totalAppointments = DB::table('bookings')
                ->where('status', '!=', 'cancelled')
                ->count();

            $totalServices = DB::table('services')
                ->count();

            $totalWashingPoints = DB::table('washing_points')
                ->count();

            $todayDate = Carbon::today()->toDateString();
            $todaysBookings = DB::table('bookings')
                ->whereDate('created_at', $todayDate)
                ->count();

            // 2. Get barber status breakdown
            $barberStatus = DB::table('app_users')
                ->where('role', 'car detailer')
                ->selectRaw('
                    SUM(CASE WHEN availability = true AND status = false THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN availability = false  THEN 1 ELSE 0 END) as offline,
                    SUM(CASE WHEN status = true THEN 1 ELSE 0 END) as suspended
                ')
                ->first();

                /**
                 * 
                 *  // 2. Get barber status breakdown
                 */

            // 3. Generate bookings trend for last 7 days
            $bookingsTrendData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $formattedDate = $date->toDateString();
                $dayName = $date->format('D');

                $dailyBookings = DB::table('bookings')
                    ->whereDate('created_at', $formattedDate)
                    ->count();

                $bookingsTrendData[$dayName] = $dailyBookings;
            }

            // 4. Get popular services
            $popularServicesQuery = DB::table('bookings')
                ->join('services', 'bookings.service_id', '=', 'services.id')
                ->select('services.package_type', DB::raw('COUNT(bookings.id) as count'))
                ->groupBy('services.package_type')
                ->orderByDesc('count')
                ->limit(9)
                ->get();

            $popularServices = [];
            foreach ($popularServicesQuery as $service) {
                $popularServices[$service->package_type] = $service->count;
            }

            // 5. Get barber popularity
            $barberPopularityQuery = DB::table('bookings')
                ->join('app_users', 'bookings.washer_id', '=', 'app_users.id')
                ->select('app_users.name', DB::raw('COUNT(bookings.id) as count'))
                ->groupBy('bookings.washer_id', 'app_users.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            $barberPopularity = [];
            foreach ($barberPopularityQuery as $barber) {
                $nameParts = explode(' ', $barber->name);
                $formattedName = count($nameParts) > 1 
                    ? $nameParts[0] . ' ' . substr($nameParts[1], 0, 1) . '.' 
                    : $nameParts[0];

                $barberPopularity[$formattedName] = $barber->count;
            }

            // 6. Get loyal clients (return max 3 only if more than 2 exist)
            $loyalClientsQuery = DB::table('bookings')
                ->join('app_users', 'bookings.client_id', '=', 'app_users.id')
                ->select(
                    'app_users.name',
                    DB::raw('COUNT(bookings.id) as visits'),
                    DB::raw('SUM(bookings.price) as spent')
                )
                ->groupBy('bookings.client_id', 'app_users.name')
                ->orderByDesc('visits')
                ->get();

            $loyalClients = $loyalClientsQuery->map(function ($client) {
                return [
                    'name' => $client->name,
                    'visits' => $client->visits,
                    'spent' => (float) $client->spent
                ];
            });

            if ($loyalClients->count() > 2) {
                $loyalClients = $loyalClients->take(3);
            }

            // 7. Final payload
            $payload = [
                'kpis' => [
                    'walletBalance' => (float) $walletBalance,
                    'totalBarbers' => $totalBarbers,
                    'totalClients' => $totalClients,
                    'totalAppointments' => $totalAppointments,
                    'totalServices' => $totalServices,
                    'todaysBookings' => $todaysBookings,
                    'totalWashingPoints' => $totalWashingPoints
                ],
                'barberStatus' => [
                    'active' => $barberStatus->active ?? 0,
                    'offline' => $barberStatus->offline ?? 0,
                    'suspended' => $barberStatus->suspended ?? 0
                ],
                'bookingsTrend' => [
                    'data' => $bookingsTrendData
                ],
                'popularServices' => $popularServices,
                'barberPopularity' => $barberPopularity,
                'loyalClients' => $loyalClients
            ];

            return response()->json($payload);

        } catch (\Exception $error) {
            \Log::error('Error fetching client statistics:', ['error' => $error]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client statistics',
                'error' => config('app.debug') ? $error->getMessage() : null
            ], 500);
        }
    }



}




