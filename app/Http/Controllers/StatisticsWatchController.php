<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StatisticsWatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Administration → Accounts → Statistics watch (access.statistics.watch; App\Services\StatisticsWatch): a day's
 * unusual activity, and taking patrons out of the top-10 lists ("users": one id, or a burst's) or putting one back.
 * Both can be undone, so neither asks for the password.
 */
class StatisticsWatchController extends Controller
{
    public function index(Request $request)
    {
        $day = StatisticsWatch::day($request->query('day'));

        return view('admin.statistics-watch', [
            'day' => $day,
            'spikes' => StatisticsWatch::spikes($day),
            'accounts' => StatisticsWatch::accounts($day),
            'signUps' => StatisticsWatch::signUps($day),
            'excluded' => StatisticsWatch::excluded(),
        ]);
    }

    public function exclude(Request $request)
    {
        $data = $request->validate([
            'users' => ['required', 'array', 'max:500'],
            'users.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $names = StatisticsWatch::exclude($data['users'], $request->user(), $data['reason'] ?? null);

        return back()->with('success', match ($names->count()) {
            0 => 'Nothing changed: they are out of the top-10 lists already.',
            1 => $names->first() . ' is out of the top-10 lists: their views, downloads and ratings no longer count.',
            default => $names->count() . ' ' . Str::plural('account', $names->count()) . ' are out of the top-10 lists: their views, downloads and ratings no longer count.',
        });
    }

    public function putBack(Request $request)
    {
        $user = User::withTrashed()->findOrFail($request->validate(['user' => ['required', 'integer']])['user']);
        StatisticsWatch::putBack($user->id, $request->user());

        return back()->with('success', "$user->name is back in the top-10 lists.");
    }
}
