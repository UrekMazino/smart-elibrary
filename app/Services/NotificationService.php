<?php

namespace App\Services;

use App\Models\User;
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

        // Unread reservation messages sent by patrons.
        $resMsg = DB::table('reservation_messages')
            ->where('sender_role', 'patron')
            ->whereNull('seen_at')
            ->count();
        if ($resMsg > 0) {
            $count += $resMsg;
            $items[] = self::item('message-square', 'primary',
                $resMsg . ' new reservation ' . self::plural($resMsg, 'message'),
                'From patrons',
                url('/admin/reservations'));
        }

        // Inquiries still awaiting a staff reply (no reply from a non-patron yet).
        $inq = DB::table('inquiries')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('reply_inquiries')
                    ->join('users', 'users.id', '=', 'reply_inquiries.user_id')
                    ->whereColumn('reply_inquiries.inquiry_id', 'inquiries.id')
                    ->where('users.usertype', '!=', 'Patron');
            })
            ->count();
        if ($inq > 0) {
            $count += $inq;
            $items[] = self::item('help-circle', 'info',
                $inq . ' ' . self::plural($inq, 'inquiry', 'inquiries') . ' awaiting reply',
                'Patrons waiting for a response',
                url('/admin/inquiry'));
        }

        return ['count' => $count, 'items' => $items];
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
