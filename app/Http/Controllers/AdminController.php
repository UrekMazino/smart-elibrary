<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Carbon\Carbon;
use MeiliSearch\Client;
use App\Mail\RegistrationMail;
use App\Listeners\FailedSendingListener;
use Milon\Barcode\DNS1D;
use App\Models\Csf;
use App\Models\Download;
use App\Models\Catalog;
use App\Models\MarcTemplate;
use App\Models\Material;
use App\Models\Patrontype;
use App\Models\Role;
use App\Models\User;
use App\Models\ViewCatalog;
use App\Models\View_holding_uncatalog;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile', ['user' => $user]);
    }

    public function validateEmail(Request $request)
    {

        // Trim leading and trailing whitespaces
        $email = trim($request->user_email);
        // Remove illegal characters
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        // Check if the email is in a valid format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data = ["result" => false, "message" => "Invalid email format."];
            return json_encode($data);
        }
        // Check if the email is unique
        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $existingUser->id != Auth::user()->id) {
            $data = ["result" => false, "message" => "The email has already been taken."];
        } else {
            $data = ["result" => true, "message" => "The email ". $email ." is valid!"];
        }

        return json_encode($data);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // Email uniqueness was only ever checked by the /validate-email AJAX call the page
        // makes before submitting — i.e. not at all for anything that posts directly.
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contactnum'     => ['required', 'string', 'max:255'],
            'birthdate'      => ['required', 'string', 'max:50'],
            'affiliation'    => ['nullable', 'string', 'max:255'],
            'sex'            => ['nullable', Rule::in(['Male', 'Female'])],   // column is an enum
            'patron_type_id' => ['required', 'exists:patrontypes,id'],        // NOT NULL column
            'region_id'      => ['nullable', 'string', 'max:10', 'exists:regions,regCode'],
            'address'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Was: $userId = User::where(...)->update([...]), then User::where('id', $userId).
        // update() returns the number of affected rows, not an id — so this looked up user
        // #1 (or #0 when nothing changed), handed the wrong/NULL user to the view, and the
        // page died on $user->sex. Update the authenticated model directly instead.
        $user->update([
            'name'           => $data['name'],
            'contactnum'     => $data['contactnum'],
            'affiliation'    => $data['affiliation'] ?? null,
            'sex'            => $data['sex'] ?? null,
            'birthdate'      => $data['birthdate'],
            'patrontypes_id' => $data['patron_type_id'],
            'region_id'      => $data['region_id'] ?? null,
            'email'          => $data['email'],
            'location'       => $data['address'] ?? null,
        ]);

        // Redirect rather than render: a refresh used to re-submit the form.
        return redirect('/admin/profile')->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        // Named bag so the page can tell a failed password change apart from a failed
        // profile save and re-open the modal with the right messages.
        $request->validateWithBag('updatePassword', [
            // 'current_password' checks against the signed-in user's hash for us; this used
            // to be a hand-rolled Hash::check that returned a view mid-flight.
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect('/admin/profile')->with('success', 'Password changed.');
    }

    public function dashboard()
    {
        $today = Carbon::now();
        $holdings = DB::table('catalogs')
            ->select(DB::raw('count(*) as totalTitles'))
            ->where('deleted_at', null)
            ->get();
        $cataloged = DB::table('catalogs')
            ->select(DB::raw('count(*) as totalCataloged'))
            ->where('is_published', 1)
            ->where('deleted_at', null)
            ->get();

        $totalUsersRaw = DB::table('users')
            ->select(DB::raw('count(*) as totalUsers'))
            ->where('usertype', 'Patron')
            ->get();
        $totalUsers = $totalUsersRaw[0]->totalUsers;
        $totalCatalogs = $cataloged[0]->totalCataloged;
        $totalTitles = $holdings[0]->totalTitles;
        $totalUncatalogs = $totalTitles - $totalCatalogs;
        $totalDownloads = Download::count();
        $patronTypes = Patrontype::get();
        $materialTypes = Material::get();

        // optimized
        $usersByPatronType = DB::table('users')
                            ->leftJoin(DB::raw('(SELECT user_id, COUNT(*) as num_downloads FROM downloads GROUP BY user_id) as download_counts'), 'users.id', '=', 'download_counts.user_id')
                            ->select('users.patrontypes_id', DB::raw('COUNT(users.id) as num_users'), DB::raw('COALESCE(SUM(download_counts.num_downloads), 0) as num_downloads'))
                            ->where('usertype', 'Patron')
                            ->where('deleted_at', null)
                            ->groupBy('users.patrontypes_id')
                            ->orderByDesc('num_users')
                            ->get();
        // optimized
        $holdingsByMaterialType = DB::table('view_catalogs')
                            ->leftJoin(DB::raw('(SELECT catalog_id, COUNT(*) as num_downloads FROM downloads GROUP BY catalog_id) as download_counts'), 'view_catalogs.id', '=', 'download_counts.catalog_id')
                            ->select('view_catalogs.material_type_id', DB::raw('COUNT(view_catalogs.id) as num_holdings'), DB::raw('COALESCE(SUM(download_counts.num_downloads), 0) as num_downloads'))
                            ->where('deleted_at', null)
                            ->groupBy('view_catalogs.material_type_id')
                            ->orderByDesc('num_holdings')
                            ->get();

        foreach($patronTypes as $pT) {
            $patronTypesArr[$pT->id] = $pT->types;
        }

        foreach($materialTypes as $mT) {
            $materialTypesArr[$mT->id] = $mT->name;
        }

        // --- Operational snapshot: real, actionable counts surfaced on the dashboard ---
        //   circulation status: 1 = For Approval, 2 = Approved, 3 = Open (checked out),
        //   4 = Declined, 5 = Closed/Returned
        $pendingReservations = DB::table('circulations')->where('status', 1)->count();
        $activeLoans         = DB::table('circulations')->where('status', 3)->count();
        $onHoldMaterials     = $totalUncatalogs;                 // catalogs with is_published = 0
        $totalInquiries      = DB::table('inquiries')->count();

        // --- Collection completeness: distinct catalogs that have full-text / a cover ---
        $withFulltext = DB::table('multimedia')->whereNull('deleted_at')->distinct()->count('catalog_id');
        $withCover    = DB::table('frontpages')->whereNull('deleted_at')->distinct()->count('catalog_id');

        return view('admin.index',
            [
                'totalUsers' => $totalUsers,
                'totalDownloads' => $totalDownloads,
                'totalTitles' => $totalTitles,
                'totalPublications' => $totalCatalogs,
                'patronTypes' => $patronTypes,
                'usersByPatronType' => $usersByPatronType,
                'patronTypesArr' => $patronTypesArr,
                'holdingsByMaterialType' => $holdingsByMaterialType,
                'materialTypesArr' => $materialTypesArr,
                // operational snapshot + collection completeness
                'pendingReservations' => $pendingReservations,
                'activeLoans' => $activeLoans,
                'onHoldMaterials' => $onHoldMaterials,
                'totalInquiries' => $totalInquiries,
                'withFulltext' => $withFulltext,
                'withCover' => $withCover,
            ]);
    }

    /**
     * Website traffic for the dashboard "Number of Views" chart: real per-month
     * view counts for the last 6 months, split into Guest vs Registered.
     *
     * Lazy-loaded by the dashboard (fetched only when the chart scrolls into view)
     * and cached for 10 minutes, so it never blocks page load and the aggregation
     * runs at most once per cache window regardless of table size.
     */
    public function viewsTrafficJson()
    {
        $data = cache()->remember('dash_views_traffic_6m', 600, function () {
            $start = Carbon::now()->startOfMonth()->subMonths(5); // 6 buckets incl. current month

            // is_guest is stored as the strings 'YES' / 'NO'.
            $rows = DB::table('views')
                ->where('created_at', '>=', $start)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
                ->selectRaw("SUM(is_guest = 'YES') as guest")
                ->selectRaw("SUM(is_guest <> 'YES') as registered")
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');

            // Build a continuous 6-month axis, filling any empty months with 0.
            $labels = $guest = $registered = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = Carbon::now()->startOfMonth()->subMonths($i);
                $key = $m->format('Y-m');
                $labels[]     = $m->format('M Y');
                $guest[]      = (int) ($rows[$key]->guest ?? 0);
                $registered[] = (int) ($rows[$key]->registered ?? 0);
            }

            return ['labels' => $labels, 'guest' => $guest, 'registered' => $registered];
        });

        return response()->json($data);
    }

    public function borrowed()
    {
        return view('admin.borrowed');
    }

    public function visit()
    {
        return view('admin.visits');
    }

    public function inquiry()
    {
        // Inquiries now only come from registered users (guests are directed to email),
        // so we surface each patron's ban/block state for the admin controls.
        $list = App\Models\View_inquiry::get();
        $userFlags = App\Models\User::whereIn('id', $list->pluck('user_id')->filter()->unique()->values())
                        ->get(['id', 'is_banned', 'is_blocked'])->keyBy('id');

        // Inquiries the current admin hasn't opened since the latest patron message
        // (drives the red "unseen" dot on each Reply button). array_flip -> O(1) lookup.
        $unread = array_flip(App\Services\NotificationService::unreadInquiryIds(Auth::user()));

        return view('admin.inquiry', ['list' => $list, 'userFlags' => $userFlags, 'unread' => $unread]);
    }

    public function inquiryjson()
    {
        $list = App\Models\View_inquiry::get();
        return $list;
    }

    // Toggle a patron's ban/block flag (used by Patrons, Inquiry and Reservation pages).
    //   flag=banned  -> cannot log in AND cannot use the Read feature
    //   flag=blocked -> cannot use chat / inquiry
    public function toggleUserFlag(Request $request)
    {
        $user = App\Models\User::find($request->user_id);
        if (! $user) return response()->json(['ok' => false], 404);

        $col = $request->flag === 'banned' ? 'is_banned' : 'is_blocked';
        $user->$col = $user->$col ? 0 : 1;
        $user->save();

        // Banning a patron auto-declines their outstanding reservation tickets
        // (For Approval / Approved). Checked-out (Open) loans are left untouched.
        $declined = 0;
        if ($request->flag === 'banned' && $user->is_banned) {
            $declined = DB::table('circulations')
                ->where('user_id', $user->id)
                ->whereIn('status', [1, 2])
                ->update([
                    'status'      => 4,
                    'declined_at' => now(),
                    'declined_by' => auth()->user()->name,
                ]);
        }

        return response()->json(['ok' => true, 'flag' => $request->flag, 'value' => (bool) $user->$col, 'declined' => $declined]);
    }

    public function patron()
    {
        return view('admin.patron');
    }

    public function administrators()
    {
        return view('admin.administrators');
    }

    public function report()
    {
        return view('admin.report');
    }

    // Build [$table, $list] for a report request; shared by preview and print so the
    // query logic lives in one place. $table maps 1:1 to typeReport.
    private function buildReport()
    {
        $applyDate = function ($q, $col = 'created_at') {
            switch ((int) request()->select_type) {
                case 1: return $q->whereDate($col, date('Y-m-d', strtotime(request()->filter_date_dt)));
                case 2: return $q->whereYear($col, request()->filter_month_year)->whereMonth($col, request()->filter_month_mon);
                case 3: return $q->whereYear($col, request()->filter_year_yr);
                case 4: return $q->whereBetween($col, [date('Y-m-d', strtotime(request()->start)), date('Y-m-d 23:59:59', strtotime(request()->end))]);
                default: return $q;
            }
        };

        $table = 0;
        $list  = collect();

        switch ((int) request()->typeReport) {
            case 1: // Acquisition (catalog records by material / source / keyword)
                $table = 1;
                $material = !(request()->selectMaterial === ['0'] || empty(request()->selectMaterial));
                $source   = !(request()->selectSource === ['0'] || empty(request()->selectSource));
                $q = request()->keyword ? App\Models\ViewCatalog::search(request()->keyword) : App\Models\ViewCatalog::query();
                if ($material) $q->whereIn('material_type_id', request()->selectMaterial);
                if ($source)   $q->whereIn('consortia', request()->selectSource);
                $list = $q->get();
                break;

            case 2: // Patron (registered users)
                $table = 2;
                $list = $applyDate(App\Models\User::where('usertype', 'Patron'))->get();
                break;

            case 3: // Borrowed Materials (physical copy loans / circulations)
                $table = 3;
                $q = DB::table('circulations')
                    ->leftJoin('circulation_reservations', 'circulation_reservations.reservation_code', '=', 'circulations.reservation_code')
                    ->leftJoin('users', 'users.id', '=', 'circulations.user_id')
                    ->leftJoin('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
                    ->select('circulations.*', 'circulation_reservations.title_statement as res_title', 'users.name as user_name', 'circulation_status.name as status_name')
                    ->orderBy('circulations.created_at', 'DESC');
                $list = $applyDate($q, 'circulations.created_at')->get();
                break;

            case 4: // Inquiries
                $table = 4;
                $list = $applyDate(App\Models\View_inquiry::query())->get();
                break;

            case 5: // CSF (customer satisfaction feedback)
                $table = 5;
                $list = $applyDate(Csf::query())->get();
                break;

            case 6: // Publication Downloads
                $table = 6;
                $list = $applyDate(Download::query())->get();
                break;

            case 7: // Publication Uploads (catalog records added in the period)
                $table = 7;
                $list = $applyDate(App\Models\ViewCatalog::whereNull('deleted_at'), 'created_at')
                        ->orderBy('created_at', 'DESC')->get();
                break;
        }

        return [$table, $list];
    }

    private function reportRatings()
    {
        return [1 => 'Poor', 2 => 'Unsatisfactory', 3 => 'Satisfactory', 4 => 'Very Satisfactory', 5 => 'Outstanding'];
    }

    public function reportprint()
    {
        [$table, $list] = $this->buildReport();
        return view('admin.print-report', ['table' => $table, 'list' => $list, 'ratings' => $this->reportRatings()]);
    }

    // In-page preview: returns just the results table partial (injected via AJAX).
    public function reportPreview()
    {
        [$table, $list] = $this->buildReport();
        return view('admin.partials.report-table', ['table' => $table, 'list' => $list, 'ratings' => $this->reportRatings(), 'preview' => true]);
    }

    /**
     * Whitelist the DataTables sort column. The client sends the column's `data` key, which
     * would otherwise go straight into orderBy() — unsafe, and it breaks outright for
     * render-only columns (Roles/Action) whose `data` isn't a real DB column.
     * Anything unrecognised falls back to Name, the default sort on both people tables.
     */
    private function userSortColumn($requested)
    {
        $sortMap = [
            'name'       => 'name',
            'email'      => 'email',
            'contactnum' => 'contactnum',
            'location'   => 'location',
            'created_at' => 'created_at',
        ];

        return $sortMap[$requested] ?? 'name';
    }

    public function patronjson(Request $request)
    {
        $draw       = $request->get('draw');
        $start      = $request->get('start');
        $rowperpage = $request->get('length'); // Rows display per page

        $columnName_arr = $request->get('columns');
        $order_arr      = $request->get('order');
        $search_arr     = $request->get('search');

        $columnIndex     = $order_arr[0]['column'];
        $columnName      = $this->userSortColumn($columnName_arr[$columnIndex]['data'] ?? 'name');
        $columnSortOrder = strtolower($order_arr[0]['dir']) === 'desc' ? 'desc' : 'asc';
        $searchValue     = $search_arr['value'];

        // Status filter chips (All / Active / Blocked / Banned). "Active" = neither flag set.
        $status = $request->get('status_filter', 'all');
        $applyStatus = function ($query) use ($status) {
            if ($status === 'banned')  $query->where('is_banned', 1);
            if ($status === 'blocked') $query->where('is_blocked', 1);
            if ($status === 'active')  $query->where('is_banned', 0)->where('is_blocked', 0);
            return $query;
        };

        // The search terms have to match the fetch query below, otherwise the row count
        // disagrees with the rows actually returned and paging breaks.
        $applySearch = function ($query) use ($searchValue) {
            return $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('contactnum', 'like', '%' . $searchValue . '%')
                    ->orWhere('library_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('location', 'like', '%' . $searchValue . '%')
                    ->orWhere('created_at', 'like', '%' . $searchValue . '%');
            });
        };

        $base = fn () => App\Models\User::where('usertype', 'Patron');

        $totalRecords           = $base()->count();
        $totalRecordswithFilter = $applySearch($applyStatus($base()))->count();

        $records = $applySearch($applyStatus($base()))
            ->select('id', 'username', 'name', 'email', 'contactnum', 'library_code', 'location', 'created_at', 'is_banned', 'is_blocked')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $record) {
            $data_arr[] = array(
                "id"           => $record->id,
                "username"     => $record->username,
                "name"         => $record->name,
                "email"        => $record->email,
                "contactnum"   => $record->contactnum,
                "library_code" => $record->library_code,
                "location"     => $record->location,
                "created_at"   => date('Y-m-d H:i:s', strtotime($record->created_at)),
                "is_banned"    => (int) $record->is_banned,
                "is_blocked"   => (int) $record->is_blocked,
            );
        }

        // Counts for the status chips — one grouped query, not three.
        $flags = $base()->selectRaw('SUM(is_banned = 1) AS banned, SUM(is_blocked = 1) AS blocked, SUM(is_banned = 0 AND is_blocked = 0) AS active')->first();

        $response = array(
            "draw"                 => intval($draw),
            "iTotalRecords"        => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData"               => $data_arr,
            "counts"               => [
                'all'     => $totalRecords,
                'active'  => (int) $flags->active,
                'blocked' => (int) $flags->blocked,
                'banned'  => (int) $flags->banned,
            ],
        );

        return response()->json($response);
    }
    
    public function adminJson(Request $request)
    {
        $draw       = $request->get('draw');
        $start      = $request->get('start');
        $rowperpage = $request->get('length'); // Rows display per page

        $columnName_arr = $request->get('columns');
        $order_arr      = $request->get('order');
        $search_arr     = $request->get('search');

        $columnIndex     = $order_arr[0]['column'];
        $columnName      = $this->userSortColumn($columnName_arr[$columnIndex]['data'] ?? 'name');
        $columnSortOrder = strtolower($order_arr[0]['dir']) === 'desc' ? 'desc' : 'asc';
        $searchValue     = $search_arr['value'];

        // The search terms have to match the fetch query below, otherwise the row count
        // disagrees with the rows actually returned and paging breaks.
        $applySearch = function ($query) use ($searchValue) {
            return $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('contactnum', 'like', '%' . $searchValue . '%')
                    ->orWhere('library_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('location', 'like', '%' . $searchValue . '%');
            });
        };

        $base = fn () => App\Models\User::where('usertype', 'Administrator');

        $totalRecords           = $base()->count();
        $totalRecordswithFilter = $applySearch($base())->count();

        // Eager-load roles + their permissions: without this each row fires its own
        // queries for getRoleNames()/getPermissionsViaRoles() (N+1).
        $records = $applySearch($base())
            ->select('id', 'username', 'name', 'email', 'contactnum', 'location', 'created_at')
            ->with('roles.permissions')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $record) {
            $perm = $record->roles
                        ->flatMap->permissions
                        ->pluck('display_name')
                        ->filter()
                        ->unique()
                        ->sort(fn ($a, $b) => strcmp($a, $b))
                        ->values()
                        ->all();

            $data_arr[] = array(
                "id"          => $record->id,
                "username"    => $record->username,
                "name"        => $record->name,
                "email"       => $record->email,
                "contactnum"  => $record->contactnum,
                "permissions" => $perm,
                "roles"       => $record->roles->pluck('name')->all(),
                "location"    => $record->location,
                "created_at"  => date('Y-m-d H:i:s', strtotime($record->created_at)),
            );
        }

        $response = array(
            "draw"                 => intval($draw),
            "iTotalRecords"        => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData"               => $data_arr
        );

        return response()->json($response);
    }

    /**
     * `developer` is the superuser role: it implicitly has everything, and
     * RolePermissionController refuses to edit its permissions so nobody can lock
     * themselves out. Handing that role out from here would route straight around
     * that guard, so only a developer may assign it.
     */
    private function assignableRoles()
    {
        return Role::query()
            ->unless(auth()->user()->hasRole('developer'), fn ($q) => $q->where('name', '!=', 'developer'))
            ->orderBy('display_name')
            ->get();
    }

    public function createAdministrator(Request $request)
    {
        return view('admin.create-admin', ['roles' => $this->assignableRoles()]);
    }

    public function storeAdministrator(Request $request)
    {
        // Previously: username and role went unvalidated, and every administrator was
        // created with the hardcoded password 'admin'.
        $data = $request->validate([
            'userName'      => ['required', 'string', 'max:255', 'unique:users,username'],
            'fullName'      => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'contactNumber' => ['required', 'string', 'max:255'],
            'role'          => ['required', 'string', 'exists:roles,name'],
        ]);

        // exists:roles,name still allows any role name that exists — including the
        // superuser one — so re-check against what this user is actually allowed to grant.
        abort_if(! $this->assignableRoles()->contains('name', $data['role']), 403);

        $libraryCode = generateLibraryCode();
        $password    = generateRandomPassword();

        $user = User::create([
            'username'     => $data['userName'],
            'name'         => $data['fullName'],
            'email'        => $data['email'],
            'contactnum'   => $data['contactNumber'],
            'password'     => Hash::make($password),
            'library_code' => $libraryCode,
        ]);

        // usertype is not mass-assignable (privilege-sensitive) — set it explicitly.
        $user->usertype = 'Administrator';
        $user->save();

        $user->assignRole($data['role']);

        event(new Registered($user));

        // Mail is switched off, so the credentials can't be emailed. Flash them once for
        // the creator to hand over — flash (not a permanent store), so they survive exactly
        // this redirect and are gone on the next request.
        //
        // Redirect back to the create page, not to the administrators list: the credentials
        // modal lives here. (It always has — but this used to redirect to the list, which
        // has no modal, so the library code was silently never shown to anyone.)
        return redirect()->route('admin.create')->with([
            'success'            => 'Administrator account created.',
            'new_admin_name'     => $user->name,
            'new_admin_password' => $password,
            'user_library_code'  => $libraryCode,
        ]);
    }

    public function countdynamicdates(Request $request)
    {
        $today = Carbon::now();
        switch ($request->filter) {
            case 'today':
                $patrons = User::whereDate('created_at',date("Y-m-d",strtotime($today)))->count();
                $downloads = Download::whereDate('created_at',date("Y-m-d",strtotime($today)))->count();
                $titles = Catalog::whereDate('created_at',date("Y-m-d",strtotime($today)))->count();
                $publications = Catalog::whereDate('created_at',date("Y-m-d",strtotime($today)))->where('is_published', 1)->count();

                // PATRON/USERS PER CATEGORIES TODAY
                $numPatrons = DB::table('users')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(DISTINCT users.id) as num_users')
                    )
                    ->whereDate('users.created_at',date("Y-m-d",strtotime($today)))
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_users')
                    ->get();

                $numPatronsDownloads = DB::table('downloads')
                    ->leftJoin('users', 'downloads.user_id', '=', 'users.id')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereDate('downloads.created_at',date("Y-m-d",strtotime($today)))
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_downloads')
                    ->get();

                // HOLDINGS/TITLES PER CATEGORIES TODAY                
                $numHoldings = DB::table('catalogs')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(DISTINCT catalogs.id) as num_holdings')
                    )
                    ->whereDate('catalogs.created_at',date("Y-m-d",strtotime($today)))
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_holdings')
                    ->get();

                $numHoldingsDownloads = DB::table('downloads')
                        ->leftJoin('catalogs', 'downloads.catalog_id', '=', 'catalogs.catalog_id')
                        ->select('catalogs.material_type_id',
                            DB::raw('COUNT(downloads.id) as num_downloads')
                        )
                        ->whereDate('downloads.created_at',date("Y-m-d",strtotime($today)))
                        ->groupBy('catalogs.material_type_id')
                        ->orderByDesc('num_downloads')
                        ->get();            
            break;
            case 'month':
                $patrons = User::whereYear('created_at',$today->year)->whereMonth('created_at', '=', date('m'))->count();
                $downloads = Download::whereYear('created_at',$today->year)->whereMonth('created_at', '=', date('m'))->count();                
                $titles = Catalog::whereYear('created_at',$today->year)->whereMonth('created_at', '=', date('m'))->count();
                $publications = Catalog::whereYear('created_at',$today->year)->whereMonth('created_at', '=', date('m'))->where('is_published', 1)->count();

                // PATRON/USERS PER CATEGORIES IN A MONTH
                $numPatrons = DB::table('users')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(DISTINCT users.id) as num_users')
                    )
                    ->whereYear('users.created_at',$today->year)->whereMonth('users.created_at', '=',  date('m'))
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_users')
                    ->get();

                $numPatronsDownloads = DB::table('downloads')
                    ->leftJoin('users', 'downloads.user_id', '=', 'users.id')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereYear('downloads.created_at',$today->year)->whereMonth('downloads.created_at', '=', date('m'))
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_downloads')
                    ->get();

                // HOLDINGS/TITLES PER CATEGORIES IN A MONTH                
                $numHoldings = DB::table('catalogs')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(DISTINCT catalogs.id) as num_holdings')
                    )
                    ->whereYear('catalogs.created_at',$today->year)->whereMonth('catalogs.created_at', '=',  date('m'))
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_holdings')
                    ->get();

                $numHoldingsDownloads = DB::table('downloads')
                        ->leftJoin('catalogs', 'downloads.catalog_id', '=', 'catalogs.catalog_id')
                        ->select('catalogs.material_type_id',
                            DB::raw('COUNT(downloads.id) as num_downloads')
                        )
                        ->whereYear('downloads.created_at',$today->year)->whereMonth('downloads.created_at', '=', date('m'))
                        ->groupBy('catalogs.material_type_id')
                        ->orderByDesc('num_downloads')
                        ->get();
            break;
            case 'year':
                $patrons = User::whereYear('created_at',$today->year)->count();
                $downloads = Download::whereYear('created_at',$today->year)->count();
                $titles = Catalog::whereYear('created_at',$today->year)->count();
                $publications = Catalog::whereYear('created_at',$today->year)->where('is_published', 1)->count();
                
                // PATRON/USERS PER CATEGORIES IN A YEAR
                $numPatrons = DB::table('users')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(DISTINCT users.id) as num_users')
                    )
                    ->whereYear('users.created_at', $today->year)
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_users')
                    ->get();

                $numPatronsDownloads = DB::table('downloads')
                    ->leftJoin('users', 'downloads.user_id', '=', 'users.id')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereYear('downloads.created_at', $today->year)
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_downloads')
                    ->get();

                // HOLDINGS/TITLES PER CATEGORIES IN A YEAR
                $numHoldings = DB::table('catalogs')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(DISTINCT catalogs.id) as num_holdings')
                    )
                    ->whereYear('catalogs.created_at',$today->year)
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_holdings')
                    ->get();

                $numHoldingsDownloads = DB::table('downloads')
                    ->leftJoin('catalogs', 'downloads.catalog_id', '=', 'catalogs.catalog_id')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereYear('downloads.created_at',$today->year)
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_downloads')
                    ->get();

            break;
            case 'dates':
                $from = $request->start;
                $end = $request->end;

                $patrons = User::whereBetween('created_at', [$from, $end])->count();
                $downloads = Download::whereBetween('created_at', [$from, $end])->count();
                $titles = Catalog::whereBetween('created_at', [$from, $end])->count();
                $publications = Catalog::whereBetween('created_at', [$from, $end])->where('is_published', 1)->count();

                // PATRON/USERS PER CATEGORIES IN GIVEN DATES
                $numPatrons = DB::table('users')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(DISTINCT users.id) as num_users')
                    )
                    ->whereBetween('users.created_at', [$from, $end])
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_users')
                    ->get();

                $numPatronsDownloads = DB::table('downloads')
                    ->leftJoin('users', 'downloads.user_id', '=', 'users.id')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereBetween('downloads.created_at', [$from, $end])
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_downloads')
                    ->get();


                // HOLDINGS/TITLES PER CATEGORIES IN GIVEN DATES
                $numHoldings = DB::table('catalogs')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(DISTINCT catalogs.id) as num_holdings')
                    )
                    ->whereBetween('catalogs.created_at', [$from, $end])
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_holdings')
                    ->get();

                $numHoldingsDownloads = DB::table('downloads')
                    ->leftJoin('catalogs', 'downloads.catalog_id', '=', 'catalogs.catalog_id')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->whereBetween('downloads.created_at', [$from, $end])
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_downloads')
                    ->get();

            break;
            case 'total':
                $holdings = DB::table('catalogs')
                ->select(DB::raw('count(*) as totalTitles'))
                ->get();
                $cataloged = DB::table('catalogs')
                ->select(DB::raw('COUNT(*) as totalCataloged'))
                ->where('is_published', 1)
                ->get();
        
                $totalUsersRaw = DB::table('users')
                ->select(DB::raw('count(*) as totalUsers'))
                ->get();
                $patrons = $totalUsersRaw[0]->totalUsers;
                $publications = $cataloged[0]->totalCataloged;
                $titles = $holdings[0]->totalTitles;
                $downloads = Download::count();

                // optimized 
                 $numPatrons = DB::table('users')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(DISTINCT users.id) as num_users')
                    )
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_users')
                    ->get();

                $numPatronsDownloads = DB::table('downloads')
                    ->leftJoin('users', 'downloads.user_id', '=', 'users.id')
                    ->select('users.patrontypes_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->groupBy('users.patrontypes_id')
                    ->orderByDesc('num_downloads')
                    ->get();

                // optimized
                $numHoldings = DB::table('catalogs')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(DISTINCT catalogs.id) as num_holdings')
                    )
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_holdings')
                    ->get();

                $numHoldingsDownloads = DB::table('downloads')
                    ->leftJoin('catalogs', 'downloads.catalog_id', '=', 'catalogs.catalog_id')
                    ->select('catalogs.material_type_id',
                        DB::raw('COUNT(downloads.id) as num_downloads')
                    )
                    ->groupBy('catalogs.material_type_id')
                    ->orderByDesc('num_downloads')
                    ->get();
                                
            break;
        }

        $response = [
            'patron' => $patrons,
            'download' => $downloads, 
            'title' => $titles, 
            'publication' => $publications,
            'numHoldingsDownloads' => $numHoldingsDownloads,
            'numPatronsDownloads' => $numPatronsDownloads,
            'numPatrons' => $numPatrons,
            'numHoldings' => $numHoldings
        ];
        return response()->json($response);
    }

    public function perDateChartJson(Request $request) 
    {
        $table_name = '';
        $col_to_count = '';

        switch ($request->filter) {
            case 'titleChart':
                $table_name = 'catalogs';
                $col_to_count = "*";
            break;
            case 'patronsChart':
                $table_name = 'users';
                $col_to_count = "*";
            break;
            case 'downloadsChart':
                $table_name = 'downloads';
                $col_to_count = "*";
            break;
            case 'publicationsChart':
                $table_name = 'catalogs';
                $col_to_count = 'is_published';
            break;
        }
        
        $datesArr = [];
        $totalPerDateArr = [];

        switch ($request->selection) {

            case 'years':
                $currentYear = date('Y'); 
                $oldestYear = $currentYear - 10;
                $recordsPerYear = DB::table($table_name)
                                    ->select(DB::raw('YEAR(created_at) as year'), DB::raw('count(*) as total'))
                                    ->whereYear('created_at', '>=', $oldestYear)
                                    ->whereRaw($col_to_count)
                                    ->groupBy(DB::raw('YEAR(created_at)'))
                                    ->orderBy(DB::raw('YEAR(created_at)'))
                                    ->get();

                foreach($recordsPerYear as $index => $record) {
                    $datesArr[$index] = $record->year;
                    $totalPerDateArr[$index] = $record->total;
                }
            break;
            case 'months':
                $currentDate = date('Y-m-d');
                $oldestDate = date('Y-m-d', strtotime('-12 months', strtotime($currentDate)));
                $recordsPerMonth = DB::table($table_name)
                                    ->select(DB::raw("DATE_FORMAT(created_at, '%m/%y') as month_year"), DB::raw('count('.$col_to_count.') as total'))
                                    ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
                                    ->groupBy(DB::raw('DATE_FORMAT(created_at, "%m/%y")'))
                                    ->orderBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                                    ->get();

                foreach ($recordsPerMonth as $index => $record) {
                    $datesArr[$index] = $record->month_year;
                    $totalPerDateArr[$index] = $record->total;
                }
            break;
            case 'days':
                $recordsPerDay = DB::table($table_name)
                                ->select(DB::raw("DATE_FORMAT(created_at, '%m/%d') as day_month"), DB::raw('count('.$col_to_count.') as total'))
                                ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
                                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%m/%y/%d")'))
                                ->orderBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d")'))
                                ->get();

                foreach($recordsPerDay as $index => $record) {
                $datesArr[$index] = $record->day_month;
                $totalPerDateArr[$index] = $record->total;
                }
            break;
        }       


        $response = [$datesArr, $totalPerDateArr];

        return response()->json($response);
    }

    public function assignMaterialToPatron()
    {
        //print_r(request()->catalog_id); 
        foreach (request()->catalog_id as $key => $value) {

            $holdings = App\Models\Holding::where('id',$value)->first();
            
            $user = App\Models\User::where('id',request()->userid)->first();

            $borrow = new App\Models\Barrowed_book;
            $borrow->user_id = $user['id'];
            $borrow->fullname = $user['name'];
            $borrow->contactnum = $user['contactnum'];
            $borrow->email = $user['email'];
            $borrow->status = 'Borrowed';
            $borrow->approved_at = date('Y-m-d H:i:s');
            $borrow->approved_by = Auth::user()->name;
            $borrow->catalog_id = $holdings['catalog_id'];
            $borrow->created_at = date('Y-m-d H:i:s');
            // $borrow->consortia_id = $consortia_id;
            $borrow->save();
        }

        return redirect('admin/borrowed');
    }

    public function actionPatron()
    {
        if(request()->action == 'delete')
        {
            App\Models\User::where('id',request()->patron_id)->delete();
        }
        else
        {
            App\Models\User::where('id',request()->patron_id)->update(['password' => bcrypt('pcaarrdelibrary') ]);
        }
        
        return redirect('admin/patron');
    }

    public function registerPatron()
    {
        return view('admin.register-patron');
    }

    public function storePatron(Request $request)
    {
        // $barcode = new DNS1D();

        $libraryCode = generateLibraryCode();

        // $qrCodeImage = $barcode->getBarcodePNG($libraryCode, 'C39+');

        // $qrCodePath = public_path('qrcodes/' . $libraryCode . '.png');
        
        // file_put_contents($qrCodePath, $qrCodeImage);

        $password = generateRandomPassword();

        $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'contactNumber' => ['required', 'string', 'max:255'],
        ]);

        // $mailable = new RegistrationMail(['library_code' => $libraryCode, 'fullname' => $request->fullName, 'password' => $password, 'type' => 'admin']);

        // Register the FailedSendingListener
        // Mail::getSwiftMailer()->registerPlugin(new FailedSendingListener);

        // $emailSent = Mail::to($request->email)->send($mailable);

        // if(is_null($emailSent)) {
        
            $user = User::create([
                'name' => $request->fullName,
                'email' => $request->email,
                'contactnum' => $request->contactNumber,
                'password' => Hash::make($password),
                'library_code' => $libraryCode,
            ]);

            event(new Registered($user));
        // } else {
        //     return redirect()->route('register_patron')->with(['error' => 'Error! Registration failed.']);     
        // }

        return redirect()->route('register_patron')->with([
            'success' => 'Saved! Your account password was sent to your email.',
            'user_library_code' => $libraryCode]);
    }

    public function updatePatronLibraryCode(Request $request)
    {
        $libraryCode = updateLibraryCode($request->id);
        
        return redirect()->route('patrons')->with([
            'success' => 'Updated! You library code was updated!',
            'user_library_code' => $libraryCode]);
    }

        
    public function adminChangeRole(Request $request)
    {
        $role = Role::select('name')->where('id', $request->role_id)->first();

        $user = User::find($request->user_id);

        $currentUserRole = $user->getRoleNames();
        
        if(count($currentUserRole) != 0) {
            $user->removeRole($currentUserRole[0]);
        }


        $user->assignRole($role->name);
  
        // $permissions = $user->getPermissionsViaRoles();
        // $perm = [];
        // foreach($permissions as $index => $permission) {
        //     $perm[$index] = $permission->display_name;
        // }
        // dump($perm);
        // dd($permissions);
        return view('admin.administrators');
    }
}