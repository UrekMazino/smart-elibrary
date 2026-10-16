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

        // Cap length to prevent oversized-payload abuse.
        if(mb_strlen((string) request()->inquiry_subject) > 255 || mb_strlen((string) request()->inquiry_message) > 5000)
        {
            return redirect()->back()->with('error', 'Your subject (max 255) or message (max 5000) is too long.');
        }

        $inq = new App\Models\Inquiry;
        $inq->subject = request()->inquiry_subject;

        // The record asked about, if the form names a real one (general inquiries have none).
        $catalogId = App\Models\Catalog::whereKey((int) request()->catalog_id_inquiry)->value('id');

        // Signed-in users only (route middleware). The sender's name, phone and email are read
        // from their account (view_inquiries), not copied here, so a correction or erasure is
        // made in one place.
        $inq->user_id = Auth::id();
        $inq->catalog_id = $catalogId;
        $inq->message = request()->inquiry_message;

        $inq->save();
        
        if(Auth::check() && in_array(Auth::user()->usertype, ['Administrator','Developer']))
            return redirect('/admin/inquiry');
        else
            return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }

    public function reply()
    {
        // Cap length to prevent oversized-payload abuse; message is required.
        $data = request()->validate([
            'catalog_id_inquiry_reply' => 'required|integer',
            'inquiry_message_reply'    => 'required|string|max:5000',
        ]);

        // Blocked accounts cannot use chat / inquiry (admins are never blocked).
        if(Auth::user()->is_blocked)
        {
            return redirect()->back()->with('error', 'Your account is blocked from replying to inquiries. Please contact the library.');
        }

        // A patron may only reply to their OWN inquiry; staff may reply to any.
        $this->authorizeInquiry($data['catalog_id_inquiry_reply']);

        $inq = new App\Models\Reply_inquiry;
        $inq->inquiry_id = $data['catalog_id_inquiry_reply'];
        $inq->user_id = Auth::user()->id;
        $inq->message = $data['inquiry_message_reply'];
        $inq->save();

        // Return to whichever inquiries page the reply was sent from (admin or patron).
        return redirect()->back()->with('success', 'Reply sent.');
    }

    /**
     * Authorize access to an inquiry thread: a patron may only touch their OWN
     * inquiry; staff (non-Patron) may access any. Prevents IDOR / id-enumeration
     * across the inquiry endpoints. Auth itself is enforced by the route middleware;
     * this is the per-record check (defence in depth).
     */
    private function authorizeInquiry($inquiryId)
    {
        $user = Auth::user();
        abort_if(! $user, 403);
        if ($user->usertype !== 'Patron') {
            abort_unless(App\Models\Inquiry::whereKey($inquiryId)->exists(), 404);
            return; // staff can access all inquiries
        }
        $owns = App\Models\Inquiry::where('id', $inquiryId)->where('user_id', $user->id)->exists();
        abort_if(! $owns, 403);
    }

    public function json2($id)
    {
        // Pure read: authorize + return the thread. The "seen" state change is done
        // separately via the markSeen() POST so this GET has no side effects.
        $this->authorizeInquiry($id);

        $inq = App\Models\View_inquiry_thread::where('id',$id)->orderBy('thread_date')->get();
        return $inq;
    }

    /**
     * Mark an inquiry thread as read for the current viewer. A POST (not a GET) so the
     * state change is CSRF-protected and can't be triggered by prefetch/embedding.
     *   - staff : upsert a per-admin inquiry_seens row (Messenger-style read receipt;
     *             drives the bell count + the Reply-button dot). No reply required.
     *   - patron: mark the library's replies as seen (clears their unread badge).
     */
    public function markSeen($id)
    {
        $this->authorizeInquiry($id);
        $user = Auth::user();

        if ($user->usertype !== 'Patron') {
            \DB::table('inquiry_seens')->updateOrInsert(
                ['inquiry_id' => $id, 'user_id' => $user->id],
                ['seen_at' => now(), 'updated_at' => now()]
            );
        } else {
            App\Models\Reply_inquiry::where('inquiry_id', $id)
                ->where('user_id', '!=', $user->id)
                ->update(['seen' => now(), 'seen_by' => $user->id]);
        }

        return response()->json(['ok' => true]);
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
        $this->authorizeInquiry($id);

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

        // Reverse direction: has the PATRON read the admin's latest message? The
        // patron's markSeen sets reply_inquiries.seen on staff replies. Only relevant
        // when the admin sent the last message (otherwise the patron receipt is moot).
        $patronSeen = ['seen' => false, 'name' => null, 'initials' => '', 'at' => null];
        if (! $lastFromPatron) {
            $latestAdminReply = \DB::table('reply_inquiries')
                ->where('inquiry_id', $id)
                ->where('user_id', '!=', $inquiry->user_id) // authored by staff, not the patron owner
                ->orderByDesc('created_at')
                ->first();

            if ($latestAdminReply && $latestAdminReply->seen) {
                $pName = \DB::table('users')->where('id', $inquiry->user_id)->value('name') ?: 'Patron';
                $parts = preg_split('/\s+/', trim((string) $pName)) ?: [];
                $pInit = strtoupper(substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
                $patronSeen = [
                    'seen'     => true,
                    'name'     => $pName,
                    'initials' => $pInit !== '' ? $pInit : '?',
                    'at'       => \Carbon\Carbon::parse($latestAdminReply->seen)->format('M d, Y g:i A'),
                ];
            }
        }

        return response()->json([
            'seens'          => $items,
            'anySeen'        => $items->count() > 0,
            'lastFromPatron' => $lastFromPatron,
            'patronSeen'     => $patronSeen,
        ]);
    }
}
