<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\AppUser;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Str;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

//use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AppUserController extends Controller
{

  
    
    public function account_creation(Request $request)
    {
        $user=AppUser::where("email", $request->email)->first();
        if($user){
            return response()->json(["success" => false, "message" => "Account exists!"]);
        }
    
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:app_users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            $validatorerrors = $validator->errors();
            return response()->json(["success" => false, "message" => "$validatorerrors"]);
        }

        $role=null;
        if($request->email=="admin@gmail.com"){
            $role="admin";
        }else{
             $role="client";
        }

       
        $save=AppUser::create([
                "email" => $request->email,
                'password' => Hash::make($request->password),
                'role'=>$role,
            ]);

            
       
    
        if ($save){

    return response()->json(["success" => true, "message" => "Account created successfully"]);
    }else{
         return response()->json(["success" => false, "message" => "Account creation failed"]);
    }
    }

 




public function washer_creation(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255|unique:app_users',
    ]);

    if ($validator->fails()) {
        return response()->json(["success" => false, "message" => $validator->errors()]);
    }

    $user = AppUser::where("email", $request->email)->first();
    if ($user) {
        return response()->json(["success" => false, "message" => "Account already exists!"]);
    }

    // Generate random 5-character password
    $rawPassword = Str::random(5);

    // Create the user
    $save = AppUser::create([
        "email" => $request->email,
        'name'=>$request->name,
        "password" => Hash::make($rawPassword),
        "role" =>"car detailer" 

    ]);

    if ($save) {
        $userEmail = $request->email;

       $fromEmail = 'syeundainnocent@gmail.com';  
       $fromPassword =  "vwuergurzyjucjmc";  // Replace with your Gmail app password
    

        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $fromEmail;
            $mail->Password = $fromPassword;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom($fromEmail, 'AutoClean');
            $mail->addAddress($userEmail);

            $mail->Subject = 'Welcome to AutoClean System';
            $mail->Body = "Hello,\n\nYour account as a Car Detailer has been successfully created.\n\nLogin Email: $userEmail\nPassword: $rawPassword\n\nPlease change your password after logging in.\n\nWelcome aboard!";

            $mail->send();

            return response()->json(["success" => true, "message" => "Car Detailer account created and credentials sent to email."]);
        } catch (Exception $e) {
            return response()->json(["success" => false, "message" => "Account created but email not sent."]);
        }
    } else {
        return response()->json(["success" => false, "message" => "Failed to create account."]);
    }
}







