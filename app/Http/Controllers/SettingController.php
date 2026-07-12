<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        return view('admin.settings', [
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        // Toggles: present in the POST (checkbox) => '1', absent => '0'
        foreach (['enable_placeholder_covers'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0']
            );
        }

        return redirect('/admin/settings')->with('success', 'Settings saved.');
    }
}
