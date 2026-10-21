<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarctagIndicator;
use App\Models\MarctagsLoc;
use App\Models\MarctagSubfield;
use App\Models\MarcTemplate;
use App\Services\MarcTemplateFields;
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
        // Paging, sorting and search as the table sends them; the sort column only from this list.
        $dt = dataTableRequest($request, ['id' => 'id', 'name' => 'name'], 'name');
        [$draw, $start, $rowPerPage, $columnName, $columnSortOrder, $searchValue] = [$dt->draw, $dt->start, $dt->length, $dt->column, $dt->dir, $dt->search];

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

    public function createMarcTemplate(MarcTemplateFields $templateFields)
    {
        return view('admin.marc-template-form', [
            'template'      => null,
            'initialFields' => $templateFields->toSource(old('field_ids', '[]')),
        ]);
    }

    public function editMarcTemplate(Request $request, MarcTemplateFields $templateFields)
    {
        $template = MarcTemplate::findOrFail($request->template_id);

        return view('admin.marc-template-form', [
            'template'      => $template,
            'initialFields' => $templateFields->toSource(old('field_ids', $template->fields)),
        ]);
    }

    public function storeMarcTemplate(Request $request, MarcTemplateFields $templateFields)
    {
        $fields = $this->validatedTemplateFields($request, $templateFields);

        MarcTemplate::create([
            'name'   => trim($request->name),
            'fields' => json_encode($fields),
        ]);

        return redirect()->route('marc-templates-index')->with(['success' => 'Template created!']);
    }

    public function updateMarcTemplate(Request $request, MarcTemplateFields $templateFields)
    {
        $template = MarcTemplate::findOrFail($request->template_id);
        $fields   = $this->validatedTemplateFields($request, $templateFields, $template->id);

        $template->update([
            'name'   => trim($request->name),
            'fields' => json_encode($fields),
        ]);

        return redirect()->route('marc-templates-index')->with(['success' => 'Template updated!']);
    }

    /** Validate the template form and return its cleaned fields (stored shape). */
    private function validatedTemplateFields(Request $request, MarcTemplateFields $templateFields, $ignoreId = null): array
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('marc_templates', 'name')->ignore($ignoreId)],
            'field_ids' => ['required', 'json'],
        ]);

        $fields = $templateFields->sanitize($request->field_ids);
        if (! $fields) {
            throw \Illuminate\Validation\ValidationException::withMessages(['field_ids' => 'Add at least one MARC tag to the template.']);
        }

        return $fields;
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
        // Paging, sorting and search as the table sends them; the sort column only from this list.
        $dt = dataTableRequest($request, ['id' => 'id', 'code' => 'code', 'description' => 'description'], 'code');
        [$draw, $start, $rowPerPage, $columnName, $columnSortOrder, $searchValue] = [$dt->draw, $dt->start, $dt->length, $dt->column, $dt->dir, $dt->search];

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
        abort_unless($tag, 404);
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

    public function getMarcTemplateDetails(Request $request, MarcTemplateFields $templateFields)
    {
        $template = MarcTemplate::findOrFail($request->template_id);

        return response()->json([
            "template_id"   => $template->id,
            "template_name" => $template->name,
            "fields"        => $templateFields->toSource($template->fields),
        ]);
    }
}
