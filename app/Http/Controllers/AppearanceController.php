<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Appearance;
use Illuminate\Http\Request;

/**
 * Appearance: colours, status colours, text size, motion and font (App\Services\Appearance).
 *   - Each staff member's own, from Appearance in the profile menu (any staff).
 *   - The library default everyone else follows, from Administration → Utilities → Theme (manage.theme).
 */
class AppearanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function edit()
    {
        $user = auth()->user();
        return view('admin.appearance', [
            'prefs'    => Appearance::for($user),
            'personal' => is_array($user->appearance),
            'default'  => Appearance::siteDefault(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $user->appearance = Appearance::sanitize($request->only(['accent', 'custom', 'status', 'text', 'motion', 'font']));
        $user->save();
        return redirect()->route('appearance')->with('success', 'Your appearance is saved.');
    }

    /** Back to the library default (and any later change to it). */
    public function reset()
    {
        $user = auth()->user();
        $user->appearance = null;
        $user->save();
        return redirect()->route('appearance')->with('success', 'You now follow the library default.');
    }

    public function editDefault()
    {
        return view('admin.utilities.theme', ['prefs' => Appearance::siteDefault()]);
    }

    public function updateDefault(Request $request)
    {
        // "Back to original" sends reset=1: the template's own look.
        $prefs = $request->boolean('reset') ? Appearance::DEFAULTS
            : Appearance::sanitize($request->only(['accent', 'custom', 'status', 'text', 'motion', 'font']));
        Setting::updateOrCreate(['key' => 'appearance_default'], ['value' => json_encode($prefs)]);
        settingsFlush();
        return redirect()->route('utilities.theme')->with('success', 'The library default is saved. Staff who chose their own appearance keep it.');
    }
}
