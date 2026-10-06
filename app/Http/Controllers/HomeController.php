<?php

namespace App\Http\Controllers;
use App\Providers\RouteServiceProvider;

use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    public function index()
    {
        if(!Auth::user())
        {
            return view('opac.index');
        }
        else
        {
            if(Auth::user()->usertype == 'Patron')
            {
                return view('opac.index');
            }
            else
            {
                return redirect(self::adminLandingUrl(Auth::user()));
            }
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
            'access.configuration'         => 'admin/settings',
        ];
        // Module access is required as well for pages inside a module.
        $module = [
            'create.catalog' => 'access.cataloging', 'view.catalog.list' => 'access.cataloging', 'view.catalog.importlist' => 'access.cataloging',
            'circulation.reservation.view' => 'access.circulation', 'manage.circulation.checkouts' => 'access.circulation',
            'access.circulation.transaction' => 'access.circulation',
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