public function client_creation(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:255|unique:app_users',
    ]);

    if ($validator->fails()) {
        return response()->json(["success" => false, "message" => $validator->errors()]);
    }

    $user = AppUser::where("email", $request->email)->first();
    if ($user) {
        return response()->json(["success" => false, "message" => "Account already exists!"]);
    }

    // Generate random 5-character password
    $rawPassword = Str::random(5);

    // Create the user
    $save = AppUser::create([
        "email" => $request->email,
        "password" => Hash::make($rawPassword),
    ]);

    if ($save) {
        $userEmail = $request->email;

         $fromEmail = 'syeundainnocent@gmail.com';  
         $fromPassword =  "vwuergurzyjucjmc";  // Replace with your Gmail app password
    
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $fromEmail;
            $mail->Password = $fromPassword;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom($fromEmail, 'AutoClean');
            $mail->addAddress($userEmail);

            $mail->Subject = 'Welcome to AutoClean System';
            $mail->Body = "Hello,\n\nYour account has been successfully created.\n\nLogin Email: $userEmail\nPassword: $rawPassword\n\nPlease change your password after logging in.\n\nWelcome aboard!";

            $mail->send();

            return response()->json(["success" => true, "message" => "Car Detailer account created and credentials sent to email."]);
        } catch (Exception $e) {
            return response()->json(["success" => false, "message" => "Account created but email not sent."]);
        }
    } else {
        return response()->json(["success" => false, "message" => "Failed to create account."]);
    }
}


    
   

    public function login(Request $request)
    {
        $credentials=$request->only("email","password");
    
            $user=AppUser::where("email", $credentials["email"])->first();
            if(!$user){
                return response()->json(["success"=>false, "message"=>"Invalid credentials!"]);
            }
            if ($user->status) {
               return response()->json(["success" => false, "message" => "Account is suspended!"]);
            }
            $auth=($user && Hash::check($credentials["password"], $user->password));

           if($auth){
                $token = $user->createToken('authToken')->plainTextToken;
                return response()->json(["success"=>true,"token" => $token,"role"=>$user->role ]);
            }else{
                return response()->json(["success"=>false, "message"=>"Invalid credentials!"]);
            }

           
            

    }




    public function password_reset(Request $request)
    {
        $email=$request->email;
        $user=AppUser::where("email", $email)->first();
        if($user){
            $password=Str::random(5);
            $hashedPassword=Hash::make($password);
            $user->password=$hashedPassword;
            $user->save();
            
            $userEmail = $email;
             $fromEmail = 'syeundainnocent@gmail.com';  
             $fromPassword =  "vwuergurzyjucjmc";  // Replace with your Gmail app password
    
    
            $mail = new PHPMailer(true);
    
            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';   
                $mail->SMTPAuth = true;
                $mail->Username = $fromEmail;    
                $mail->Password = $fromPassword; 
                $mail->SMTPSecure = 'tls';       
                $mail->Port = 587;               
                // Email settings
                $mail->setFrom($fromEmail, 'AutoClean');
                $mail->addAddress($userEmail);   
    
                $mail->Subject = 'Password Reset ';
                $mail->Body ="Password for AutoClean System,\n has been reset to ".$password;
                $mail->send();
            } catch (Exception $e) {
                return response()->json(["success" => false,  "message"=>"mail not send"]);
            }

            return response()->json(["success"=>true, "message" =>"New password has been send to your email"]);
        }else{
            return response()->json(["success"=>false, "message" =>"no account found"]);
        }
       
    }




    
    public function google_login(Request $request)
    {
        
        $credentials=$request->only("userName", "userEmail", "userImgUrl");
         $user=AppUser::where("email", $credentials["userEmail"])->first();
         //return response()->json(["success"=>false,"message"=>"Account is suspended" ]);

      if($user){
        
             if ($user->status) {
                 return response()->json(["success" => false, "message" => "Account is suspended!"]);
            }

           if ($user->img_url == '' || $user->img_url == null) {
               $user->img_url = $credentials["userImgUrl"];
               $user->save();
            }

            $token = $user->createToken('authToken')->plainTextToken;

            
            return response()->json(["success"=>true,"isAdmin"=>false,"message"=>"Login successful",  "token" =>  $token, "role"=>$user->role]); 
      }else{
            $password= Str::random(32);
            $user =AppUser::create([
                "name" => $credentials["userName"],
                "email" => $credentials["userEmail"],
                "img_url" => $credentials["userImgUrl"],
                'role'=>'client',
                'password' => Hash::make($password),
            ]);

                $userEmail = $credentials["userEmail"];
                $name=$credentials["userName"];
         
                 // Email sending logic
                 $fromEmail = 'syeundainnocent@gmail.com';  // Replace with your Gmail address
                 $fromPassword =  "vwuergurzyjucjmc";  // Replace with your Gmail app password
         
                 $mail = new PHPMailer(true);
         
                 try {
                    // SMTP configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';   
                    $mail->SMTPAuth = true;
                    $mail->Username = $fromEmail;    
                    $mail->Password = $fromPassword; 
                    $mail->SMTPSecure = 'tls';     
                    $mail->Port = 587;               
        
                    // Email settings
                    $mail->setFrom($fromEmail, 'AutoClean');
                    $mail->addAddress($userEmail);   // Recipient's email
        
                    $mail->Subject = ' Welcome to AutoClean System ';
                    $mail->Body = 
   "Greetings,\n\nYour account with AutoClean has been successfully created.\n\nThank you for choosing us,\n\n";

                    $mail->send();
                } catch (Exception $e) {
                   
                    return response()->json(["success" => false,  "message"=>"mail not send"]);
                }
                $token = $user->createToken('authToken')->plainTextToken;
            return response()->json(["success" => true, "message" => "Account created successfully",'token'=>$token, "role"=>$user->role]);
       } 
          
    }


    public function toggleStatus(Request $request)
{
    $user = AppUser::find($request->user_id);

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found.']);
    }

    $user->status = !$user->status;
    $user->save();

    $statusText = $user->status ? 'activated' : 'suspended';

    return response()->json(['success' => true, 'message' => "User $statusText.", 'status' => $user->status]);
}



    public function logout(Request $request)
    {
        // Revoke the user's token
        $user = Auth::guard('sanctum')->user();
        
        $user->tokens()->delete();
        return response()->json(["success" => true, "message" => "Logged out"]);
       
    }



     public function destroy(Request $request)
    {
        // Revoke the user's token
        $user = Auth::guard('sanctum')->user();
        
       $user->tokens()->delete();
        return response()->json(["success" => true, "message" => "Account deleted"]);
       
    }







     public function getLoggedInUser(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        //$user = $request->user();
        return response()->json([
            'success'=>true,
            'name' => $user->name,
            'email' => $user->email,
            'contact' => $user->contact,
            'image_url' => $user->img_url,
            'role'=>$user->role,
            'createdAt' => $user->created_at,  // <-- include created_at timestamp
            'updatedAt' => $user->updated_at   // <-- include updated_at timestamp (optional
        ]);
    }

