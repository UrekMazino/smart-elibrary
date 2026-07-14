<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;
use App\Models\User;

use MeiliSearch\Client;


class OPACController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function profile()
    {
        $user = User::find(Auth::id());
        return view('opac.profile', ['user' => $user]);
    }

    public function updatePatronProfile(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'contactnum' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'string', 'max:100']
        ]);
        User::where('id',Auth::user()->id)
            ->update([
                'name' => $request->name,
                'contactnum' => $request->contactnum,
                'affiliation' => $request->affiliation,
                'sex' => $request->sex,
                'birthdate' => $request->birthdate,
                'patrontypes_id' => $request->patron_type_id,
                'region_id' => $request->region_id,
                'email' => $request->email,
                'location' => $request->address
            ]);

        $user = User::find(Auth::user()->id);
        return view('opac.profile', ['user' => $user, 'success' => 'Profile updated successfully!']);
    }

    public function transactions()
    {
        $list = App\Models\Circulation::where('user_id',Auth::user()->id)->get();

        $flag = true;
        if(count($list) == 0)
        {
            $flag = false;
        }

        // Unread messages from the library (admin) per reservation, for the Messages badge.
        $unread = \DB::table('reservation_messages')
            ->whereIn('reservation_code', $list->pluck('reservation_code')->filter()->unique()->values())
            ->where('sender_role', 'admin')
            ->whereNull('seen_at')
            ->select('reservation_code', \DB::raw('count(*) as c'))
            ->groupBy('reservation_code')
            ->pluck('c', 'reservation_code');

        return view('opac.transactions',['list' => $list,'flag' => $flag,'unread' => $unread]);
    }

    // ---- Patron side of the per-reservation chat (same reservation_messages thread the
    //      admin uses on /admin/reservations) ----

    public function reservationChatJson($reservation_code)
    {
        // A patron may only read a thread for a reservation that is their own.
        $owns = App\Models\Circulation::where('reservation_code', $reservation_code)
                    ->where('user_id', Auth::id())->exists();
        abort_if(!$owns, 403);

        $messages = \DB::table('reservation_messages')
            ->where('reservation_code', $reservation_code)
            ->orderBy('created_at')
            ->get(['id', 'reservation_code', 'sender_role', 'message', 'created_at']);

        // Opening the thread marks the library's messages as seen (clears the badge).
        \DB::table('reservation_messages')
            ->where('reservation_code', $reservation_code)
            ->where('sender_role', 'admin')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return response()->json($messages);
    }

    public function reservationChatReply(Request $request)
    {
        $request->validate([
            'reservation_code' => 'required|string|max:100',
            'message'          => 'required|string|max:5000',
        ]);

        // Blocked accounts cannot use chat.
        if (Auth::user()->is_blocked) {
            return response()->json(['ok' => false, 'error' => 'Your account is blocked from messaging the library.'], 403);
        }

        $owns = App\Models\Circulation::where('reservation_code', $request->reservation_code)
                    ->where('user_id', Auth::id())->exists();
        abort_if(!$owns, 403);

        \DB::table('reservation_messages')->insert([
            'reservation_code' => $request->reservation_code,
            'user_id'          => Auth::id(),
            'sender_id'        => Auth::id(),
            'sender_role'      => 'patron',
            'message'          => $request->message,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function inquiries()
    {
        $list = App\Models\View_inquiry::where('user_id',Auth::user()->id)->paginate(5);
        $flag = true;
        if(count($list) == 0)
        {
            $flag = false;
        }

        return view('opac.inquiries',['list' => $list,'flag' => $flag]);
    }

    public function toReview()
    {
        // Materials the patron downloaded OR read, minus those they've already rated —
        // one entry per material, and one review (covering both download and read)
        // clears it for good.
        $list = reviewPendingQuery(Auth::user()->id)
                    ->orderBy('last_at','desc')
                    ->paginate(5);

        $flag = count($list) > 0;

        return view('opac.to-review',['list' => $list,'flag' => $flag]);
    }

}
