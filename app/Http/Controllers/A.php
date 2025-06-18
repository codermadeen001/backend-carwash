<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Cloudinary\Cloudinary;

class AppUserController extends Controller
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary();
    }

    public function account_creation(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:app_users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()]);
        }

        $role = $email == "admin@gmail.com" ? "admin" : "client";

        $save = AppUser::create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        return $save
            ? response()->json(["success" => true, "message" => "Account created successfully"])
            : response()->json(["success" => false, "message" => "Account creation failed"]);
    }

    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $user = AppUser::where('email', $email)->first();

        if (!$user) {
            return response()->json(["success" => false, "message" => "Invalid credentials!"]);
        }

        if ($user->status == 1 || $user->status === true) {
            return response()->json(["success" => false, "message" => "Account is suspended!"]);
        }

        if (Hash::check($password, $user->password)) {
            $token = $user->createToken('authToken')->plainTextToken;
            return response()->json(["success" => true, "token" => $token, "role" => $user->role]);
        }

        return response()->json(["success" => false, "message" => "Invalid credentials!"]);
    }

    public function password_reset(Request $request)
    {
        $email = $request->email;
        $user = AppUser::where("email", $email)->first();

        if ($user) {
            $newPassword = Str::random(5);
            $user->password = Hash::make($newPassword);
            $user->save();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'syeundainnocent@gmail.com';
                $mail->Password = 'vwuergurzyjucjmc';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('syeundainnocent@gmail.com', 'AutoClean');
                $mail->addAddress($email);
                $mail->Subject = 'Password Reset';
                $mail->Body = "Your new password is: $newPassword";
                $mail->send();
            } catch (Exception $e) {
                return response()->json(["success" => false, "message" => "Mail not sent"]);
            }

            return response()->json(["success" => true, "message" => "New password sent to your email"]);
        }

        return response()->json(["success" => false, "message" => "No account found"]);
    }

    // [All other methods remain unchanged unless you explicitly want password logic updated.]
}
