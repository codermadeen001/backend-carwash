<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
//use APP\Models\WashingPoint;
use App\Models\WashingPoint;
use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DB;




class BookingController extends Controller
{
    // MPESA Configuration
    private $consumerKey = "YPEGEcAMRuPHQ6AMAZfERSs4uDtGkCi5";
    private $consumerSecret = "cEqsWn1ejW4fYAYL";
    private $shortcode = '174379';
    private $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
    private $callbackUrl = 'https://astranet.co.ke/stk/callback_url.php';

    public function create(Request $request)
    {
        try {
            // Step 1: Validate authenticated user
             $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
            } 

            //$user = AppUser::find(1);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Step 2: Get user phone and email
            $userPhone = $user->contact;
            if(!$userPhone){
                 return response()->json([
                    'success' => false,
                    'message' => 'Update Profile!'
                ]);
            }
            $userEmail = $user->email;
            
            if (!$userEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'User email not found'
                ], 400);
            }

            // Step 3: Validate and get service/location data
            if (!$request->packageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package ID is required'
                ]);
            }

            $service = Service::find($request->packageId);
           // $service = Service::find(1);
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            $washingPoint = WashingPoint::find($request->locationId);
            //$washingPoint = WashingPoint::find(1);
            if (!$washingPoint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Washing point not found'
                ], 404);
            }

            $amount = $service->price;
            $amount = (int) round($amount)??(int) round($request->price); // Now $amount = 1235

            $location_name = $washingPoint->location_name;
            $location_url = $washingPoint->location_url;

            // Step 4: Get washer information (optional)
            $washer = null;
            if ($request->washerId) {
                $washer = AppUser::find($request->washerId);
            }
            $washerName = $washer ? $washer->name : 'Assigned Attendant';

            // Step 5: Initiate STK Push
            $stkResult = $this->initiateStkPush($userPhone, $amount);
            
            if (!$stkResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'STK Push failed',
                    'message' => $stkResult['message'] ?? 'Unknown STK error'
                ], 400);
            }

            $checkoutRequestID = $stkResult['CheckoutRequestID'];

            // Step 6: Save booking data (only after successful STK push)
            $bookingData = $this->saveBookingData($request, $user, $service, $washingPoint, $checkoutRequestID, $amount);
            
            if (!$bookingData['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to save booking',
                    'message' => $bookingData['message'] ?? 'Unknown booking save error'
                ], 500);
            }

            $booking = $bookingData['booking'];

            // Step 7: Prepare email data
            $emailData = $this->prepareEmailData($request, $user, $booking, $service, $washingPoint, $washerName, $checkoutRequestID, $amount);

            // Step 8: Send confirmation email
            $emailResult = $this->sendConfirmationEmail($userEmail, $emailData);
            
            if (!$emailResult['success']) {
                // Log email failure but don't fail the entire process
                \Log::warning('Email sending failed for booking: ' . $booking->id, [
                    'error' => $emailResult['message'] ?? 'Unknown email error'
                ]);
            }

            // Step 9: Return success response
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking_id' => $booking->id,
                    'checkout_request_id' => $checkoutRequestID,
                    'receipt' => $checkoutRequestID,
                    'amount' => $amount ?? 99,
                    'email_sent' => $emailResult['success'] ?? false,
                    'booking_details' => [
                        'service' => $service->name ?? 'Car Wash Service',
                        'location' => $location_name,
                        'date_time' => $booking->time,
                        'plate_number' => $booking->plate_number,
                        'washer' => $washerName
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error occurred',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    private function initiateStkPush($phone, $amount)
    {
        try {
            // Ensure phone number starts with 254
            if (strpos($phone, '+') === 0) {
                $phone = substr($phone, 1);
            }
            if (strpos($phone, '254') !== 0) {
                $phone = '254' . ltrim($phone, '0');
            }

            // Get MPESA Access Token
            $tokenResponse = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');

            if (!$tokenResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to get MPESA access token: ' . $tokenResponse->body()
                ];
            }

            $tokenData = $tokenResponse->json();
            if (!isset($tokenData['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'Access token not found in response'
                ];
            }

            $accessToken = $tokenData['access_token'];

            // Prepare STK Push parameters
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $stkResponse = Http::withToken($accessToken)->post(
                'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $phone,
                    'PartyB' => $this->shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->callbackUrl,
                    'AccountReference' => 'CarWashBooking',
                    'TransactionDesc' => 'Car wash service payment',
                ]
            );

            if (!$stkResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'STK Push HTTP error: ' . $stkResponse->body()
                ];
            }

            $responseData = $stkResponse->json();

            if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === '0') {
                return [
                    'success' => true,
                    'CheckoutRequestID' => $responseData['CheckoutRequestID']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMessage'] ?? $responseData['CustomerMessage'] ?? 'STK Push request failed'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'STK Push exception: ' . $e->getMessage()
            ];
        }
    }

    private function saveBookingData($request, $user, $service, $washingPoint, $checkoutRequestID, $amount)
    {
        try {
            // Parse date and time
           // $requestDate = $request->date ?? now()->format('Y-m-d');
            //$requestTime = $request->time ?? now()->format('H:i');
            
           // $datetime = Carbon::parse($requestDate . ' ' . $requestTime);

           $datetime=$request->time;

            $booking = Booking::create([
                'service_id' => $service->id,
                'washing_point_id' => $washingPoint->id,
                'client_id' => $user->id,
                'washer_id' => $request->washerId ,
                'time' => $datetime,
                'receipt' => $checkoutRequestID,
                'plate_number' => $request->plate_number ?? 'N/A',
                'status' => 'active', 
                'price' => $amount,
                //'special_instructions' => $request->specialInstructions ?? null,
                //'payment_method' => 'mpesa',
            ]);

            // Credit Admin's wallet (3/4 of the amount)
            $admin = AppUser::where('role', 'admin')->first();
            if ($admin) {
                $admin->wallet += (3 / 4) * $amount;
                $admin->save();
            }

            // Credit Washer's wallet (1/4 of the amount)
            $washer = AppUser::find($request->washerId);
            if ($washer) {
                $washer->wallet += (1 / 4) * $amount;
                $washer->save();
            }

            return [
                'success' => true,
                'booking' => $booking,
                'message' => 'Booking saved successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    private function prepareEmailData($request, $user, $booking, $service, $washingPoint, $washerName, $receipt, $amount)
    {
        $bookingDateTime = Carbon::parse($booking->time);
        
        return [
            'username' => $user->name ?? 'Valued Customer',
            'car_plate_number' => $booking->plate_number ?? 'N/A',
            'price' => number_format($amount, 2),
            'receipt' => $receipt,
            'washer' => $washerName,
            'date' => $bookingDateTime->format('j F'),
            'day' => $bookingDateTime->format('l'),
            'time' => $bookingDateTime->format('g:i A'),
            'location_name' => $washingPoint->location_name ?? 'Car Wash Center',
            'location_url' => $washingPoint->location_url ?? 'https://maps.google.com/',
        ];
    }

    private function sendConfirmationEmail($email, $emailData)
    {
        try {
            $htmlTemplate = $this->getEmailTemplate($emailData);
            $subject = 'Car Wash Service Confirmation - Receipt #' . $emailData['receipt'];

            Mail::send([], [], function ($message) use ($email, $htmlTemplate, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->html($htmlTemplate);
            });

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ];
        }
    }

    private function getEmailTemplate($data)
    {
        $replacements = [
            '{{ $username }}' => htmlspecialchars($data['username']),
            '{{ $car_plate_number }}' => htmlspecialchars($data['car_plate_number']),
            '{{ $price }}' => htmlspecialchars($data['price']),
            '{{ $receipt }}' => htmlspecialchars($data['receipt']),
            '{{ $washer }}' => htmlspecialchars($data['washer']),
            '{{ $date }}' => htmlspecialchars($data['date']),
            '{{ $day }}' => htmlspecialchars($data['day']),
            '{{ $time }}' => htmlspecialchars($data['time']),
            '{{ $location_name }}' => htmlspecialchars($data['location_name']),
            '{{ $location_url }}' => htmlspecialchars($data['location_url']),
        ];

        $template = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Wash Confirmation</title>
</head>
<body style="margin: 0; padding: 20px; font-family: Arial, sans-serif; background-color: #f8f9fa; color: #343a40; line-height: 1.6;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1a3a5f 0%, #2c5282 100%); color: #ffffff; padding: 40px 30px; text-align: center;">
                <div style="font-size: 60px; margin-bottom: 20px;">🚗</div>
                <h1 style="font-size: 32px; font-weight: 700; margin: 0 0 10px 0;">Booking Confirmed!</h1>
                <p style="font-size: 18px; margin: 0; opacity: 0.9;">Your car wash is scheduled</p>
            </td>
        </tr>
        
        <!-- Content -->
        <tr>
            <td style="padding: 40px 30px;">
                <!-- Confirmation Badge -->
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; padding: 15px 30px; border-radius: 25px; display: inline-block; font-weight: 600; font-size: 16px; margin-bottom: 30px;">
                    ✅ Payment Processing
                </div>

                <!-- Customer Details -->
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 5px solid #ff6b35; margin-bottom: 15px;">
                            <div style="font-weight: 600; color: #1a3a5f; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                👤 Customer Name
                            </div>
                            <div style="font-size: 18px; font-weight: 500; color: #343a40;">
                                {{ $username }}
                            </div>
                        </td>
                    </tr>
                    <tr><td style="height: 15px;"></td></tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 5px solid #ff6b35;">
                            <div style="font-weight: 600; color: #1a3a5f; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                🚗 Vehicle Plate Number
                            </div>
                            <div style="font-size: 18px; font-weight: 500; color: #343a40;">
                                {{ $car_plate_number }}
                            </div>
                        </td>
                    </tr>
                    <tr><td style="height: 15px;"></td></tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 5px solid #ff6b35;">
                            <div style="font-weight: 600; color: #1a3a5f; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                🧽 Service Attendant
                            </div>
                            <div style="font-size: 18px; font-weight: 500; color: #343a40;">
                                {{ $washer }}
                            </div>
                        </td>
                    </tr>
                    <tr><td style="height: 15px;"></td></tr>
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 5px solid #ff6b35;">
                            <div style="font-weight: 600; color: #1a3a5f; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                📅 Scheduled Date & Time
                            </div>
                            <div style="font-size: 18px; font-weight: 500; color: #343a40;">
                                {{ $date }}, {{ $day }} at {{ $time }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Price Section -->
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 30px 0;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%); color: #ffffff; text-align: center; padding: 30px; border-radius: 10px;">
                            <div style="font-size: 16px; opacity: 0.9; margin-bottom: 10px;">Total Amount</div>
                            <div style="font-size: 36px; font-weight: 700; margin-bottom: 5px;">KSh {{ $price }}</div>
                            <div style="font-size: 14px; opacity: 0.9;">Payment via M-Pesa</div>
                        </td>
                    </tr>
                </table>

                <!-- Location -->
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 20px 0;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: #ffffff; padding: 25px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; margin-bottom: 10px;">📍</div>
                            <div style="font-size: 18px; font-weight: 600;">{{ $location_name }}</div>
                            <div style="font-size: 14px; opacity: 0.9; margin-top: 5px;">
                                <a href="{{ $location_url }}" style="color: #ffffff; text-decoration: none;">Click to view on map</a>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Receipt Section -->
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 30px 0;">
                    <tr>
                        <td style="background: #ffffff; border: 2px dashed #1a3a5f; padding: 25px; border-radius: 10px; text-align: center;">
                            <div style="color: #1a3a5f; font-size: 20px; font-weight: 600; margin-bottom: 15px;">📄 Booking Reference</div>
                            <div style="font-family: monospace; font-size: 20px; font-weight: 700; color: #ff6b35; background: #f8f9fa; padding: 15px 20px; border-radius: 10px; display: inline-block; letter-spacing: 1px;">
                                {{ $receipt }}
                            </div>
                            <p style="margin-top: 15px; color: #343a40; font-size: 14px;">
                                Keep this reference for your records
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Thank You Section -->
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                            <h3 style="color: #1a3a5f; margin-bottom: 15px;">Thank You for Choosing Us!</h3>
                            <p style="color: #343a40; margin-bottom: 15px;">
                                Complete your M-Pesa payment to confirm your booking. We look forward to serving you!
                            </p>
                            <p style="font-size: 14px; color: #1a3a5f; font-weight: 600;">
                                🌟 Questions? Contact us • 🔄 Reschedule anytime • 📱 Download our app
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background: #1a3a5f; color: #ffffff; padding: 30px; text-align: center;">
                <h3 style="margin-bottom: 15px; font-size: 20px;">🚗 Premium Car Wash</h3>
                <p style="opacity: 0.8; margin-bottom: 10px;">Quality service, every time</p>
                <p style="opacity: 0.8; margin-bottom: 10px;">📧 support@carwash.com | 📞 (555) 123-4567</p>
                <p style="font-size: 12px; margin-top: 15px; opacity: 0.7;">
                    This is an automated confirmation email. Please do not reply to this message.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }






    public function todayBookings()
{
  $user = Auth::guard('sanctum')->user();
    
    $bookings = Booking::where('washer_id',$user->id)
        ->where('status', 'active')
        ->whereDate('time', now()->format('Y-m-d'))
        ->with(['service:id,package_type,price,duration,description'])
        ->with(['washing_point:id,location_name,location_url'])
        ->with(['client:id,name,contact'])
        ->with(['washer:id,name,contact'])
        ->orderBy('time', 'desc')
        ->get();
    
    return response()->json([
        'success' => true,
        'message' => "Today's bookings retrieved successfully",
        'data' => $bookings
    ]);
}

public function tomorrowBookings()
{
   $user = Auth::guard('sanctum')->user();
    $tomorrow = now()->addDay()->format('Y-m-d');

    $bookings = Booking::where('washer_id', $user->id)
        ->where('status', 'active')
        ->whereDate('time', $tomorrow)
        ->with(['service:id,package_type,price,duration,description'])
        ->with(['washing_point:id,location_name,location_url'])
        ->with(['client:id,name,contact'])
        ->with(['washer:id,name,contact'])
        ->orderBy('time', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => "Tomorrow's bookings retrieved successfully",
        'data' => $bookings
    ]);
}



public function weekBookings()
{
    $user = Auth::guard('sanctum')->user();

    $startOfWeek = now()->startOfWeek()->format('Y-m-d');
    $endOfWeek = now()->endOfWeek()->format('Y-m-d');

    $bookings = Booking::where('washer_id', $user->id)
        ->where('status', 'active')
        ->whereBetween('time', [$startOfWeek, $endOfWeek])
        ->with(['service:id,package_type,price,duration,description'])
        ->with(['washing_point:id,location_name,location_url'])
        ->with(['client:id,name,contact'])
        ->with(['washer:id,name,contact'])
        ->orderBy('time', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'message' => "This week's bookings retrieved successfully",
        'data' => $bookings
    ]);
}

































 


    public function markComplete(Request $request)
    {
        $booking = Booking::findOrFail($request->id);
        $booking->status = 'completed';
        $booking->save();
        return response()->json(['success'=>true, 'status' => 'completed']);
    }