/*



    public function updateLoggedInUser(Request $request)
    {
        $user = $request->user();

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('contact')) {
            $user->contact = $request->contact;
        }
$inno=null;
        if ($request->hasFile('image')) {
            $uploadedFileUrl = Cloudinary::upload($request->file('image')->getRealPath())->getSecurePath();
            $user->img_url = $uploadedFileUrl;
            $inno=$uploadedFileUrl;
        }

       $r= $user->save();

       if($r){
        return response()->json(['success'=>true,'message' => $inno]);
       }else{
        return response()->json(['success'=>false,'message' => 'User not updated successfully']);
       }

       // return response()->json(['message' => 'User updated successfully']);
    }

    */






     // Fetch all users with role 'client'
    public function getClients()
    {
        $clients = AppUser::where('role', 'client')->get();

        return response()->json([
            'success' => true,
            'results' => $clients,
        ]);
    }
      public function getwashers()
    {
        $clients = AppUser::where('role', 'car detailer')->get();

        return response()->json($clients);
    }




 protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary();
    }

    public function updateLoggedInUser(Request $request)
    {
        $user = $request->user();
        //$user = Auth::guard('sanctum')->user();

       if ($request->has('name')) {
       //if ($request->name=!null || $request->name=!'') {
            $user->name = $request->name;
            //$user->save();
             //return response()->json(['success' => true, 'message' => $request->name]);
        }

       if ($request->has('contact')) {
        //if ($request->contact=!null||$request->contact=!'') {
            $user->contact = $request->contact;
           // $user->save();
        }

        $imageUrl = null;

        /*

        if (!$request->hasFile('image')) {
    return response()->json(['success'=>false, 'message'=> 'No image received']);
}

if (!$request->file('image')->isValid()) {
    return response()->json(['success'=>false, 'message' => 'Image file is invalid']);
}
*/

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $uploadedFile = $this->cloudinary->uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'user_images']
            );
            $imageUrl = $uploadedFile['secure_url'] ?? null;
            $user->img_url = $imageUrl;
            //$user->save();
        }

        if ($user->save()) {
            return response()->json(['success' => true, 'message' => 'profile updated']);
        } else {
            return response()->json(['success' => false, 'message' => 'unable to update profile']);
        }
    }



    public function washer_balance(){
         $user = Auth::guard('sanctum')->user();
          return response()->json(['success' => true,'balance'=>$user->wallet, 'message' => 'Wallet retrieven successfully']);

    }




public function updateStatus(Request $request)
{
   /* $request->validate([
        'userId' => 'required|integer|exists:users,id',
        'status' => 'required|string|in:suspend,activate', // Changed to match your frontend
    ]);
    */

    try {
        $user = AppUser::findOrFail($request->userId);

        // Save boolean true/false based on the action
        $user->status = ($request->status === 'suspend'); // true for suspend, false for activate
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->status ? 'User suspended successfully' : 'User activated successfully',
            'data' => [
                'id' => $user->id,
                'status' => $user->status // boolean true/false
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user status',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function deleteUser(Request $request)
{
    try {
        $userId = $request->userId;

        $user = AppUser::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        // Delete user (cascade will handle feedbacks, bookings)
        $user->delete();

        return response()->json(['success' => true, 'message' => 'User and related data deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred', 'error' => $e->getMessage()]);
    }
}







public function Availability(Request $request)
{
    $user = Auth::guard('sanctum')->user();
   // $availability=$user->availability;

     return response()->json([
        'success' => true,
        'message' => 'Availability status retrieven successfully',
        'available' => $user->availability
    ]);
}

public function toggleAvailability(Request $request)
{
    $user = Auth::guard('sanctum')->user();

    if (!$request->has('available')) {
        return response()->json(['success' => false, 'message' => 'Availability value is required'], 400);
    }

    $user->availability = $request->available;
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Availability status updated successfully',
        'available' => $user->availability
    ]);
}



    
    
}










/**  $fromEmail = 'syeundainnocent@gmail.com';  // Replace with your Gmail address    $fromPassword =  "vwuergurzyjucjmc";  // Replace with your Gmail app password
    
 */


 /*
 📦 Prerequisites
Install Cloudinary SDK for Laravel:

bash
Copy
Edit
composer require cloudinary-labs/cloudinary-laravel
Publish config:

bash
Copy
Edit
php artisan vendor:publish --provider="CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider"
Set this in .env:

env
Copy
Edit
CLOUDINARY_URL=cloudinary://769447669581899:SMXcoOapJt4KElCoVzbCJ_SzIqM@dadcnkqbg
🔐 Setup Auth Middleware
Ensure your routes are inside the auth:sanctum or auth:api middleware group.

*/