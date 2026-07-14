<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App;

class InquiryController extends Controller
{
    public function create()
    {
        // Blocked accounts cannot use chat / inquiry.
        if(Auth::check() && Auth::user()->is_blocked)
        {
            return redirect()->back()->with('error', 'Your account is blocked from sending inquiries. Please contact the library.');
        }

        // Subject and message are both required (guards against empty/whitespace-only
        // submissions that bypass the client-side `required` attributes).
        if(trim((string) request()->inquiry_subject) === '' || trim((string) request()->inquiry_message) === '')
        {
            return redirect()->back()->with('error', 'Please enter both a subject and your inquiry message.');
        }

        $inq = new App\Models\Inquiry;
        $inq->subject = request()->inquiry_subject;

        if(Auth::check())
        {
            $inq->user_id = Auth::user()->id;
            $inq->catalog_id = request()->catalog_id_inquiry;
            $inq->fullname = Auth::user()->name;
            $inq->contactnum = Auth::user()->contactnum;
            $inq->email = Auth::user()->email;
            $inq->message = request()->inquiry_message;
        }
        else
        {
            $inq->catalog_id = request()->catalog_id_inquiry;
            $inq->fullname = request()->inquiry_fullname;
            $inq->contactnum = request()->inquiry_contactnum;
            $inq->email = request()->inquiry_email;
            $inq->message = request()->inquiry_message;
        }

        $inq->save();
        
        if(Auth::check() && in_array(Auth::user()->usertype, ['Administrator','Developer']))
            return redirect('/admin/inquiry');
        else
            return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }

    public function reply()
    {
        // Blocked accounts cannot use chat / inquiry (admins are never blocked).
        if(Auth::check() && Auth::user()->is_blocked)
        {
            return redirect()->back()->with('error', 'Your account is blocked from replying to inquiries. Please contact the library.');
        }

        $inq = new App\Models\Reply_inquiry;
        $inq->inquiry_id = request()->catalog_id_inquiry_reply;
        $inq->user_id = Auth::user()->id;
        $inq->message = request()->inquiry_message_reply;
        $inq->save();

        // Return to whichever inquiries page the reply was sent from (admin or patron).
        return redirect()->back()->with('success', 'Reply sent.');
    }

    public function json2($id)
    {
        // Messenger-style read receipt: when an admin OPENS the thread (clicks Reply),
        // record a per-admin "seen" — reading it is enough to clear their notification,
        // no reply required. Multiple admins each get their own row.
        if (Auth::check() && Auth::user()->usertype !== 'Patron') {
            \DB::table('inquiry_seens')->updateOrInsert(
                ['inquiry_id' => $id, 'user_id' => Auth::user()->id],
                ['seen_at' => now(), 'updated_at' => now()]
            );
        }

        $inq = App\Models\View_inquiry_thread::where('id',$id)->orderBy('thread_date')->get();
        return $inq;
    }

    public function seen($id)
    {
        $inq = App\Models\Reply_inquiry::where('inquiry_id',$id)->where('user_id','!=',Auth::user()->id)->update(['seen' => date('Y-m-d H:i:s'),'seen_by' => Auth::user()->id]);
        return $inq;
    }

    /**
     * Seen receipts for an inquiry thread.
     *   - admins: every admin who has viewed the thread at/after the latest message
     *     (name + initials + time), so the chat can show multiple "Seen by" avatars.
     *   - patron: a single "Seen by Library" avatar when the library has read the
     *     patron's most recent message (lastFromPatron && anySeen), regardless of how
     *     many admins actually saw it.
     */
    public function seenReceipts($id)
    {
        $inquiry = App\Models\Inquiry::find($id);
        if (! $inquiry) {
            return response()->json(['seens' => [], 'anySeen' => false, 'lastFromPatron' => false]);
        }

        // Latest activity in the thread (real timestamps from the base tables) and
        // whether that last message came from the patron who owns the inquiry.
        $lastReply = \DB::table('reply_inquiries')->where('inquiry_id', $id)->orderByDesc('created_at')->first();
        if ($lastReply) {
            $lastAt = $lastReply->created_at;
            $lastFromPatron = ((int) $lastReply->user_id === (int) $inquiry->user_id);
        } else {
            $lastAt = $inquiry->created_at;   // only the original patron message
            $lastFromPatron = true;
        }

        // Admins who have seen the thread at/after that last message.
        $seens = \DB::table('inquiry_seens as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->where('s.inquiry_id', $id)
            ->whereNotNull('s.seen_at')
            ->where('s.seen_at', '>=', $lastAt)
            ->orderBy('s.seen_at')
            ->get(['u.id', 'u.name', 's.seen_at']);

        $items = $seens->map(function ($s) {
            $parts = preg_split('/\s+/', trim((string) $s->name)) ?: [];
            $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
            return [
                'name'     => $s->name,
                'initials' => $initials !== '' ? $initials : '?',
                'seen_at'  => \Carbon\Carbon::parse($s->seen_at)->format('M d, Y g:i A'),
            ];
        })->values();

        return response()->json([
            'seens'          => $items,
            'anySeen'        => $items->count() > 0,
            'lastFromPatron' => $lastFromPatron,
        ]);
    }
}
