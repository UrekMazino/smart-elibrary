<?php

namespace App\Services;

use App\Models\User;
use App\Models\Circulation;
use App\Models\View_inquiry_thread;
use Illuminate\Support\Facades\DB;

/**
 * Centralised in-app notifications for both patron and staff (admin) accounts.
 *
 * Single source of truth: every notification is derived from existing "unseen" /
 * pending signals across reservations, inquiries and to-review — no extra tables.
 * Returns a uniform payload consumed by the shared notification-bell partial:
 *
 *   ['count' => int, 'items' => [ ['icon','color','title','text','url'], ... ]]
 *
 * Items naturally clear when the user acts on them (opening the target page marks
 * messages seen; approvals / reviews drop out once handled), so the next page load
 * recomputes a fresh count — no polling and no persisted read-state required.
 */
class NotificationService
{
    public static function for(User $user): array
    {
        return $user->usertype === 'Patron'
            ? self::patron($user)
            : self::staff($user);
    }

    protected static function patron(User $user): array
    {
        $items = [];
        $count = 0;

        // New messages from the library on the patron's reservation threads.
        $resMsg = DB::table('reservation_messages')
            ->where('user_id', $user->id)
            ->where('sender_role', 'admin')
            ->whereNull('seen_at')
            ->count();
        if ($resMsg > 0) {
            $count += $resMsg;
            $items[] = self::item('message-square', 'primary',
                $resMsg . ' new ' . self::plural($resMsg, 'message') . ' from the library',
                'On your reservation ' . self::plural($resMsg, 'thread'),
                url('/patron/transactions'));
        }

        // Process update: reservations approved and ready for pickup (status 2).
        $ready = DB::table('circulations')->where('user_id', $user->id)->where('status', 2)->count();
        if ($ready > 0) {
            $count += $ready;
            $items[] = self::item('check-circle', 'success',
                $ready . ' ' . self::plural($ready, 'reservation') . ' ready for pickup',
                'Approved — visit the library to claim',
                url('/patron/transactions'));
        }

        // Other changes the patron hasn't seen on their Reservations page yet (approved ones are
        // "ready for pickup" above): declined, checked out, returned.
        $changed = reservationUpdatesQuery($user->id)->where('status', '<>', 2)->pluck('status');
        if ($changed->isNotEmpty()) {
            $count += $changed->count();
            $what = $changed->unique()->sort()->map(fn ($s) => [1 => 'back to For Approval', 3 => 'checked out', 4 => 'declined', 5 => 'returned'][$s] ?? null)->filter();
            $items[] = self::item('refresh-cw', 'info',
                $changed->count() . ' ' . self::plural($changed->count(), 'reservation') . ' updated',
                ucfirst($what->implode(', ')),
                url('/patron/transactions'));
        }

        // New replies from the library on the patron's inquiries.
        $inq = View_inquiry_thread::where('user_id', $user->id)
            ->where('replied_by', '!=', $user->id)
            ->whereNull('seen')
            ->count();
        if ($inq > 0) {
            $count += $inq;
            $items[] = self::item('help-circle', 'info',
                $inq . ' new inquiry ' . ($inq == 1 ? 'reply' : 'replies'),
                'The library responded to your inquiry',
                url('/patron/inquiries'));
        }

        // Materials awaiting the patron's review. reviewPendingQuery() is grouped by
        // catalog_id, so ->count() would return a single group's row-count; count the
        // distinct materials with ->get()->count() (same as getNotif('review')).
        $review = reviewPendingQuery($user->id)->get()->count();
        if ($review > 0) {
            $count += $review;
            $items[] = self::item('star', 'warning',
                $review . ' ' . self::plural($review, 'material') . ' to review',
                'Share your feedback',
                url('/patron/to-review'));
        }

        return ['count' => $count, 'items' => $items];
    }

    protected static function staff(User $user): array
    {
        $items = [];
        $count = 0;

        // Reservation requests awaiting approval (status 1 = For Approval).
        $pending = DB::table('circulations')->where('status', 1)->count();
        if ($pending > 0) {
            $count += $pending;
            $items[] = self::item('clock', 'warning',
                $pending . ' ' . self::plural($pending, 'reservation') . ' awaiting approval',
                'Review and approve or decline',
                url('/admin/reservations'));
        }

        // Unread reservation messages sent by patrons, on the reservations the Reservations page
        // lists. Those on declined or closed reservations count with the inquiries below.
        $resMsg = getReservationUnseenMsgCount();
        if ($resMsg > 0) {
            $count += $resMsg;
            $items[] = self::item('message-square', 'primary',
                $resMsg . ' new reservation ' . self::plural($resMsg, 'message'),
                'From patrons',
                url('/admin/reservations'));
        }

        // Inquiries with patron activity THIS admin hasn't opened yet. Opening the
        // thread (clicking Reply) records a per-admin seen and clears it — no reply
        // required. A later patron follow-up makes it unread again.
        $inq = count(self::unreadInquiryIds($user));
        // Reservation conversations with unread patron messages: all are listed in Inquiries; the
        // ones the Reservations page doesn't list (declined, closed) are announced with them.
        $resThreads = DB::table('reservation_messages as m')
            ->join('circulations as c', 'c.reservation_code', '=', 'm.reservation_code')
            ->where('m.sender_role', 'patron')->whereNull('m.seen_at')
            ->select('m.reservation_code', DB::raw('MAX(c.status) as status'))->groupBy('m.reservation_code')
            ->pluck('status', 'm.reservation_code');
        $closedThreads = $resThreads->reject(fn ($st) => in_array((int) $st, Circulation::ON_RESERVATIONS_PAGE, true))->count();

        $inqItem = $inq + $closedThreads;
        if ($inqItem > 0) {
            $count += $inqItem;
            $items[] = self::item('help-circle', 'info',
                $inqItem . ' unread ' . self::plural($inqItem, 'inquiry', 'inquiries'),
                'New questions or replies from patrons',
                url('/admin/inquiry'));
        }

        // 'inquiries' feeds the sidebar's Inquiries badge: inquiries plus every reservation conversation
        // with unread patron messages, as Inquiries lists them all.
        return ['count' => $count, 'items' => $items, 'inquiries' => $inq + $resThreads->count()];
    }

    /**
     * IDs of inquiries with patron activity the given admin hasn't opened since the
     * latest patron message. Single source of truth for both the bell count and the
     * per-row "unseen" dot on the admin inquiry table.
     */
    public static function unreadInquiryIds(User $user): array
    {
        return DB::table('inquiries as i')
            ->leftJoin('inquiry_seens as s', function ($j) use ($user) {
                $j->on('s.inquiry_id', '=', 'i.id')->where('s.user_id', '=', $user->id);
            })
            ->whereRaw("GREATEST(i.created_at, COALESCE((SELECT MAX(r.created_at) FROM reply_inquiries r WHERE r.inquiry_id = i.id AND r.user_id = i.user_id), i.created_at)) > COALESCE(s.seen_at, '1970-01-01 00:00:00')")
            ->pluck('i.id')
            ->all();
    }

    protected static function item(string $icon, string $color, string $title, string $text, string $url): array
    {
        return compact('icon', 'color', 'title', 'text', 'url');
    }

    protected static function plural(int $n, string $singular, ?string $plural = null): string
    {
        return $n === 1 ? $singular : ($plural ?? $singular . 's');
    }
}
