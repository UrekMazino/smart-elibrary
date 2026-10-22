<?php

namespace App\Http\Controllers;
use App\Providers\RouteServiceProvider;

use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    /** The OPAC home, for guests, patrons and staff alike (staff open it from the admin navbar). */
    public function index()
    {
        return view('opac.index');
    }

    /**
     * Where a signed-in user belongs when nothing else is asked for (after login, for example):
     * staff in the admin, everyone else on the OPAC home.
     */
    public static function landingUrl($user): string
    {
        return $user && $user->isStaff() ? self::adminLandingUrl($user) : url(RouteServiceProvider::HOME);
    }

    /** Admin navbar "OPAC": remember the admin page it was opened from, then show the OPAC. */
    public function openOpac()
    {
        $from = url()->previous();
        if (str_starts_with($from, url('/admin')) && self::opensWithGet($from)) {
            session(['opac.return_to' => $from]);
        }

        return redirect('/home');
    }

    /** OPAC "Return to admin": back to the admin page the OPAC was opened from. */
    public function returnToAdmin()
    {
        return redirect(session()->pull('opac.return_to') ?: self::adminLandingUrl(Auth::user()));
    }

    /** Some admin pages are shown by a POST (the record page, a batch's details): not one to return to. */
    private static function opensWithGet(string $url): bool
    {
        try {
            app('router')->getRoutes()->match(Request::create($url, 'GET'));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * First admin page this user's role can open (sidebar order), so a role without
     * Dashboard access lands somewhere useful instead of on a 403.
     */
    public static function adminLandingUrl($user): string
    {
        $pages = [
            'access.dashboard'             => 'admin/dashboard',
            'create.catalog'               => 'admin/create-record',
            'view.catalog.list'            => 'admin/record-list',
            'view.catalog.importlist'      => 'admin/batch-list',
            'circulation.reservation.view' => 'admin/reservations',
            'manage.circulation.checkouts' => 'admin/check-out-in',
            'access.circulation.transaction' => 'admin/borrowed',
            'access.inventory'             => 'admin/inventory',
            'access.accounts'              => 'admin/patron',
            'access.inquiry'               => 'admin/inquiry',
            'access.news'                  => 'admin/news',
            'access.reports'               => 'admin/report',
            'access.settings'              => 'admin/settings',
            'manage.material.types'        => 'admin/utilities',
            'view.config.template'         => 'admin/utilities',
            'access.ai.sync.monitor'       => 'admin/utilities',
            'manage.theme'                 => 'admin/utilities',
        ];
        // Module access is required as well for pages inside a module.
        $module = [
            'create.catalog' => 'access.cataloging', 'view.catalog.list' => 'access.cataloging', 'view.catalog.importlist' => 'access.cataloging',
            'circulation.reservation.view' => 'access.circulation', 'manage.circulation.checkouts' => 'access.circulation',
            'access.circulation.transaction' => 'access.circulation',
            'access.settings' => 'access.configuration', 'manage.material.types' => 'access.configuration',
            'view.config.template' => 'access.configuration', 'access.ai.sync.monitor' => 'access.configuration',
            'manage.theme' => 'access.configuration',
        ];

        foreach ($pages as $permission => $url) {
            if ($user->can($permission) && (! isset($module[$permission]) || $user->can($module[$permission]))) {
                return url($url);
            }
        }

        return url('admin/profile');   // no module at all: at least their own profile
    }

    public function index2()
    {
        return redirect(RouteServiceProvider::HOME);
    }
}
