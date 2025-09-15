<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function update()
    {
        $patron = User::where('id',request()->userid)
                    ->update([
                        'name' => request()->fullname,
                        'contactnum' => request()->contactnum,
                        'affiliation' => request()->affiliation,
                        'sex' => request()->sex,
                        'birthdate' => request()->birthdate,
                        'patrontypes_id' => request()->patrontypes_id,
                        'region_id' => request()->region_id,
                    ]);
        return redirect('profile', )->with('success', 'Profile updated successfully!');
    }

    public function checkEmail(Request $request)
    {
       // Trim leading and trailing whitespaces
       $email = trim($request->user_email);
       // Remove illegal characters
       $email = filter_var($email, FILTER_SANITIZE_EMAIL);
       // Check if the email is in a valid format
       if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
           $data = ["result" => false, "message" => "Invalid email format."];
           return json_encode($data);
       }
       // Check if the email is unique
       $existingUser = User::where('email', $email)->first();
       
       if ($existingUser && $existingUser->id != Auth::user()->id) {
           $data = ["result" => false, "message" => "The email has already been taken."];
       } else {
           $data = ["result" => true, "message" => "The email ". $email ." is valid!"];
       }

       return json_encode($data);
    }
}
