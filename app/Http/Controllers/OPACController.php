<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

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

    /**
     * The {id} in the route is ignored on purpose — the signed-in user is the only account
     * that can be edited here, so passing someone else's id changes nothing.
     */
    public function updatePatronProfile(Request $request, $id)
    {
        $user = Auth::user();

        // Email uniqueness was only checked by the /patron/profile/validate-email AJAX call
        // the page makes before submitting — i.e. not at all for anything posting directly.
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contactnum'     => ['required', 'string', 'max:255'],
            'birthdate'      => ['required', 'date', 'before:today'],   // 'Y-m-d' (OPAC) or 'm/d/Y' (admin picker)
            'affiliation'    => ['nullable', 'string', 'max:255'],
            'sex'            => ['nullable', Rule::in(['Male', 'Female'])],   // column is an enum
            'patron_type_id' => ['required', 'exists:patrontypes,id'],        // NOT NULL column
            'region_id'      => ['nullable', 'integer', 'exists:regions,id'],
            'address'        => ['nullable', 'string', 'max:1000'],
        ]);

        $user->update([
            'name'           => $data['name'],
            'contactnum'     => $data['contactnum'],
            'affiliation'    => $data['affiliation'] ?? null,
            'sex'            => $data['sex'] ?? null,
            'birthdate'      => \Carbon\Carbon::parse($data['birthdate'])->toDateString(),
            'patrontypes_id' => $data['patron_type_id'],
            'region_id'      => $data['region_id'] ?? null,
            'email'          => $data['email'],
            'location'       => $data['address'] ?? null,
        ]);

        // Redirect rather than render: a refresh used to re-submit the form.
        return redirect('/patron/profile')->with('success', 'Profile updated.');
    }

    /**
     * Patrons had no password endpoint of their own: the Change Password modal on this page
     * posted to /admin/profile/update/password, which sits behind the `admin` middleware, so
     * a patron was silently redirected to the homepage and their password was never changed.
     * Mirrors AdminController@updatePassword.
     */
    public function updatePassword(Request $request)
    {
        // Named bag so the page can tell a failed password change apart from a failed
        // profile save and re-open the modal with the right messages.
        $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect('/patron/profile')->with('success', 'Password changed.');
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

        // Status changes the patron hasn't seen: marked "Updated" on this visit, then seen. Each is
        // marked with the status shown here, so a change made meanwhile still counts next time.
        $updated = $list->filter(fn ($c) => $c->patron_seen_status === null || (int) $c->status !== (int) $c->patron_seen_status);
        foreach ($updated as $c) {
            \DB::table('circulations')->where('id', $c->id)->where('status', $c->status)->update(['patron_seen_status' => $c->status]);
        }

        return view('opac.transactions',['list' => $list,'flag' => $flag,'unread' => $unread,'updated' => $updated->pluck('id')->all()]);
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
        // Newest first: the chat button adds General inquiries here too, and the latest is the one to check.
        $list = App\Models\View_inquiry::where('user_id',Auth::user()->id)->orderByDesc('created_at')->paginate(5);
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
