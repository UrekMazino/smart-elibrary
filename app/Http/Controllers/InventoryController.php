<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holdingcopy;
use App;
use Auth;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function inventory()
    {
        return view('admin.inventory');
    }

    public function getCopy($cirno)
    {
       //CHECK IF EXIST
       $copy =  App\Models\Holdingcopy::where('circulation_number',$cirno)->where('status',1)->first();

       if(isset($copy))
       {
            $presented = App\Models\Holdingscopypresented::where('circulation_number',$cirno)->first();
            if(isset($presented))
            {
                $status = "Circulation number already scanned"; 
            }
            else
            {
                //UPDATE HOLDINGS COPY 
                App\Models\Holdingcopy::where('circulation_number',$cirno)->where('status',1)->update(['presented' => date('Y-m-d H:i:s')]);

                $copypresented = new App\Models\Holdingscopypresented;
                $copypresented->catalog_id = $copy['catalog_id'];
                $copypresented->circulation_number = $cirno;
                $copypresented->save();
                $status = "Success";
            }
       }
       else
       {
            $status = "Circulation number does not exist";
       }

       return json_encode(array("status" => $status));
    }

    public function presented()
    {
        $total = number_format(count(App\Models\Holdingscopypresented::get()));
        return json_encode(array("total" => $total));
    }

    public function reportpresented()
    {
        return view('admin.inventory-report-presented');
    }

    public function reportmissing()
    {
        return view('admin.inventory-report-missing');
    }

    public function inventoryreset()
    {
        // //DELETE PRESENTED
        App\Models\Holdingscopypresented::truncate();

        // //CLEAR HOLDINGS COPY
        App\Models\Holdingcopy::whereNotNull('presented')->update(['presented' => null]);

        return redirect('/admin/inventory');
    }

    public function inventoryremovefromlist()
    {
        // //DELETE PRESENTED
        App\Models\Holdingscopypresented::where('id',request()->presented_id)->delete();

        return redirect('/admin/inventory/reportpresented');
    }

    public function physicalInventoryListJson(Request $request)
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

        $totalRecords = Holdingcopy::count();

        // dd($totalRecords);
        $totalRecordswithFilter = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.presented', 'like', '%' . $searchValue . '%');
        })
        ->count();

        // Fetch records
        $records = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.presented', 'like', '%' . $searchValue . '%');
        })
        ->orderBy('copies.id','desc')
        ->skip(0)
        ->take(10)
        ->get();
       
        $data_arr = array();

        foreach($records as $index => $record){
            $data_arr[] = array(
                "id" =>  $record->id,
                "circulation_number" => $record->circulation_number,
                "status_name" => $record->status_name,
                "source" => $record->source,
                "location" => $record->location,
                "presented" => $record->presented,
            );
        }
        // dd($data_arr);
        $response = array(
            "draw" => intval(1),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );
 
        return response()->json($response);
    }
}
