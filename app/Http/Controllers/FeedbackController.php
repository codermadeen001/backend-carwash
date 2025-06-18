<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    // Add feedback
    public function addFeedback(Request $request)
    {
      /*  $request->validate([
            'feedback' => 'required|string',
        ]);*/

         $user = Auth::guard('sanctum')->user();
        $feedback = Feedback::create([
            'user_id' =>  $user->id,
            'feedback' => $request->message,
        ]);

        return response()->json(['success' => true, 'message' => 'Feedback submitted', 'data' => $feedback]);
    }

    // Retrieve logged-in user's feedback in descending order
    public function myFeedback()
    {
      // $user = Auth::guard('sanctum')->user();
       $user = Auth::guard('sanctum')->user();
       $id=$user->id;
        $feedbacks = Feedback::where('user_id',$id)->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $feedbacks]);
    }

    // Retrieve all feedback (admin use)
    public function allFeedback()
    {
        $feedbacks = Feedback::with('user')->orderBy('created_at', 'desc')->get();
       // return response()->json(['success' => true, 'data' => $feedbacks]);
       return response()->json(['success' => true, 'feedbacks' => $feedbacks]);

    }

    // Delete a feedback by ID
   /* public function deleteFeedback($id)
    {
        $feedback = Feedback::find($id);

        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found']);
        }

        // Only allow owner or admin (optional logic)
        if ($feedback->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $feedback->delete();
        return response()->json(['success' => true, 'message' => 'Feedback deleted']);
    }*/




    public function deleteFeedback($id)
{
    $feedback = Feedback::find($id);

    if (!$feedback) {
        return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
    }

   // return response()->json(['success' => true, 'message' => $feedback ], 200);

    try {
        $feedback->delete();
        return response()->json(['success' => true, 'message' => 'Feedback deleted']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Deletion failed'], 500);
    }
    
}

}
