<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarctagIndicator;
use App\Models\MarctagsLoc;
use App\Models\MarctagSubfield;
use App\Models\MarcTemplate;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function marcTemplates()
    {
        return view('admin.marc-template');
    }

    public function marcTemplatesJson(Request $request)
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
        $totalRecords = MarcTemplate::count();

        $totalRecordswithFilter = MarcTemplate::select(DB::raw('count(*) as allcount'))->where('name', 'like', '%' . $searchValue . '%')->count();

        // Fetch records
        $records = MarcTemplate::orderBy($columnName, $columnSortOrder)
            ->where('name', 'like', '%' . $searchValue . '%')
            ->skip($start)
            ->take($rowPerPage)
            ->get();
    
        $data_arr = array();

        foreach($records as $index => $record){
           
 
            $data_arr[] = array(
                "id" =>  $record->id,
                "name" => $record->name,
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

    public function createMarcTemplate()
    {
        return view('admin.create-marc-template');
    }

    public function editMarcTemplate(Request $request)
    {
        $template = MarcTemplate::where('id',$request->template_id)->first();

        return view('admin.edit-marc-template', ['template' => $template]);
    }

    public function storeMarcTemplate(Request $request)
    {
        MarcTemplate::create([
            'name' => $request->name,
            'fields' => $request->field_ids,
        ]);

        return redirect()->route('marc-templates-index')->with(['success' => 'Template created!']);
    }

    public function updateMarcTemplate(Request $request)
    {
        MarcTemplate::where('id', $request->template_id)
            ->update([
                        'name' => $request->template_name,
                        'fields' => $request->field_ids,
                    ]);
        return redirect()->route('marc-templates-index')->with(['success' => 'Template updated!']);
    }

    public function destroyMarcTemplate($id) 
    {
        try {
            // Delete the record by its primary key
            MarcTemplate::destroy($id);
            
            // Return a success response
            return response()->json(['success' => true, 'message' => 'Template deleted successfully']);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json(['success' => false, 'message' => 'Error deleting template']);
        }

    }

    public function marcTagsJson(Request $request)
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

        // The create form asks to hide the control fields that can't be added by hand
        // (LDR and 008 are auto-generated and edited in the Fixed Fields panel). Sent only
        // from that page, so the edit page's Add Tag list is unchanged.
        $excludeControl = $request->boolean('exclude_control');

        // Search grouped in a closure so the exclusion below is a real AND, not defeated
        // by the code/description OR.
        $search = function ($q) use ($searchValue) {
            $q->where('code', 'like', '%' . $searchValue . '%')
              ->orWhere('description', 'like', '%' . $searchValue . '%');
        };

        // Total records
        $totalRecords = MarctagsLoc::when($excludeControl, fn ($q) => $q->whereNotIn('code', ['LDR', '008']))->count();

        $totalRecordswithFilter = MarctagsLoc::where($search)
            ->when($excludeControl, fn ($q) => $q->whereNotIn('code', ['LDR', '008']))
            ->count();

        // Fetch records
        $records = MarctagsLoc::where($search)
            ->when($excludeControl, fn ($q) => $q->whereNotIn('code', ['LDR', '008']))
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowPerPage)
            ->get();
    
        $data_arr = array();


        $firstIndArray = array();
        $secondIndArray = array();
        foreach($records as $index => $record){
            $firstDecodedArray = json_decode($record->first_indictors);
            $secondDecodedArray = json_decode($record->second_indicators);
            if (count($firstDecodedArray) > 0){
          
                foreach($firstDecodedArray as $index => $id) {
                    $indicator = getIndDesc($id);
                    $firstIndId = isset($indicator->id) ? $indicator->id : null;
                    $firstIndicator = isset($indicator->indicator) ? $indicator->indicator : null;
                    $firstIndArray[$index] = ['id' => $firstIndId, 'code' => $firstIndicator];
                }
            }

            if (count($secondDecodedArray) > 0){
          
                foreach($secondDecodedArray as $index => $id) {
                    $indicator = getIndDesc($id);
                    $secondIndId = isset($indicator->id) ? $indicator->id : null;
                    $secondIndicator = isset($indicator->indicator) ? $indicator->indicator : null;
                    $secondIndArray[$index] = ['id' => $secondIndId, 'code' => $secondIndicator];
                }
            }
 
            $data_arr[] = array(
                "id" => $record->id,
                "code" => $record->code,
                "description" => $record->description,
                "first_indictors" => json_encode($firstIndArray),
                "second_indicators" => json_encode($secondIndArray),

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

    public function getMarctagDetails(Request $request)
    {

        $tag = MarctagsLoc::select(DB::raw('*'))->where('id', $request->marctag_id)->first();
        $subfields = MarctagSubfield::where('marctags_loc_id', $tag->id)->get();
        $firstIndArray = json_decode($tag->first_indictors);
        $secondIndArray = json_decode($tag->second_indicators);
        $subfieldValues = [];
        
        if(count($subfields) > 0) {
            foreach($subfields as $sf) {
                $subfieldValues[] = array(
                    "id" => $sf->id,
                    "code" => $sf->code,
                    "description" => $sf->description,
                    "marctags_loc_id" => $sf->marctags_loc_id,
                );
            }
        
        }

        if(count($firstIndArray) > 0) {  
            $indicators = MarctagIndicator::whereIn('id', $firstIndArray)->get();
            foreach($indicators as $ind) {
                $firstIndValues[] = array(
                    "id" => $ind->id,
                    "indicator" => $ind->indicator,
                );
            }
        } else {
            $firstIndValues[] = array();
        }

        if(count($secondIndArray) > 0) {
            $indicators = MarctagIndicator::whereIn('id', $secondIndArray)->get();
            
            foreach($indicators as $ind) {
                $secondIndValues[] = array(
                    "id" => $ind->id,
                    "indicator" => $ind->indicator,
                );
            }
        } else {
            $secondIndValues[] = array();
        }

        $response = array(
            "id" => $tag->id,
            "code" => $tag->code,
            "description" => $tag->description,
            "subfields" => $subfieldValues,
            "first_indictors" => $firstIndValues,
            "second_indicators" => $secondIndValues,
        );
        return response()->json($response); 
    }

    public function getMarcTemplateDetails(Request $request)
    {
        $template = MarcTemplate::select(DB::raw('*'))->where('id', $request->template_id)->first();
        
        $fieldArray = json_decode($template->fields);
        
        $detailsArr = array();
        
        $indicatorArr = array();
        
        foreach($fieldArray as $index => $tag) {
            
            $tagDetail = getTagDetails($tag->id);

            $subfieldArr = array();
            if(count($tag->subfield) > 0) {
                foreach($tag->subfield as $i => $id)
                {
                    $subFieldDetails = getSubfieldDetails($id);

                    $subfieldArr[$i] = ['id' => is_null($subFieldDetails) ? 1 : $subFieldDetails->id, 'code' => is_null($subFieldDetails) ? '001-NS' : $subFieldDetails->code, 'description' => is_null($subFieldDetails) ? 'No Subfield' : $subFieldDetails->description, 'default' => count($tag->default) > 0 ? $tag->default[$i] : "Enter value" ];
                }
            } 
            
            $ind1_id = is_null(getIndicatorDetails($tag->indicator[0])) ? 0 : getIndicatorDetails($tag->indicator[0])->id;
            $ind2_id = is_null(getIndicatorDetails($tag->indicator[1])) ? 0 : getIndicatorDetails($tag->indicator[1])->id;
            $ind1_text = is_null(getIndicatorDetails($tag->indicator[0])) ? 'None' : getIndicatorDetails($tag->indicator[0])->indicator;
            $ind2_text = is_null(getIndicatorDetails($tag->indicator[1])) ? 'None' : getIndicatorDetails($tag->indicator[1])->indicator;
            $indicatorArr[0] = ['id' => $ind1_id, 'indicator' => $ind1_text];
            $indicatorArr[1] = ['id' => $ind2_id, 'indicator' => $ind2_text];

            $detailsArr[$index] = 
                [
                    'tag' => ['id' => $tagDetail->id, 'code' => $tagDetail->code, 'description' => $tagDetail->description, 'isRequired' => $tag->required], 
                    'subfields' =>  $subfieldArr,
                    'indicators' => $indicatorArr,
                ];
        }
        $response = array(
            "template_id" => $template->id,
            "template_name" => $template->name,
            "fields" => $detailsArr,
        );
        return response()->json($response); 
    }
}