public function cancelBooking(Request $request)
{
   /* $request->validate([
        'receipt' => 'required|string'
    ]);*/ //$request-> rental_id

    // 1. Get booking by receipt
    $booking = Booking::where('receipt', $request->rental_id)->first();
    if (!$booking) {
        return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
    }

    // 2. Get client details
    $client = AppUser::find($booking->client_id);
    if (!$client) {
        return response()->json(['success' => false, 'message' => 'Client not found'], 404);
    }

    // 3. Update status
    $booking->status = 'cancelled';
    $booking->save();

    // 4. Send simple email
    Mail::raw("Hello {$client->name},\n\nYour booking with receipt #{$booking->receipt} has been cancelled s.", function ($message) use ($client) {
        $message->to($client->email)
                ->subject('Booking Cancelled');
    });

    return response()->json(['success' => true, 'message' => 'Booking cancelled and notification email sent']);
}






/*




    public function cancelBooking(Request $request)
    {
        Booking::findOrFail($request-> rental_id);
       $booking->status = 'cancelled';
        $booking->save();
        return response()->json(['success'=>true,'message' => 'cancelled']);
    }*/

    /*
    public function allBookings()
    {
        $bookings = Booking::with(['washer', 'service', 'client'])->get();
        return response()->json($bookings);
    }
    */


    public function allBookings()
{
    $bookings = Booking::with([
        'washer:id,id,name,img_url,contact,email',
        'client:id,id,name,img_url,contact,email',
        'service:id,id,package_type,img_url,price,duration,list,description',
        'washingPoint:id,id,location_name,location_url'
    ])->get();

    return response()->json($bookings);
}






    public function activeUserBookings()
    {
        $user = Auth::guard('sanctum')->user();
        $bookings = Booking::with(['washer', 'service', 'washingPoint'])
            ->where('client_id',$user->id)
            ->where('status', 'active')
            ->get();
        return response()->json($bookings);
    }

    public function pastUserBookings()
    {
        $user = Auth::guard('sanctum')->user();
        $bookings = Booking::with(['washer', 'service'])
            ->where('client_id',$user->id)
            ->where('status', 'completed')
            ->get();
        return response()->json($bookings);
    }







    public function autoCompleteStaleBookings()
    {
        $affected = Booking::where('status', 'active')
            ->where('time', '<', now()->subHours(2))
            ->update(['status' => 'completed']);

        return response()->json(['updated' => $affected]);
    }







}