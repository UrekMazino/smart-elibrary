<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App;
use Auth;
use App\Models\Circulation;
use App\Models\CirculationReservation;

class BorrowController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function index($userid)
    {
        $data = [
            "userid" => $userid
        ];
        return view('admin.borrow-patron')->with('data',$data);
    }

    public function json(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // Total records
        $totalRecordswithFilter =  Circulation::count();

        // Fetch records
        $records = DB::table('circulations')
            ->orderBy('created_at', 'DESC')
            ->leftJoin('circulation_reservations', 'circulation_reservations.reservation_code', '=', 'circulations.reservation_code')
            ->leftJoin('users', 'users.id', '=', 'circulations.user_id')
            ->leftJoin('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
            ->whereIn('status', [1, 2, 3, 4, 5])
            ->where(function ($query) use ($searchValue) {
                $query->where('title_statement', 'like', '%' . $searchValue . '%')
                    ->orWhere('circulations.transaction_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('circulations.created_at', 'like', '%' . $searchValue . '%')
                    ->orWhere('circulation_reservations.reservation_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('circulations.circulation_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('circulation_status.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.name', 'like', '%' . $searchValue . '%');
            })
            ->select('circulation_status.name as status_name', 'circulations.id', 'circulation_reservations.title_statement', 'circulations.catalog_id', 'circulations.user_id', 'circulations.status', 'circulations.reservation_code', 'circulations.circulation_number', 'circulations.transaction_code', 'circulations.created_at', 'users.name as user_name', 'circulations.copy_id')
            ->orderBy($columnName, $columnSortOrder === 'asc' ? 'desc' : 'asc')
            ->skip($start)
            ->take($rowPerPage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            
            $title_statement = $record->title_statement == "" ? getTitleFromHoldingCopy($record->copy_id) : formatTitle1($record->title_statement);


            $data_arr[] = array(
                "id" => $record->id,
                "user_id" => $record->user_id,
                "patron_name" => $record->user_name,
                "title_statement" => $title_statement,
                "status" => $record->status_name,
                'reservation_code' => checkIfFound($record, 'reservation_code', 'Walk-in'), 
                "circulation_number" => $record->circulation_number,
                "catalog_id" => $record->catalog_id,
                "transaction_code" => $record->transaction_code,
                "created_at" => $record->created_at,
            );
         }

         
         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordswithFilter,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
         );
 
         return response()->json($response); 

    }

    public function action()
    {
        switch(request()->borrowed_action)
        {
            case 1:
                $borrowed = App\Models\Barrowed_book::where('id',request()->borrowed_id)
                            ->update([
                               'status' => 'Borrowed',
                               'approved_at' => date('Y-m-d H:i:s'),  
                               'approved_by' => Auth::user()->name     
                            ]);
                //BAWAS SA INVENTORY
                //GET COPY
                $material =  App\Models\Barrowed_book::where('id',request()->borrowed_id)->first();
                $catalog_id = $material['catalog_id'];
                
                $holdings = App\Models\Holding::where('catalog_id',$catalog_id)->first();
                // $upcopies = $holdings['copies'] - 1;
                // //UPDATE
                // $holdings = App\Models\Holding::where('catalog_id',$catalog_id)->update(['copies' => $upcopies]);


                break;
            case 2:
                $borrowed = App\Models\Barrowed_book::where('id',request()->borrowed_id)->delete();
                break;
            case 3:
                $borrowed = App\Models\Barrowed_book::where('id',request()->borrowed_id)
                            ->update([
                               'status' => 'Returned',
                               'returned_at' => date('Y-m-d H:i:s'),  
                               'update_return_by' => Auth::user()->name     
                            ]);
                //BALIK SA INVENTORY
                //GET COPY
                $material =  App\Models\Barrowed_book::where('id',request()->borrowed_id)->first();
                $catalog_id = $material['catalog_id'];
                
                // $holdings = App\Models\Holding::where('catalog_id',$catalog_id)->first();
                // $upcopies = $holdings['copies'] + 1;
                // //UPDATE
                // $holdings = App\Models\Holding::where('catalog_id',$catalog_id)->update(['copies' => $upcopies]);
                break;
        }

        return redirect('admin/borrowed');
    }
}
