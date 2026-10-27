<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\ViewCatalog;
use App\Services\BrowserSessions;
use App\Services\DashboardService;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        return view('admin.profile', ['user' => $user, 'browserSessions' => BrowserSessions::of($request)]);
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
            'birthdate'      => ['required', 'date', 'before:today'],   // 'Y-m-d' (OPAC) or 'm/d/Y' (admin picker)
            'affiliation'    => ['nullable', 'string', 'max:255'],
            'sex'            => ['nullable', Rule::in(['Male', 'Female'])],   // column is an enum
            'patron_type_id' => ['required', 'exists:patrontypes,id'],        // NOT NULL column
            'region_id'      => ['nullable', 'integer', 'exists:regions,id'],
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
            'birthdate'      => \Carbon\Carbon::parse($data['birthdate'])->toDateString(),
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
        return view('admin.index', DashboardService::overview(Auth::user()) + ['ranges' => DashboardService::RANGES]);
    }

    /** Date-range-scoped dashboard data (KPIs, trend, top materials) — see DashboardService::activity(). */
    public function dashboardActivity(Request $request)
    {
        $data = $request->validate([
            'range' => ['nullable', Rule::in(array_merge(array_keys(DashboardService::RANGES), ['custom']))],
            'from'  => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d'],
            'to'    => ['nullable', 'required_if:range,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return response()->json(DashboardService::activity($data['range'] ?? '30d', $data['from'] ?? null, $data['to'] ?? null));
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
        // Inquiries from patrons (about a material, or General from the chat button) and General ones
        // from guests (no account; answered by email). Each patron's ban/block state feeds the controls.
        $list = App\Models\View_inquiry::get();

        // Reservation conversations a patron wrote in (the Messages chat on their Transactions page,
        // answered on the admin Transactions page too): one row per reservation, read and answered
        // here in the same thread.
        $threads = DB::table('reservation_messages as m')
            ->join('circulation_reservations as r', 'r.reservation_code', '=', 'm.reservation_code')
            ->leftJoin('circulations as c', 'c.reservation_code', '=', 'm.reservation_code')
            ->leftJoin('circulation_status as s', 's.order_no', '=', 'c.status')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->groupBy('m.reservation_code', 'r.user_id', 'r.visit_date', 'r.visit_time', 'c.catalog_id', 'c.status', 's.name', 'u.name', 'u.contactnum', 'u.email')
            ->havingRaw("SUM(m.sender_role = 'patron') > 0")
            ->get(['m.reservation_code', 'r.user_id', 'r.visit_date', 'r.visit_time', 'c.catalog_id', 'c.status', 's.name as status_name',
                   'u.name as fullname', 'u.contactnum', 'u.email',
                   DB::raw("MIN(CASE WHEN m.sender_role = 'patron' THEN m.created_at END) as created_at"),
                   DB::raw("SUM(m.sender_role = 'patron' AND m.seen_at IS NULL) as unread")]);

        $userFlags = App\Models\User::whereIn('id', $list->pluck('user_id')->merge($threads->pluck('user_id'))->filter()->unique()->values())
                        ->get(['id', 'is_banned', 'is_blocked', 'patrontypes_id'])->keyBy('id');
        // The User type column: the sender's patron type (Student, Professional, ...), or Guest without an account.
        $patronTypes = DB::table('patrontypes')->pluck('types', 'id');

        // Inquiries the current admin hasn't opened since the latest patron message
        // (drives the red "unseen" dot on each Reply button). array_flip -> O(1) lookup.
        $unread = array_flip(App\Services\NotificationService::unreadInquiryIds(Auth::user()));

        return view('admin.inquiry', ['list' => $list, 'threads' => $threads, 'userFlags' => $userFlags, 'patronTypes' => $patronTypes, 'unread' => $unread]);
    }

    // Toggle a patron's ban/block flag (used by Patrons, Inquiry and Reservation pages).
    //   flag=banned  -> cannot log in AND cannot use the Read feature
    //   flag=blocked -> cannot use chat / inquiry
    public function toggleUserFlag(Request $request)
    {
        $user = App\Models\User::find($request->user_id);
        if (! $user) return response()->json(['ok' => false], 404);

        $col = $request->flag === 'banned' ? 'is_banned' : 'is_blocked';

        $declined = DB::transaction(function () use ($request, $user, $col) {
            $user->$col = $user->$col ? 0 : 1;
            $user->save();

            // Banning a patron auto-declines their outstanding reservation tickets
            // (For Approval / Approved) and puts the copies held for approved ones back on the
            // shelf. Checked-out (Open) loans are left untouched.
            if ($request->flag !== 'banned' || ! $user->is_banned) return 0;

            $tickets = DB::table('circulations')->where('user_id', $user->id)->whereIn('status', [1, 2])
                ->lockForUpdate()->get(['id', 'status', 'circulation_number']);
            releaseReservedCopies($tickets->where('status', 2)->pluck('circulation_number'));

            return DB::table('circulations')->whereIn('id', $tickets->pluck('id'))->update([
                'status'      => 4,
                'declined_at' => now(),
                'declined_by' => auth()->id(),
            ]);
        });

        // A ban signs the account out everywhere (ASVS 7.4.2): EndBannedSessions refuses its next request from any
        // device and shows the sign-in page's banned popup, so its sessions are left for that rather than deleted.
        // A new remember token ends "Keep me signed in" for good, also after the ban is lifted.
        if ($request->flag === 'banned' && $user->is_banned) {
            Auth::getProvider()->updateRememberToken($user, Str::random(60));
        }

        return response()->json(['ok' => true, 'flag' => $request->flag, 'value' => (bool) $user->$col, 'declined' => $declined]);
    }

    /**
     * Administration → Accounts: a card for each account page this admin may use. Patrons needs
     * access.accounts; the staff pages are for super_admin and developer, as their routes are.
     */
    public function accounts()
    {
        $staffAdmin = auth()->user()->hasAnyRole(['super_admin', 'developer']);
        $patrons    = fn () => User::where('usertype', 'Patron');
        $cards      = [];

        if ($staffAdmin) {
            $cards[] = [
                'title' => 'Administrators',
                'icon'  => 'shield',
                'url'   => route('administrators'),
                'text'  => 'Staff accounts and their roles: add an administrator, change a role, reset a password or library code.',
                'meta'  => User::where('usertype', 'Administrator')->count() . ' staff accounts',
            ];
        }
        if (auth()->user()->can('access.accounts')) {
            $cards[] = [
                'title' => 'Patrons',
                'icon'  => 'users',
                'url'   => route('patrons'),
                'text'  => 'Registered library users: search, filter, block or ban, reset a password or library code, register a new patron.',
                'meta'  => $patrons()->count() . ' patrons · ' . $patrons()->where('is_blocked', 1)->count() . ' blocked · '
                         . $patrons()->where('is_banned', 1)->count() . ' banned',
            ];
        }
        if ($staffAdmin) {
            $cards[] = [
                'title' => 'Roles',
                'icon'  => 'user-check',
                'url'   => route('admin.list_roles'),
                'text'  => 'Staff roles and their access: tick or untick each permission of a role. Changes save instantly.',
                'meta'  => Role::count() . ' roles',
            ];
            $cards[] = [
                'title' => 'Permissions',
                'icon'  => 'key',
                'url'   => route('admin.list_permissions'),
                'text'  => 'The permissions roles are built from, grouped by module, with their display names.',
                'meta'  => Permission::count() . ' permissions',
            ];
        }
        if (auth()->user()->can('access.sessions')) {
            $signedIn = BrowserSessions::everyone(request());
            $cards[] = [
                'title' => 'Signed in now',
                'icon'  => 'activity',
                'url'   => route('admin.sessions'),
                'text'  => 'Who is signed in right now, and on which browsers. Sign one person out everywhere, or everyone.',
                'meta'  => $signedIn->where('staff', true)->count() . ' staff · '
                         . ($patronsIn = $signedIn->where('staff', false)->count()) . ' ' . Str::plural('patron', $patronsIn) . ' signed in',
            ];
        }

        return view('admin.hub', [
            'title' => 'Accounts',
            'intro' => 'Staff and patron accounts, and the roles and permissions that decide what staff can do. You see the ones your role may use.',
            'cards' => $cards,
        ]);
    }

    public function patron()
    {
        return view('admin.patron');
    }

    public function administrators()
    {
        // Change Role offers only the roles this admin may hand out (developer: developers only).
        return view('admin.administrators', ['assignableRoles' => $this->assignableRoles()]);
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
        $dt         = dataTableRequest($request);   // paging, sort and search, with defaults when the table sent none
        $draw       = $dt->draw;
        $start      = $dt->start;
        $rowperpage = $dt->length;

        $columnName_arr = $request->get('columns');
        $order_arr      = $request->get('order');
        $search_arr     = $request->get('search');

        $columnName      = $this->userSortColumn($dt->data ?? 'name');
        $columnSortOrder = $dt->dir;
        $searchValue     = $dt->search;

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
            // Only what the Patrons table shows (OWASP ASVS 15.3.1).
            ->select('id', 'name', 'email', 'contactnum', 'library_code', 'location', 'created_at', 'is_banned', 'is_blocked')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $record) {
            $data_arr[] = array(
                "id"           => $record->id,
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
        $dt         = dataTableRequest($request);   // paging, sort and search, with defaults when the table sent none
        $draw       = $dt->draw;
        $start      = $dt->start;
        $rowperpage = $dt->length;

        $columnName_arr = $request->get('columns');
        $order_arr      = $request->get('order');
        $search_arr     = $request->get('search');

        $columnName      = $this->userSortColumn($dt->data ?? 'name');
        $columnSortOrder = $dt->dir;
        $searchValue     = $dt->search;

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
            ->select('id', 'name', 'email', 'contactnum', 'location', 'created_at')   // what the table shows (ASVS 15.3.1)
            ->with('roles.permissions')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        $meIsDeveloper = auth()->user()->hasRole('developer');

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
                "name"        => $record->name,
                "email"       => $record->email,
                "contactnum"  => $record->contactnum,
                "permissions" => $perm,
                "roles"       => $record->roles->pluck('name')->all(),
                "role_ids"    => $record->roles->pluck('id')->all(),
                // Drives the Action menu: your own account and (for non-developers) developer
                // accounts can't be changed here — the endpoints refuse them too.
                "is_self"     => $record->id === auth()->id(),
                "manageable"  => $record->id !== auth()->id()
                                 && ($meIsDeveloper || ! $record->roles->contains('name', 'developer')),
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

    /*
     * Patron account actions (Patrons page). Each route carries its own permission
     * (edit.patron / delete.patron); these only ever touch PATRON accounts — staff accounts
     * go through the Administrators page, which has its own guards.
     */
    private function patronOrFail($id): User
    {
        return User::where('usertype', 'Patron')->findOrFail($id);
    }

    /** Set a one-time random password and hand it back once, for the librarian to pass on. */
    public function resetPatronPassword($id)
    {
        $patron = $this->patronOrFail($id);

        return response()->json($this->resetPasswordOf($patron));
    }

    public function regeneratePatronLibraryCode($id)
    {
        $patron = $this->patronOrFail($id);

        return response()->json($this->regenerateLibraryCodeOf($patron));
    }

    public function destroyPatron($id)
    {
        $patron = $this->patronOrFail($id);
        $patron->delete();   // soft delete (User uses SoftDeletes) — recoverable

        return response()->json(['message' => $patron->name . "'s account was deleted."]);
    }

    private function resetPasswordOf(User $user): array
    {
        $password = generateRandomPassword();
        $user->forceFill(['password' => Hash::make($password)])->save();

        return [
            'message'  => 'Password reset for ' . $user->name . '.',
            'password' => $password,
        ];
    }

    private function regenerateLibraryCodeOf(User $user): array
    {
        $user->forceFill(['library_code' => generateLibraryCode()])->save();

        return [
            'message'      => 'New library code for ' . $user->name . '.',
            'library_code' => $user->library_code,
        ];
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

        
    /*
     * Administrator account actions (Administrators page; routes are super_admin/developer only).
     * Guards: never your own account (use Profile — and no locking yourself out), a developer
     * account only by a developer, and never the last super_admin.
     */
    private function manageableAdminOrFail($id): User
    {
        $user = User::whereIn('usertype', ['Administrator', 'Developer'])->findOrFail($id);

        abort_if($user->id === auth()->id(), 403, "You can't change your own account here. Use your Profile instead.");
        abort_if($user->hasRole('developer') && ! auth()->user()->hasRole('developer'), 403, 'Only a developer can manage a developer account.');

        return $user;
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin')
            && User::role('super_admin')->whereKeyNot($user->id)->count() === 0;
    }

    public function adminChangeRole(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ], ['role_id.*' => 'Select a role.']);

        $user = $this->manageableAdminOrFail($data['user_id']);
        $role = $this->assignableRoles()->firstWhere('id', (int) $data['role_id']);
        abort_unless($role, 403, "You can't assign that role.");

        if ($role->name !== 'super_admin' && $this->isLastSuperAdmin($user)) {
            return back()->with('error', $user->name . ' is the only Super Administrator. Make someone else a Super Administrator first.');
        }

        $user->syncRoles([$role->name]);   // one role per administrator

        return redirect()->route('administrators')
            ->with('success', $user->name . ' is now ' . ($role->display_name ?: $role->name) . '.');
    }

    public function resetAdminPassword($id)
    {
        return response()->json($this->resetPasswordOf($this->manageableAdminOrFail($id)));
    }

    public function regenerateAdminLibraryCode($id)
    {
        return response()->json($this->regenerateLibraryCodeOf($this->manageableAdminOrFail($id)));
    }

    public function destroyAdmin($id)
    {
        $user = $this->manageableAdminOrFail($id);

        if ($this->isLastSuperAdmin($user)) {
            return response()->json(['message' => $user->name . ' is the only Super Administrator and can\'t be deleted.'], 422);
        }

        $user->delete();   // soft delete

        return response()->json(['message' => $user->name . "'s administrator account was deleted."]);
    }
}
