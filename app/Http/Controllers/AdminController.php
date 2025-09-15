<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
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

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'contactnum' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'string', 'max:100']
        ]);
        
        $userId = User::where('id',Auth::user()->id)
            ->update([
                'name' => $request->name,
                'contactnum' => $request->contactnum,
                'affiliation' => $request->affiliation,
                'sex' => $request->sex,
                'birthdate' => $request->birthdate,
                'patrontypes_id' => $request->patron_type_id,
                'region_id' => $request->region_id,
                'email' => $request->email,
                'location' => $request->address
            ]);

        $user = User::where('id', $userId)->first();

        return view('admin.profile', ['user' => $user, 'success' => 'Profile Updated!']);
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

     
        try {
         
            // Validate the request data
            $request->validate([
                'current_password' => 'required',
                'password' => 'min:6|required_with:password_confirmation|same:password_confirmation',
                'password_confirmation' => 'min:6'
            ]);
         
             // Check if the entered current password matches the user's current password
            if (!Hash::check($request->current_password, $user->password)) {
                // Not match
                $userUpdated = User::where('id', $user->id)->first();
                return view('admin.profile', ['user' => $userUpdated, 'error' => 'The current password is incorrect!']);
            }

            // If the current password is correct and the new password and confirm password match, update the password
            $user->password = Hash::make($request->input('password'));
            $user->save();
            // dd('yow');
        } catch (Exception $e) {
            return view('admin.profile', ['user' => $userUpdated, 'error' => $e->getMessage()]);
        }

        $userUpdated = User::where('id', $user->id)->first();
        return view('admin.profile', ['user' => $userUpdated, 'success' => 'Password was changed!']);
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
            ]);
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
        $list = App\Models\View_inquiry::get();
        return view('admin.inquiry',['list' => $list]);
    }

    public function inquiryjson()
    {
        $list = App\Models\View_inquiry::get();
        return $list;
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

    public function reportprint()
    {
        $ratings = [1 => "Poor", 2 => "Unsatisfactory", 3 => "Satisfactory", 4 => "Very Satisfactory", 5 => "Outstanding"];
        //CHECK REPORT TYPE 
        switch (request()->typeReport) {
            case 1:
                    $table = 1;
                    if(request()->selectMaterial === ["0"])
                    {
                        $material = false;
                    }
                    else
                    {
                        $material = true;
                        $arrMaterial = request()->selectMaterial;
                    }

                    if(request()->selectSource === ["0"])
                    {
                        $source = false;
                    }
                    else
                    {
                        $source = true;
                        $arrSource = request()->selectSource;
                    }
                    

                    if($material && $source)
                    {
                        if(request()->keyword == '')
                            $list = App\Models\ViewCatalog::whereIn('material_type_id',$arrMaterial)->whereIn('consortia',$arrSource)->get();
                        else
                            $list = App\Models\ViewCatalog::search(request()->keyword)->whereIn('material_type_id',$arrMaterial)->whereIn('consortia',$arrSource)->get();
                    }
                    elseif(!$material && $source)
                    {
                        if(request()->keyword == '')
                            $list = App\Models\ViewCatalog::whereIn('consortia',$arrSource)->get();
                        else
                            $list = App\Models\ViewCatalog::search(request()->keyword)->whereIn('consortia',$arrSource)->get();
                    }
                    elseif($material && !$source)
                    {
                        if(request()->keyword == '')
                        $list = App\Models\ViewCatalog::whereIn('material_type_id',$arrMaterial)->get();
                        else
                            $list = App\Models\ViewCatalog::search(request()->keyword)->whereIn('material_type_id',$arrMaterial)->get();
                    }
                    elseif(!$material && !$source)
                    {
                        $list = App\Models\ViewCatalog::search(request()->keyword)->get();
                    }
                    
            break;

            case 2:
                $table = 2;
                switch (request()->select_type) {
                    case 1:
                        $list = App\Models\User::whereDate('created_at',date("Y-m-d",strtotime(request()->filter_date_dt)))->get();
                    break;
                    case 2:
                        $list = App\Models\User::whereYear('created_at',request()->filter_month_year)->whereMonth('created_at',request()->filter_month_mon)->get();
                    break;
                    case 3:
                        $list = App\Models\User::whereYear('created_at',request()->filter_year_yr)->get();
                    break;
                    case 4:
                        $from = date("Y-m-d",strtotime(request()->start));
                        $end = date("Y-m-d",strtotime(request()->end));

                        $list = App\Models\User::whereBetween('created_at', [$from, $end])->get();
                    break;
                }
            break;

            case 4:
                $table = 3;
                switch (request()->select_type) {
                    case 1:
                        $list = App\Models\View_inquiry::whereDate('created_at',date("Y-m-d",strtotime(request()->filter_date_dt)))->get();
                    break;
                    case 2:
                        $list = App\Models\View_inquiry::whereYear('created_at',request()->filter_month_year)->whereMonth('created_at',request()->filter_month_mon)->get();
                    break;
                    case 3:
                        $list = App\Models\View_inquiry::whereYear('created_at',request()->filter_year_yr)->get();
                    break;
                    case 4:
                        $from = date("Y-m-d",strtotime(request()->start));
                        $end = date("Y-m-d",strtotime(request()->end));

                        $list = App\Models\View_inquiry::whereBetween('created_at', [$from, $end])->get();
                    break;
                }
            break;

            case 5:
                $table = 5;
                switch (request()->select_type) {
                    case 1:
                        $list = Csf::whereDate('created_at',date("Y-m-d",strtotime(request()->filter_date_dt)))->get();
                    break;
                    case 2:
                        $list = Csf::whereYear('created_at',request()->filter_month_year)->whereMonth('created_at',request()->filter_month_mon)->get();
                    break;
                    case 3:
                        $list = Csf::whereYear('created_at',request()->filter_year_yr)->get();
                    break;
                    case 4:
                        $from = date("Y-m-d",strtotime(request()->start));
                        $end = date("Y-m-d",strtotime(request()->end));

                        $list = Csf::whereBetween('created_at', [$from, $end])->get();
                    break;
                }
            break;
            case 6:
                $table = 6;
                switch (request()->select_type) {
                    case 1:
                        $list = Download::whereDate('created_at',date("Y-m-d",strtotime(request()->filter_date_dt)))->get();
                    break;
                    case 2:
                        $list = Download::whereYear('created_at',request()->filter_month_year)->whereMonth('created_at',request()->filter_month_mon)->get();
                    break;
                    case 3:
                        $list = Download::whereYear('created_at',request()->filter_year_yr)->get();
                    break;
                    case 4:
                        $from = date("Y-m-d",strtotime(request()->start));
                        $end = date("Y-m-d",strtotime(request()->end));

                        $list = Download::whereBetween('created_at', [$from, $end])->get();
                    break;
                }
            break;
            case 7:
                $table = 7;
                switch (request()->select_type) {
                    case 1:
                        $list = Csf::whereDate('created_at',date("Y-m-d",strtotime(request()->filter_date_dt)))->get();
                    break;
                    case 2:
                        $list = Csf::whereYear('created_at',request()->filter_month_year)->whereMonth('created_at',request()->filter_month_mon)->get();
                    break;
                    case 3:
                        $list = Csf::whereYear('created_at',request()->filter_year_yr)->get();
                    break;
                    case 4:
                        $from = date("Y-m-d",strtotime(request()->start));
                        $end = date("Y-m-d",strtotime(request()->end));

                        $list = Csf::whereBetween('created_at', [$from, $end])->get();
                    break;
                }
            break;
        }

        return view('admin.print-report',['table' => $table,'list' => $list, 'ratings' => $ratings]);
    }

    public function patronjson(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // Total records
        $totalRecords = App\Models\User::where('usertype','Patron')->count();
        $totalRecordswithFilter = App\Models\User::select('count(*) as allcount')->where('usertype','Patron')->where('name', 'like', '%' .$searchValue . '%')->count();

        // Fetch records
        $records = App\Models\User::orderBy($columnName,$columnSortOrder)
            ->select('id','username','name','email','contactnum','location','created_at')
            ->where(function ($query) use ($searchValue) {
                $query->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('contactnum', 'like', '%' . $searchValue . '%')
                    ->orWhere('library_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('location', 'like', '%' . $searchValue . '%')
                    ->orWhere('created_at', 'like', '%' . $searchValue . '%');
            })
            ->where('usertype','Patron')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;
            $username = $record->username;
            $name = $record->name;
            $email = $record->email;
            $contactnum = $record->contactnum;
            $location = $record->location;
            $created_at = date('Y-m-d H:i:s',strtotime($record->created_at));
            
 
            $data_arr[] = array(
                "id" => $id,
                "username" => $username,
                "name" => $name,
                "email" => $email,
                "contactnum" => $contactnum,
                "location" => $location,
                "created_at" => $created_at,
            );
         }

         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
         );
 
         return response()->json($response); 

    }
    
    public function adminJson(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // Total records
        // $totalRecords = App\Models\User::whereIn('usertype', ['Administrator', 'Developer'])->count();
        $totalRecords = App\Models\User::where('usertype','Administrator')->count();
        $totalRecordswithFilter = App\Models\User::select('count(*) as allcount')->where('usertype','Administrator')->where('name', 'like', '%' .$searchValue . '%')->count();

        // Fetch records
        $records = App\Models\User::orderBy($columnName,$columnSortOrder)
            ->select('id','username','name','email','contactnum','location','created_at')
            ->where(function ($query) use ($searchValue) {
                $query->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('contactnum', 'like', '%' . $searchValue . '%')
                    ->orWhere('library_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('location', 'like', '%' . $searchValue . '%');
            })
            ->where('usertype','Administrator')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;
            $username = $record->username;
            $name = $record->name;
            $email = $record->email;
            $contactnum = $record->contactnum;
            $location = $record->location;
            $created_at = date('Y-m-d H:i:s',strtotime($record->created_at));


            $user = User::find($record->id);

            // Get roles associated with the user
            $roles = $user->getRoleNames();

            $permissions = $user->getPermissionsViaRoles();
            $perm = [];
            foreach($permissions as $index => $permission) {
                $perm[$index] = $permission->display_name;
            }
            // Sort the $perm array based on display_name
            usort($perm, function ($a, $b) {
                return strcmp($a, $b);
            });
            
            $data_arr[] = array(
                "id" => $id,
                "username" => $username,
                "name" => $name,
                "email" => $email,
                "contactnum" => $contactnum,
                "permissions" => $perm,
                "roles" => $roles,
                "location" => $location,
                "created_at" => $created_at,
            );
         }

         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
         );
 
         return response()->json($response); 

    }

    public function createAdministrator(Request $request)
    {
        // dd("CREATE ADMIN");
        $roles = Role::all();

        // dd($roles);
        
        return view('admin.create-admin', ['roles' => $roles]);
        
    }

    public function storeAdministrator(Request $request)
    {
        // $barcode = new DNS1D();

        $libraryCode = generateLibraryCode();

        // $qrCodeImage = $barcode->getBarcodePNG($libraryCode, 'C39+');

        // $qrCodePath = public_path('qrcodes/' . $libraryCode . '.png');
        
        // file_put_contents($qrCodePath, $qrCodeImage);

        // $password = generateRandomPassword();

        $password = 'admin';

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
                'username' => $request->userName,
                'name' => $request->fullName,
                'email' => $request->email,
                'contactnum' => $request->contactNumber,
                'password' => Hash::make($password),
                'library_code' => $libraryCode,
                'usertype' => 'Administrator'
            ]);
            
            $user->assignRole($request->role);

            event(new Registered($user));
        // } else {
        //     return redirect()->route('admin.create')->with(['error' => 'Error! Registration failed.']);     
        // }

        return redirect()->route('administrators')->with([
            'success' => 'Saved! Your account password was sent to your email.',
            'user_library_code' => $libraryCode]);
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