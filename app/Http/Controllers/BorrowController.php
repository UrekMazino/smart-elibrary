<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Circulation;
use App\Models\CirculationReservation;

class BorrowController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function json(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = dataTableLength($request); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column'] ?? 5; // Column index
        $columnName = $columnName_arr[$columnIndex]['data'] ?? 'created_at'; // Column name
        $columnSortOrder = strtolower($order_arr[0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $searchValue = $search_arr['value'] ?? ''; // Search value

        // DataTables column -> SQL column. Only these can be sorted on (the table's own data
        // names like "patron_name" aren't DB columns, and request input never reaches SQL raw).
        $sortable = [
            'title_statement'  => 'circulation_reservations.title_statement',
            'patron_name'      => 'users.name',
            'transaction_code' => 'circulations.transaction_code',
            'reservation_code' => 'circulations.reservation_code',
            'status'           => 'circulation_status.name',
            'created_at'       => 'circulations.created_at',
        ];
        $sortColumn = $sortable[$columnName] ?? 'circulations.created_at';

        $base = fn () => DB::table('circulations')
            ->leftJoin('circulation_reservations', 'circulation_reservations.reservation_code', '=', 'circulations.reservation_code')
            ->leftJoin('users', 'users.id', '=', 'circulations.user_id')
            ->leftJoin('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
            ->whereIn('circulations.status', [1, 2, 3, 4, 5]);

        $search = function ($query) use ($searchValue) {
            $query->where('circulation_reservations.title_statement', 'like', '%' . $searchValue . '%')
                ->orWhere('circulations.transaction_code', 'like', '%' . $searchValue . '%')
                ->orWhere('circulations.created_at', 'like', '%' . $searchValue . '%')
                ->orWhere('circulations.reservation_code', 'like', '%' . $searchValue . '%')
                ->orWhere('circulations.circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('circulation_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('users.name', 'like', '%' . $searchValue . '%');
        };

        // Totals: all transactions vs. the ones matching the search (DataTables paging needs both).
        $totalRecords = $base()->count();
        $totalRecordswithFilter = $searchValue === '' ? $totalRecords : $base()->where($search)->count();

        // Fetch records: the clicked column in the clicked direction, newest first as tie-break.
        $records = $base()
            ->when($searchValue !== '', fn ($q) => $q->where($search))
            ->select('circulation_status.name as status_name', 'circulations.id', 'circulation_reservations.title_statement', 'circulations.catalog_id', 'circulations.user_id', 'circulations.status', 'circulations.reservation_code', 'circulations.circulation_number', 'circulations.transaction_code', 'circulations.created_at', 'users.name as user_name', 'circulations.copy_id')
            ->orderBy($sortColumn, $columnSortOrder)
            ->orderBy('circulations.created_at', 'desc')
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
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
         );
 
         return response()->json($response); 

    }

}
