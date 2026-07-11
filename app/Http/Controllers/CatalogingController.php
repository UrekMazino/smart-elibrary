<?php

namespace App\Http\Controllers;
use Auth;
use App\Http\Requests\BatchUploadRequest;
use App\Http\Requests\MarcFileUploadRequest;
use App\Models\Catalog;
use App\Models\CatalogImport;
use App\Models\Download;
// use App\Models\CatalogImportProgress;
use App\Models\Frontpage;
use App\Models\Holdingcopy;
use App\Models\MarctagIndicator;
use App\Models\MarctagsLoc;
use App\Models\MarctagSubfield;
use App\Models\MarcTemplate;
use App\Models\Material;
use App\Models\Multimedia;
use App\Models\ViewCatalog;
use Illuminate\Support\Facades\DB;
use Scriptotek\Marc\Collection;
use Scriptotek\Marc\Record;
use ReflectionClass; 

use Illuminate\Http\Request;

class CatalogingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function createRecord()
    { 
        $marcTemplates = MarcTemplate::get();

        $materials = Material::get();

        return view('admin.create-catalog-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials]);
    }

    public function viewRecord(Request $request)
    {
     
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 

        $totalCopies = Holdingcopy::where('catalog_id', $request->catalog_id)->count();

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
     
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

       return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'totalCopies' => $totalCopies]);
    }

    public function viewBatch(Request $request)
    {
        $batch = CatalogImport::select('*', 'catalog_imports.id as batch_id')
        ->join('materials', 'materials.id', '=', 'catalog_imports.material_type_id')
        ->where('catalog_imports.id', $request->batch_id)
        ->where('catalog_imports.deleted_at', null)
        ->first();

       return view('admin.batch-view', ['batch' => $batch]);
    }

    public function viewBatchRecords($id)
    {
        return view('admin.batch-records', ['id' => $id]);
    }

    public function viewBatchRecordsJson(Request $request)
    {
        $parts = explode('/', $request->getPathInfo());
        $batchId = end($parts);
   
        $draw = isset($request->search_title) ? 1 : $request->get('draw');
        $start =  isset($request->search_title) ? 0 : $request->get("start");
        $rowperpage =  isset($request->search_title) ? PHP_INT_MAX : $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        if(isset($request->search_title)) {
            $columnIndex = 0;
            $columnName = 'id';
            $columnSortOrder = 'desc';
            $searchValue =  $request->search_title;
            $searchTitle = $request->search_title;
        } else {
            $columnIndex = is_null($columnIndex_arr) ? 0 : $columnIndex_arr[0]['column']; // Column index
            $columnName = is_null($columnName_arr) ? 'id' : $columnName_arr[$columnIndex]['data']; // Column name
            $columnName = $columnName == "function" ? "id" : $columnName;
            $columnSortOrder = is_null($order_arr) ? 'desc' : $order_arr[0]['dir']; // asc or desc
            $searchValue = is_null($search_arr['value']) ? '' : $search_arr['value']; // Search value
            $searchTitle = '';
        }
       
        // Total records
        $totalRecords = ViewCatalog::where('deleted_at', null)->where('catalog_import_id', $batchId)->count();
        $totalRecordswithFilter = ViewCatalog::select('count(*) as allcount')
            ->where(function ($query) use ($searchValue) {
                $query->where('material_type', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_id', 'like', '%' . $searchValue . '%')
                    ->orWhere('accession_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('title_statement', 'like', '%' . $searchValue . '%')
                    ->orWhere('isbn', 'like', '%' . $searchValue . '%')
                    ->orWhere('issn', 'like', '%' . $searchValue . '%')
                    ->orWhere('pub_year', 'like', '%' . $searchValue . '%')
                    ->orWhere('publisher', 'like', '%' . $searchValue . '%')
                    ->orWhere('joint_authors', 'like', '%' . $searchValue . '%')
                    ->orWhere(DB::raw("DATE_FORMAT(created_at, '%m/%d/%y')"), 'like', '%' . $searchValue . '%');
            })
            ->where('deleted_at', null)
            ->where('catalog_import_id', $batchId)
            ->count();


        $records = ViewCatalog::select('*')
            ->where(function ($query) use ($searchValue) {
                $query->where('material_type', 'like', '%' . $searchValue . '%')
                    ->orWhere('catalog_id', 'like', '%' . $searchValue . '%')
                    ->orWhere('accession_number', 'like', '%' . $searchValue . '%')
                    ->orWhere('title_statement', 'like', '%' . $searchValue . '%')
                    ->orWhere('isbn', 'like', '%' . $searchValue . '%')
                    ->orWhere('issn', 'like', '%' . $searchValue . '%')
                    ->orWhere('pub_year', 'like', '%' . $searchValue . '%')
                    ->orWhere('publisher', 'like', '%' . $searchValue . '%')
                    ->orWhere('joint_authors', 'like', '%' . $searchValue . '%')
                    ->orWhere(DB::raw("DATE_FORMAT(created_at, '%m/%d/%y')"), 'like', '%' . $searchValue . '%');
            })
            ->where('deleted_at', null)
            ->where('catalog_import_id', $batchId)
            ->orderBy($columnName, $columnSortOrder === 'asc' ? 'desc' : 'asc')
            ->skip($start)
            ->take($rowperpage)
            ->get();


            $resultArr = [];

            foreach($records as $record) {
                $resultArr[] = [
                    'id' => $record->catalog_id,
                    'material_type' => $record->material_type,
                    'pub_year' => $record->pub_year,
                    'publisher' => $record->publisher,
                    'authors' => $record->joint_authors,
                    'title_statement' => $record->title_statement,
                ];
            }
   
         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
         );
   
         return response()->json($response);
    }

    public function storeBatch(BatchUploadRequest $request)
    {
        $records = Collection::fromFile($request->file('batch_file'));
        
        $user = Auth::user();

        $files = [];
        $fields = [];
        $leaders = [];
        $r = [];

        foreach($records as $record){
            $a = $record->record;
            $files[] = $a;     
            $r[] = $record;
        }
        
        for($b = 0; $b < count($files); $b++) {
            $fields[] = $files[$b]->getFields();
            $leaders[] = $files[$b]->getLeader();
        }
        
        $formattedRecords = [];

        $totalRecords = count($fields);

        $catalogImport = CatalogImport::create([
            'no_of_records' => $totalRecords,
            'is_aanr' => isset($request->is_aanr) ? 1 : 0,
            'comment' => $request->comment,
            'material_type_id' => $request->material_type_id,
            'status' => 'imported',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // $task = new CatalogImportProgress();
        // $task->total_records = $totalRecords;
        // $task->save();

        foreach($fields as $index => $f) {
            $marcTags = [];

            foreach($f as $c) {
                $marcTags[] = $c->getTag();
            }

            try{
                // LDR - LEADER
                $fa = []; 
    
                $subfieldArr = array();
                $defaultArr = array();
                $indicatorArr = array();
                
                $indicatorArr[0] = [
                    "id" => 0,
                    "indicator" => "N"
                ];
                $indicatorArr[1] = [
                    "id" => 0,
                    "indicator" => "N"
                ];
    
                $l = str_split($leaders[$index]);
    
                $subfieldArr[0] = [
                    "sub-index" => 1,
                    "id" => 2617,
                    "code" => "LDR$00-04",
                    "description" => "Record length",
                    "value" => $l[0].$l[1].$l[2].$l[3].$l[4]
                ];
                
                $subfieldArr[1] = [
                    "sub-index" => 2,
                    "id" => 2618,
                    "code" => "LDR$05",
                    "description" => "Record status",
                    "value" => $l[5]
                ];
    
                $subfieldArr[2] = [
                    "sub-index" => 3,
                    "id" => 2619,
                    "code" => "LDR$06",
                    "description" => "Type of record",
                    "value" => $l[6]
                ];
    
                $subfieldArr[3] = [
                    "sub-index" => 4,
                    "id" => 2620,
                    "code" => "LDR$07",
                    "description" => "Bibliographic level",
                    "value" => $l[7]
                ];
    
                $subfieldArr[4] = [
                    "sub-index" => 5,
                    "id" => 2621,
                    "code" => "LDR$08",
                    "description" => "Type of control",
                    "value" => $l[8]
                ];
    
                $subfieldArr[5] = [
                    "sub-index" => 6,
                    "id" => 2622,
                    "code" => "LDR$09",
                    "description" => "Character coding scheme",
                    "value" => $l[9]
                ];
    
                $subfieldArr[6] = [
                    "sub-index" => 7,
                    "id" => 2623,
                    "code" => "LDR$10",
                    "description" => "Indicator count",
                    "value" => $l[10]
                ];
    
                $subfieldArr[7] = [
                    "sub-index" => 8,
                    "id" => 2624,
                    "code" => "LDR$10",
                    "description" => "Subfield code count",
                    "value" => $l[11]
                ];
    
                $subfieldArr[8] = [
                    "sub-index" => 9,
                    "id" => 2625,
                    "code" => "LDR$12-16",
                    "description" => "Base address of data",
                    "value" => $l[12].$l[13].$l[14].$l[15].$l[16]
                ];
            
    
                $subfieldArr[9] = [
                    "sub-index" => 10,
                    "id" => 2626,
                    "code" => "LDR$17",
                    "description" => "Encoding level",
                    "value" => $l[17]
                ];
    
                $subfieldArr[10] = [
                    "sub-index" => 11,
                    "id" => 2627,
                    "code" => "LDR$18",
                    "description" => "Descriptive cataloging form",
                    "value" => $l[18]
                ];
    
                $subfieldArr[11] = [
                    "sub-index" => 12,
                    "id" => 2628,
                    "code" => "LDR$19",
                    "description" => "Multipart resource record level",
                    "value" => $l[19]
                ];
    
                $subfieldArr[12] = [
                    "sub-index" => 13,
                    "id" => 2629,
                    "code" => "LDR$20",
                    "description" => "Length of the length-of-field portion",
                    "value" => $l[20]
                ];
    
                $subfieldArr[13] = [
                    "sub-index" => 14,
                    "id" => 2630,
                    "code" => "LDR$21",
                    "description" => "Length of the starting-character-position portion",
                    "value" => $l[21]
                ];
    
                $subfieldArr[14] = [
                    "sub-index" => 15,
                    "id" => 2631,
                    "code" => "LDR$22",
                    "description" => "Length of the implementation-defined portion",
                    "value" => $l[22]
                ];
    
                $subfieldArr[15] = [
                    "sub-index" => 16,
                    "id" => 2632,
                    "code" => "LDR$23",
                    "description" => "Undefined",
                    "value" => $l[23]
                ];
                
                $fa[] = array(
                    'marctag' => [
                        "index" => (int)$index+1, 
                        "id" => "2501", 
                        "code" => "LDR",
                        "description" => "Leader",
                     ],
                    'subfield' => $subfieldArr,
                    'indicator' => $indicatorArr,
    
                );
                // End of Leader

                $repeatedMarctags = [];

                for($w = 0; $w< count($marcTags); $w++) {
                    
                    // $fa = []; 

                    $subfieldArr = array();
    
                    $defaultArr = array();
    
                    $indicatorArr = array();
    
                    $required = 0;
    
                    foreach ($r[$index]->query($marcTags[$w]) as $field) {
                        
                        $marcTagid = getTagDetails(0,$marcTags[$w]);
                        
                        $marcTagCode = $marcTags[$w];

                        $marcTagDescription = getTagDetails(0,$marcTags[$w], 'description');

                        if(method_exists($field->field, 'getSubfields')) {

                            $indicatorArr1[$w] = $field->getIndicator(1);
                            $indicatorArr2[$w] = $field->getIndicator(2);
                      
                            foreach($field->getSubfields() as $i => $y) {
                                $sfCode = $marcTags[$w]."$".$y->getCode();
                                $subfieldArr[] = [
                                    "sub-index" => (int)$i+1,
                                    "id" => getSubfieldId($sfCode),
                                    "code" => $sfCode,
                                    "description" => getSubfield($sfCode, 'description'),
                                    "value" => $y->getData()
                                ];
                                $indicatorArr[0] = [
                                    "id" => getFirstIndicatorId($marcTagid, $indicatorArr1[$w]),
                                    "indicator" => $indicatorArr1[$w]
                                ];
                                $indicatorArr[1] = [
                                    "id" => getSecondIndicatorId($marcTagid, $indicatorArr2[$w]),
                                    "indicator" => $indicatorArr2[$w]
                                ];
                            }
                        } else {
                            $string = $field->getData();
                            $c = str_split($string);
                            // getSubfield('001$#', 'description')
                            if($marcTags[$w] == '001'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2613,
                                    "code" => "001$#",
                                    "description" => "Control Number",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '003'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2614,
                                    "code" => "003$#",
                                    "description" => "Control Number Identifier",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '005'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2612,
                                    "code" => "005$#",
                                    "description" => "Date and Time",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '006'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2615,
                                    "code" => "006$#",
                                    "description" => "Fixed-Length Data",
                                    "value" => $string
                                ];
                            }
    
                            if($marcTags[$w] == '007'){
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2616,
                                    "code" => "007$#",
                                    "description" => "Physical Description Fixed Field",
                                    "value" => $string
                                ];
                            }
    
    
                            if($marcTags[$w] == '008') {
                            
                                $subfieldArr[0] = [
                                    "sub-index" => 1,
                                    "id" => 2603,
                                    "code" => "008$00-05",
                                    "description" => "Date entered on file",
                                    "value" => $c[0].$c[1].$c[2].$c[3].$c[4].$c[5]
                                ];
    
                                $subfieldArr[1] = [
                                    "sub-index" => 2,
                                    "id" => 2604,
                                    "code" => "008$06",
                                    "description" => "Type of date/Publication status",
                                    "value" => $c[6]
                                ];
    
                                $subfieldArr[2] = [
                                    "sub-index" => 3,
                                    "id" => 2605,
                                    "code" => "008$07-10",
                                    "description" => "Date 1",
                                    "value" => $c[7].$c[8].$c[9].$c[10]
                                ];
    
                                $subfieldArr[3] = [
                                    "sub-index" => 4,
                                    "id" => 2606,
                                    "code" => "008$11-14",
                                    "description" => "Date2",
                                    "value" => $c[11].$c[12].$c[13].$c[14]
                                ];
    
                                $subfieldArr[4] = [
                                    "sub-index" => 5,
                                    "id" => 2607,
                                    "code" => "008$15-17",
                                    "description" => "Place of publication, production, or execution",
                                    "value" => $c[15].$c[16].$c[17]
                                ];
                                
                                $subfieldArr[5] = [
                                    "sub-index" => 6,
                                    "id" => 2608,
                                    "code" => "008$18-34",
                                    "description" => "Material specific coded elements",
                                    "value" => $c[18].$c[19].$c[20].$c[21].$c[22].$c[23].$c[24].$c[25].$c[26].$c[27].$c[28].$c[29].$c[30].$c[31].$c[32].$c[33].$c[34]
                                ];
                                
                                    if(isset($c[35])) {
                                        $subfieldArr[6] = [
                                            "sub-index" => 7,
                                            "id" => 2609,
                                            "code" => "008$35-37",
                                            "description" => "Language",
                                            "value" => $c[35].$c[36].$c[37]
                                        ];
                                        
                                        $subfieldArr[7] = [
                                            "sub-index" => 8,
                                            "id" => 2610,
                                            "code" => "008$38",
                                            "description" => "Modified record",
                                            "value" => $c[38]
                                        ];
    
                                        $subfieldArr[8] = [
                                            "sub-index" => 9,
                                            "id" => 2611,
                                            "code" => "008$39",
                                            "description" => "Cataloging source",
                                            "value" => $c[39]
                                        ];
                                    }
                                
                            } 
                            $indicatorArr[0] = [
                                "id" => 0,
                                "indicator" => "N"
                            ];
                            $indicatorArr[1] = [
                                "id" => 0,
                                "indicator" => "N"
                            ];
    
                           
                        }
                    }
                    
                    if(!in_array($marcTagid, $repeatedMarctags)) {
                        $fa[] = array(
                            'marctag' => [
                                "index" => $w+1, 
                                "id" => $marcTagid, 
                                "code" => $marcTagCode,
                                "description" => $marcTagDescription
                            ],
                            'subfield' => $subfieldArr,
                            'indicator' => $indicatorArr,
            
                        );
                        $repeatedMarctags[] = $marcTagid;
                    }
                   
                    
                }
                $formattedRecords[] = $fa;
                $fieldDataString = json_encode($fa);    
        



                $titleTagCode = "245"; // Marctag for Title Statemet 
                $titleCode =  "245\$a"; // Subfield code for Title
                
                $generalNoteTagCode = "500";
                $generalNoteCode =  "500\$a";
        
                $summaryTagCode = "520";
                $summaryCode = "520\$a";
        
                $publisherTagCode = "260";
                $publishersCode = "260\$b";
                $publicationDatesCode = "260\$c";
        
                $authorTagCode = "100";
                $authorsCode = "100\$a";
                $termCode = "700\$e";
                
                $isbnTagCode = "020";
                $isbnCode = "020\$a";

                $issnTagCode = "022";
                $issnCode = "022\$a";

                $callNumberTagCode = "050";
                $callNumberCode =  "050\$a";

                $recordTypeTagCode = "LDR";
                $recordTypeCode = "LDR\$06";
        
                    $fieldData = json_decode($fieldDataString, true);
                    // Getting the title 
                    $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
                    })->flatMap(function ($item) use ($titleCode) {
                        $titles = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                                if ($subfield['code'] == $titleCode) {
                                    $titles['title'] = $subfield['value'];
                                }
                            });
                        }            
                        return $titles;
                    })->toArray();

                    // Getting the recordtype 
                    $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
                    })->flatMap(function ($item) use ($recordTypeCode) {
                        $recordType = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                                if ($subfield['code'] == $recordTypeCode) {
                                    $recordType['recordType'] = $subfield['value'];
                                }
                            });
                        }            
                        return $recordType;
                    })->toArray();
        
                    // Getting the ISBN 
                    $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
                    })->flatMap(function ($item) use ($isbnCode) {
                        $isbn = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                                if ($subfield['code'] == $isbnCode) {
                                    $isbn['isbn'] = $subfield['value'];
                                }
                            });
                        }            
                        return $isbn;
                    })->toArray();

                    // Getting the ISSN 
                    $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
                    })->flatMap(function ($item) use ($issnCode) {
                        $issn = []; 
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                                if ($subfield['code'] == $issnCode) {
                                    $issn['issn'] = $subfield['value'];
                                }
                            });
                        }            
                        return $issn;
                    })->toArray();
        
        
                    // Getting the general note
                    $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
                    })->flatMap(function ($item) use ($generalNoteCode) {
                        $generalNote = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                                if ($subfield['code'] == $generalNoteCode) {
                                    $generalNote['general_note'] = $subfield['value'];
                                }
                            });
                        }            
                        return $generalNote;
                    })->toArray();
                    

                    // Getting the call number
                    $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
                    })->flatMap(function ($item) use ($callNumberCode) {
                        $callNumber = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                                if ($subfield['code'] == $callNumberCode) {
                                    $callNumber['call_number'] = $subfield['value'];
                                }
                            });
                        }            
                        return $callNumber;
                    })->toArray();
                    
        
                    // Getting the Summary
                    $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
                    })->flatMap(function ($item) use ($summaryCode) {
                        $summary = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                                if ($subfield['code'] == $summaryCode) {
                                    $summary['summary'] = $subfield['value'];
                                }
                            });
                        }            
                        return $summary;
                    })->toArray();
        
                    // Getting the Publisher
                    $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
                    })->flatMap(function ($item) use ($publishersCode) {
                        $publisher = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                                if ($subfield['code'] == $publishersCode) {
                                    $publisher['publisher'] = $subfield['value'];
                                }
                            });
                        }            
                        return $publisher;
                    })->toArray();
        
        
                    // Getting the Date of Publication
                    $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
                    })->flatMap(function ($item) use ($publicationDatesCode) {
                        $dateOfPublication = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                                if ($subfield['code'] == $publicationDatesCode) {
                                    $dateOfPublication['date_of_pub'] = $subfield['value'];
                                }
                            });
                        }            
                        return $dateOfPublication;
                    })->toArray();
        
                    // Getting the Relator/Author
                    $matchingAuthor = collect($fieldData)->filter(function ($item) use ($authorTagCode) {
                        return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $authorTagCode;
                    })->flatMap(function ($item) use ($authorsCode, $termCode) {
                        $author = [];
                        $role = [];
                        if (isset($item['subfield'])) {
                            collect($item['subfield'])->each(function ($subfield) use ($authorsCode, $termCode, &$author, &$role) {
                                if ($subfield['code'] == $authorsCode) {
                                    $author[] = $subfield['value'];
                                }
        
                                if ($subfield['code'] == $termCode) {
                                    $role[] = $subfield['value'];
                                }
                            });
                        }            
                        return [$author, $role];
                    })->toArray();
        
        
                    if( count($matchingAuthor) > 0) {
                        $jointAuthors = []; // Initialize an empty array to store joint authors
        
                        for ($x = 0; $x < count($matchingAuthor[0]); $x++) {
                            if(count($matchingAuthor[0]) == count($matchingAuthor[1])) {
                                $jointAuthors[] = $matchingAuthor[0][$x] . ' ' . $matchingAuthor[1][$x];                    
                            } else {
                                $jointAuthors[] = $matchingAuthor[0][$x];
                            }
        
        
                        }
                        $jointAuthorString = implode(', ', $jointAuthors); // Converts the array to a comma-separated string
                        
                        // Remove square brackets
                        $jointAuthorString = str_replace(['[', ']'], '', $jointAuthorString);
                    } else {
                        $jointAuthorString = NULL;
                    }

                    if(isset($matchingRecordType['recordType'])) {
                        $material = getMaterialDataCUsingCode($matchingRecordType['recordType']);
                    } else {
                        $material = getMaterialData($request->material_type_id);
                    }
        
                    $material_type = $material->name;
                    $material_type_id = $material->id;

                    $viewCatMattype = getMaterialData($request->material_type_id);


                    // $task->progress = $index+1;
                    // $task->save();

                $catalog = Catalog::create([
                    'material_type_id' => $material_type_id,
                    'field_data' => $fieldDataString,
                    'catalog_import_id' => $catalogImport->id,
                    'is_aanr' => $request->is_aanr ? 1 : 0,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                if($catalog) {
                    ViewCatalog::create([
                        'catalog_id' => $catalog->id,
                        'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
                        'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
                        'material_type' => $viewCatMattype->name,
                        'material_type_id'=> $viewCatMattype->id,
                        'catalog_import_id' => $catalogImport->id,
                        'title_statement' => isset($matchingTitles['title']) ? $matchingTitles['title'] : NULL,
                        'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
                        'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
                        'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
                        'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
                        'joint_authors' => $jointAuthorString, 
                        'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
                     
                $formattedRecords = [];
            } catch (Exception $e) {
                dump("Error: $e->getMessage()");
            }
          
        }
        // CatalogImportProgress::truncate();

        return view('admin.batch-list', ['message' => 'Successfully saved!']);

    }

    public function batchList()
    {
        return view('admin.batch-list');
    }

    public function createBatch()
    {
        $materials = Material::get();
        
        return view('admin.create-batch', ['materials' => $materials]);
    }

    public function destroyBatch(Request $request)
    {
        $user = Auth::user();
        CatalogImport::where('id',$request->id)
            ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id
                ]);

        Catalog::where('catalog_import_id',$request->id)
            ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id
                ]);

        ViewCatalog::where('catalog_import_id',$request->id)
            ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id
                ]);

        return redirect()->back();
    }

    public function batchListJson(Request $request)
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
        $totalRecords = CatalogImport::where('deleted_at', null)->count();

        

        $totalRecordswithFilter = CatalogImport::select(DB::raw('count(*) as allcount'))->where(function ($query) use ($searchValue) {
            $query->where('no_of_records', 'like', '%' . $searchValue . '%')
                ->orWhere('status', 'like', '%' . $searchValue . '%')
                ->orWhere('created_at', 'like', '%' . $searchValue . '%');
        })->where('deleted_at', null)->count();

        // Fetch records
        $records = CatalogImport::select('*', 'catalog_imports.id as batch_id', \DB::raw('count(case when catalog_imports.id = catalogs.catalog_import_id and catalogs.deleted_at is null then 1 else null end) as current_no_of_records'))
        ->join('catalogs', 'catalogs.catalog_import_id', '=', 'catalog_imports.id')
        ->where(function ($query) use ($searchValue) {
            $query->where('no_of_records', 'like', '%' . $searchValue . '%')
                ->orWhere('catalog_imports.status', 'like', '%' . $searchValue . '%')
                ->orWhere('catalog_imports.created_at', 'like', '%' . $searchValue . '%');
        })
        ->where('catalog_imports.deleted_at', null)
        ->orderBy('catalog_imports.'.$columnName, $columnSortOrder === 'asc' ? 'desc' : 'asc')
        ->groupBy('catalog_imports.id')
        ->skip($start)
        ->take($rowPerPage)
        ->get();

        $data_arr = array();

        foreach($records as $index => $record){
            $data_arr[] = array(
                "id" =>  $record->batch_id,
                "init_no_of_records" => $record->no_of_records,
                "current_no_of_records" => $record->current_no_of_records,
                "status" => $record->status,
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

    public function singleImportMarcFile(MarcFileUploadRequest $request)
    {
        //$marcTagsFields = MarctagSubfield::get();

        $record = Record::fromFile($request->file('marcFile'));

        $a = $record->record;
        
        

        foreach ($a->getFields() as $f) {
            
            $tags = $f->getTag();

            $marcTags[] = $tags;
        
        }
        try{
            $fa = [];

            $subfieldArr = array();
            $defaultArr = array();
            $indicatorArr = array();
            
            $indicatorArr[0] = null;
            $indicatorArr[1] = null;

            // LDR - LEADER
            $l = str_split($a->getLeader());

            $subfieldArr[0] = getSubfieldId('LDR$00-04');
            $defaultArr[0] = $l[0].$l[1].$l[2].$l[3].$l[4];
            
            $subfieldArr[1] = getSubfieldId('LDR$05');
            $defaultArr[1] = $l[5];

            $subfieldArr[2] = getSubfieldId('LDR$06');
            $defaultArr[2] = $l[6];

            $subfieldArr[3] = getSubfieldId('LDR$07');
            $defaultArr[3] = $l[7];

            $subfieldArr[4] = getSubfieldId('LDR$08');
            $defaultArr[4] = $l[8];

            $subfieldArr[5] = getSubfieldId('LDR$09');
            $defaultArr[5] = $l[9];

            $subfieldArr[6] = getSubfieldId('LDR$10');
            $defaultArr[6] = $l[10];

            $subfieldArr[7] = getSubfieldId('LDR$11');
            $defaultArr[7] = $l[11];

            $subfieldArr[8] = getSubfieldId('LDR$12-16');
            $defaultArr[8] = $l[12].$l[13].$l[14].$l[15].$l[16];

            $subfieldArr[9] = getSubfieldId('LDR$17');
            $defaultArr[9] = $l[17];

            $subfieldArr[10] = getSubfieldId('LDR$18');
            $defaultArr[10] = $l[18];

            $subfieldArr[11] = getSubfieldId('LDR$19');
            $defaultArr[11] = $l[19];

            $subfieldArr[12] = getSubfieldId('LDR$20');
            $defaultArr[12] = $l[20];

            $subfieldArr[13] = getSubfieldId('LDR$21');
            $defaultArr[13] = $l[21];

            $subfieldArr[14] = getSubfieldId('LDR$22');
            $defaultArr[14] = $l[22];

            $subfieldArr[15] = getSubfieldId('LDR$23');
            $defaultArr[15] = $l[23];
            
            $fa[] = array(
                'id' => "2501",
                'required' =>  0,
                'subfield' => $subfieldArr,
                'default' => $defaultArr,
                'indicator' => $indicatorArr,

            );

            $repeatedMarctags = [];

            for($w = 0; $w< count($marcTags); $w++) {
                
                $subfieldArr = array();

                $defaultArr = array();

                $indicatorArr = array();

                $required = 0;

                foreach ($record->query($marcTags[$w]) as $field) {
                    
                    $marcTagid = getTagDetails(0,$marcTags[$w]);
                    
                    if(method_exists($field->field, 'getSubfields')) {
                        $reflectionClass = new ReflectionClass($field->field);
                        $methods = $reflectionClass->getMethods();
                    
                        $indicatorArr1[$w] = $field->getIndicator(1);
                        $indicatorArr2[$w] = $field->getIndicator(2);
                        
                        foreach($field->getSubfields() as $y) {
                            $sfCode = $marcTags[$w]."$".$y->getCode();
                            $subfieldArr[] = getSubfieldId($sfCode);
                            $defaultArr[] = $y->getData();
                            $indicatorArr[0] = getFirstIndicatorId($marcTagid, $indicatorArr1[$w]);
                            $indicatorArr[1] = getSecondIndicatorId($marcTagid, $indicatorArr2[$w]);
                        }
                    } else {
                        $string = $field->getData();
                        $c = str_split($string);


                        if($marcTags[$w] == '001'){

                            $subfieldArr[0] = getSubfieldId('001$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '003'){

                            $subfieldArr[0] = getSubfieldId('003$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '005'){

                            $subfieldArr[0] = getSubfieldId('005$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '006'){

                            $subfieldArr[0] = getSubfieldId('006$#');
                            $defaultArr[0] = $string;
                        }

                        if($marcTags[$w] == '007'){

                            $subfieldArr[0] = getSubfieldId('007$#');
                            $defaultArr[0] = $string;
                        }


                        if($marcTags[$w] == '008') {
                        
                            $subfieldArr[0] = getSubfieldId('008$00-05');
                            $defaultArr[0] = $c[0].$c[1].$c[2].$c[3].$c[4].$c[5];

                            $subfieldArr[1] = getSubfieldId('008$06');
                            $defaultArr[1] = $c[6];

                            $subfieldArr[2] = getSubfieldId('008$07-10');
                            $defaultArr[2] = $c[7].$c[8].$c[9].$c[10];

                            $subfieldArr[3] = getSubfieldId('008$11-14');
                            $defaultArr[3] = $c[11].$c[12].$c[13].$c[14];

                            $subfieldArr[4] = getSubfieldId('008$15-17');
                            $defaultArr[4] = $c[15].$c[16].$c[17];
                            
                            $subfieldArr[5] = getSubfieldId('008$18-34');
                            $defaultArr[5] = $c[18].$c[19].$c[20].$c[21].$c[22].$c[23].$c[24].$c[25].$c[26].$c[27].$c[28].$c[29].$c[30].$c[31].$c[32].$c[33].$c[34];
                            
                                if(isset($c[35])) {
                                    $subfieldArr[6] = getSubfieldId('008$35-37');
                                    $defaultArr[6] = $c[35].$c[36].$c[37];
                                    
                                    $subfieldArr[7] = getSubfieldId('008$38');
                                    $defaultArr[7] = $c[38];

                                    $subfieldArr[8] = getSubfieldId('008$39');
                                    $defaultArr[8] = $c[39];
                                }
                            
                        } 
                        $indicatorArr[0] = null;
                        $indicatorArr[1] = null;

                        
                    }
                }
                
                if(!in_array($marcTagid, $repeatedMarctags)) {
                    $fa[] = array(
                        'id' => $marcTagid,
                        'required' =>  $required,
                        'subfield' => $subfieldArr,
                        'default' => $defaultArr,
                        'indicator' => $indicatorArr,
    
                    );
                    $repeatedMarctags[] = $marcTagid;
                }

                // dump($fa)
                $faString = json_encode($fa);
            }

        } catch (Exception $e) {
            dump("Error: $e->getMessage()");
        }

        // dd($fa);
        $fieldArray = json_decode($faString);
    
        $detailsArr = array();
        
        $indicatorArr = array();
        
        foreach($fieldArray as $index => $tag) {
            // dump($tag);
            $tagDetail = getTagDetails($tag->id);

            $subfieldArr = array();
            if(count($tag->subfield) > 0) {
                foreach($tag->subfield as $i => $id)
                {
                    // dd($id);
                    $subFieldDetails = getSubfieldDetails($id);

                    $subfieldArr[$i] = ['id' => $subFieldDetails->id, 'code' => $subFieldDetails->code, 'description' => $subFieldDetails->description, 'default' => count($tag->default) > 0 ? $tag->default[$i] : "Enter value" ];
                }
            } 

            $ind1_id = is_null(getIndicatorDetails($tag->indicator[0])) ? 0 : getIndicatorDetails($tag->indicator[0])->id;
            $ind2_id = is_null(getIndicatorDetails($tag->indicator[1])) ? 0 : getIndicatorDetails($tag->indicator[1])->id;
            $ind1_text = is_null(getIndicatorDetails($tag->indicator[0])) ? 'N' : getIndicatorDetails($tag->indicator[0])->indicator;
            $ind2_text = is_null(getIndicatorDetails($tag->indicator[1])) ? 'N' : getIndicatorDetails($tag->indicator[1])->indicator;
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
            "fields" => $detailsArr,
        );


        $jsonOutput = json_encode($response);

        $marcTemplates = MarcTemplate::get();

        $materials = Material::get();

        if ($request->action  == "edit") {

            $record = Catalog::where('id', $request->record_id)->first();
            
            return view('admin.catalog-edit-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'fieldData' => $jsonOutput, 'record' => $record]);            
        }

        return view('admin.create-catalog-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'fieldData' => $jsonOutput]);
    }
    
    public function storeRecord(Request $request)
    {
        $user = Auth::user();

        $titleTagCode = "245"; // Marctag for Title Statemet 
        $titleCode =  "245\$a"; // Subfield code for Title
        
        $generalNoteTagCode = "500";
        $generalNoteCode =  "500\$a";

        $summaryTagCode = "520";
        $summaryCode = "520\$a";

        $publisherTagCode = "260";
        $publishersCode = "260\$b";
        $publicationDatesCode = "260\$c";

        $authorTagCode = "100";
        $authorsCode = "100\$a";
        $termCode = "700\$e";
        
        $isbnTagCode = "020";
        $isbnCode = "020\$a";

        $issnTagCode = "022";
        $issnCode = "022\$a";

        $callNumberTagCode = "050";
        $callNumberCode =  "050\$a";

        $recordTypeTagCode = "LDR";
        $recordTypeCode = "LDR\$06";

            $fieldData = json_decode($request->field_data, true);
            // Getting the title 
            $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
            })->flatMap(function ($item) use ($titleCode) {
                $titles = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                        if ($subfield['code'] == $titleCode) {
                            $titles['title'] = $subfield['value'];
                        }
                    });
                }            
                return $titles;
            })->toArray();

            // Getting the recordtype 
            $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
            })->flatMap(function ($item) use ($recordTypeCode) {
                $recordType = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                        if ($subfield['code'] == $recordTypeCode) {
                            $recordType['recordType'] = $subfield['value'];
                        }
                    });
                }            
                return $recordType;
            })->toArray();
            

            // Getting the ISBN 
            $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
            })->flatMap(function ($item) use ($isbnCode) {
                $isbn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                        if ($subfield['code'] == $isbnCode) {
                            $isbn['isbn'] = $subfield['value'];
                        }
                    });
                }            
                return $isbn;
            })->toArray();

            // Getting the ISSN 
            $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
            })->flatMap(function ($item) use ($issnCode) {
                $issn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                        if ($subfield['code'] == $issnCode) {
                            $issn['issn'] = $subfield['value'];
                        }
                    });
                }            
                return $issn;
            })->toArray();


            // Getting the general note
            $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
            })->flatMap(function ($item) use ($generalNoteCode) {
                $generalNote = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                        if ($subfield['code'] == $generalNoteCode) {
                            $generalNote['general_note'] = $subfield['value'];
                        }
                    });
                }            
                return $generalNote;
            })->toArray();


            // Getting the call number
            $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
            })->flatMap(function ($item) use ($callNumberCode) {
                $callNumber = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                        if ($subfield['code'] == $callNumberCode) {
                            $callNumber['call_number'] = $subfield['value'];
                        }
                    });
                }            
                return $callNumber;
            })->toArray();
            

            // Getting the Summary
            $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
            })->flatMap(function ($item) use ($summaryCode) {
                $summary = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                        if ($subfield['code'] == $summaryCode) {
                            $summary['summary'] = $subfield['value'];
                        }
                    });
                }            
                return $summary;
            })->toArray();

            // Getting the Publisher
            $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publishersCode) {
                $publisher = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                        if ($subfield['code'] == $publishersCode) {
                            $publisher['publisher'] = $subfield['value'];
                        }
                    });
                }            
                return $publisher;
            })->toArray();


            // Getting the Date of Publication
            $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publicationDatesCode) {
                $dateOfPublication = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                        if ($subfield['code'] == $publicationDatesCode) {
                            $dateOfPublication['date_of_pub'] = $subfield['value'];
                        }
                    });
                }            
                return $dateOfPublication;
            })->toArray();

            // Getting the Relator/Author
            $matchingAuthor = collect($fieldData)->filter(function ($item) use ($authorTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $authorTagCode;
            })->flatMap(function ($item) use ($authorsCode, $termCode) {
                $author = [];
                $role = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($authorsCode, $termCode, &$author, &$role) {
                        if ($subfield['code'] == $authorsCode) {
                            $author[] = $subfield['value'];
                        }

                        if ($subfield['code'] == $termCode) {
                            $role[] = $subfield['value'];
                        }
                    });
                }            
                return [$author, $role];
            })->toArray();


            if( count($matchingAuthor) > 0) {
                $jointAuthors = []; // Initialize an empty array to store joint authors

                for ($x = 0; $x < count($matchingAuthor[0]); $x++) {
                    if(count($matchingAuthor[0]) == count($matchingAuthor[1])) {
                        $jointAuthors[] = $matchingAuthor[0][$x] . ' ' . $matchingAuthor[1][$x];                    
                    } else {
                        $jointAuthors[] = $matchingAuthor[0][$x];
                    }


                }
                $jointAuthorString = implode(', ', $jointAuthors); // Converts the array to a comma-separated string
                
                // Remove square brackets
                $jointAuthorString = str_replace(['[', ']'], '', $jointAuthorString);
            } else {
                $jointAuthorString = NULL;
            }

            if(isset($matchingRecordType['recordType'])) {
                $material = getMaterialDataCUsingCode($matchingRecordType['recordType']);
            } else {
             
                $material = getMaterialData($request->material_type);
            }
            
            $material_type = $material->name;
            $material_type_id = $material->id;

            $viewCatMatType = getMaterialData($request->material_type);

        $catalog = Catalog::create([
            'material_type_id' => $material_type_id,
            'is_aanr' => isset($request->is_aanr) ? 1 : 0,
            'marc_class_sys_id' => 1,
            'accession_number'=> $request->accession_number,
            'field_data' => $request->field_data,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);


        if($catalog) {
            ViewCatalog::create([
                'catalog_id' => $catalog->id,
                'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
                'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
                'accession_number' => $request->accession_number,
                'material_type' => $viewCatMatType->name,
                'material_type_id'=> $viewCatMatType->id,
                'title_statement' => isset($matchingTitles['title']) ? $matchingTitles['title'] : NULL,
                'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
                'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
                'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
                'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
                'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
                'joint_authors' => $jointAuthorString, 
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }


        $marcTemplates = MarcTemplate::get();

        $materials = Material::get();

        return view('admin.create-catalog-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials]);
    }

    public function destroyRecord(Request $request)
    {
        $user = Auth::user();
        Catalog::where('id',$request->id)
            ->update([
                    'deleted_at' => now(),
                    'deleted_by' => $user->id
                ]);

        ViewCatalog::where('catalog_id',$request->id)
            ->update([
                'deleted_at' => now(),
                'deleted_by' => $user->id
            ]);

        return redirect()->back();
    }

    public function editRecord($id)
    {
        $record = Catalog::where('id', $id)->first();
        
        $fieldData = $record->field_data;
        
        $marcTemplates = MarcTemplate::get();

        $materials = Material::get();
        // dd($fieldData);
        return view('admin.catalog-edit-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'record' => $record, 'fieldData' => $fieldData]);
    }

    public function updateRecord(Request $request, $id)
    {
        $user = Auth::user();

        $titleTagCode = "245"; // Marctag for Title Statemet 
        $titleCode =  "245\$a"; // Subfield code for Title
        
        $generalNoteTagCode = "500";
        $generalNoteCode =  "500\$a";

        $summaryTagCode = "520";
        $summaryCode = "520\$a";

        $publisherTagCode = "260";
        $publishersCode = "260\$b";
        $publicationDatesCode = "260\$c";

        $authorTagCode = "100";
        $authorsCode = "100\$a";
        $termCode = "700\$e";
        
        $isbnTagCode = "020";
        $isbnCode = "020\$a";

        $issnTagCode = "022";
        $issnCode = "022\$a";

        $callNumberTagCode = "050";
        $callNumberCode =  "050\$a";

        $recordTypeTagCode = "LDR";
        $recordTypeCode = "LDR\$06";

            $fieldData = json_decode($request->field_data, true);
            // Getting the title 
            $matchingTitles = collect($fieldData)->filter(function ($item) use ($titleTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $titleTagCode;
            })->flatMap(function ($item) use ($titleCode) {
                $titles = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($titleCode, &$titles) {
                        if ($subfield['code'] == $titleCode) {
                            $titles['title'] = $subfield['value'];
                        }
                    });
                }            
                return $titles;
            })->toArray();

            // Getting the recordtype 
            $matchingRecordType = collect($fieldData)->filter(function ($item) use ($recordTypeTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $recordTypeTagCode;
            })->flatMap(function ($item) use ($recordTypeCode) {
                $recordType = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($recordTypeCode, &$recordType) {
                        if ($subfield['code'] == $recordTypeCode) {
                            $recordType['recordType'] = $subfield['value'];
                        }
                    });
                }            
                return $recordType;
            })->toArray();
            

            // Getting the ISBN 
            $matchingISBN = collect($fieldData)->filter(function ($item) use ($isbnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $isbnTagCode;
            })->flatMap(function ($item) use ($isbnCode) {
                $isbn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($isbnCode, &$isbn) {
                        if ($subfield['code'] == $isbnCode) {
                            $isbn['isbn'] = $subfield['value'];
                        }
                    });
                }            
                return $isbn;
            })->toArray();

            // Getting the ISSN 
            $matchingISSN = collect($fieldData)->filter(function ($item) use ($issnTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $issnTagCode;
            })->flatMap(function ($item) use ($issnCode) {
                $issn = []; 
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($issnCode, &$issn) {
                        if ($subfield['code'] == $issnCode) {
                            $issn['issn'] = $subfield['value'];
                        }
                    });
                }            
                return $issn;
            })->toArray();


            // Getting the general note
            $matchingGeneralNote = collect($fieldData)->filter(function ($item) use ($generalNoteTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $generalNoteTagCode;
            })->flatMap(function ($item) use ($generalNoteCode) {
                $generalNote = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($generalNoteCode, &$generalNote) {
                        if ($subfield['code'] == $generalNoteCode) {
                            $generalNote['general_note'] = $subfield['value'];
                        }
                    });
                }            
                return $generalNote;
            })->toArray();


            // Getting the call number
            $matchingCallNumber = collect($fieldData)->filter(function ($item) use ($callNumberTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $callNumberTagCode;
            })->flatMap(function ($item) use ($callNumberCode) {
                $callNumber = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($callNumberCode, &$callNumber) {
                        if ($subfield['code'] == $callNumberCode) {
                            $callNumber['call_number'] = $subfield['value'];
                        }
                    });
                }            
                return $callNumber;
            })->toArray();
            

            // Getting the Summary
            $matchingSummary = collect($fieldData)->filter(function ($item) use ($summaryTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $summaryTagCode;
            })->flatMap(function ($item) use ($summaryCode) {
                $summary = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($summaryCode, &$summary) {
                        if ($subfield['code'] == $summaryCode) {
                            $summary['summary'] = $subfield['value'];
                        }
                    });
                }            
                return $summary;
            })->toArray();

            // Getting the Publisher
            $matchingPublisher = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publishersCode) {
                $publisher = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publishersCode, &$publisher) {
                        if ($subfield['code'] == $publishersCode) {
                            $publisher['publisher'] = $subfield['value'];
                        }
                    });
                }            
                return $publisher;
            })->toArray();


            // Getting the Date of Publication
            $matchingDateOfPublication = collect($fieldData)->filter(function ($item) use ($publisherTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $publisherTagCode;
            })->flatMap(function ($item) use ($publicationDatesCode) {
                $dateOfPublication = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($publicationDatesCode, &$dateOfPublication) {
                        if ($subfield['code'] == $publicationDatesCode) {
                            $dateOfPublication['date_of_pub'] = $subfield['value'];
                        }
                    });
                }            
                return $dateOfPublication;
            })->toArray();

            // Getting the Relator/Author
            $matchingAuthor = collect($fieldData)->filter(function ($item) use ($authorTagCode) {
                return isset($item['marctag']) && isset($item['marctag']['id']) && $item['marctag']['code'] == $authorTagCode;
            })->flatMap(function ($item) use ($authorsCode, $termCode) {
                $author = [];
                $role = [];
                if (isset($item['subfield'])) {
                    collect($item['subfield'])->each(function ($subfield) use ($authorsCode, $termCode, &$author, &$role) {
                        if ($subfield['code'] == $authorsCode) {
                            $author[] = $subfield['value'];
                        }

                        if ($subfield['code'] == $termCode) {
                            $role[] = $subfield['value'];
                        }
                    });
                }            
                return [$author, $role];
            })->toArray();


            if( count($matchingAuthor) > 0) {
                $jointAuthors = []; // Initialize an empty array to store joint authors

                for ($x = 0; $x < count($matchingAuthor[0]); $x++) {
                    if(count($matchingAuthor[0]) == count($matchingAuthor[1])) {
                        $jointAuthors[] = $matchingAuthor[0][$x] . ' ' . $matchingAuthor[1][$x];                    
                    } else {
                        $jointAuthors[] = $matchingAuthor[0][$x];
                    }


                }
                $jointAuthorString = implode(', ', $jointAuthors); // Converts the array to a comma-separated string
                
                // Remove square brackets
                $jointAuthorString = str_replace(['[', ']'], '', $jointAuthorString);
            } else {
                $jointAuthorString = NULL;
            }

            if(isset($matchingRecordType['recordType'])) {
                $material = getMaterialDataCUsingCode($matchingRecordType['recordType']);
            } else {
             
                $material = getMaterialData($request->material_type);
            }
            
            $material_type = $material->name;
            $material_type_id = $material->id;

            $viewCatMatType = getMaterialData($request->material_type);
            
            
            $catalog = Catalog::where('id', $id)
                ->update([
                    'material_type_id' => $material_type_id,
                    'is_aanr' => isset($request->is_aanr) ? 1 : 0,
                    'marc_class_sys_id' => 1,
                    'accession_number'=> $request->accession_number,
                    'field_data' => $request->field_data,
                    'updated_by' => $user->id,
                ]);


        if($catalog) {
            ViewCatalog::where('catalog_id', $id) 
                ->update([
                    'catalog_id' => $id,
                    'isbn' => isset($matchingISBN['isbn']) ? $matchingISBN['isbn'] : NULL,
                    'issn' => isset($matchingISSN['issn']) ? $matchingISSN['issn'] : NULL,
                    'accession_number' => $request->accession_number,
                    'material_type' => $viewCatMatType->name,
                    'material_type_id'=> $viewCatMatType->id,
                    'title_statement' => isset($matchingTitles['title']) ? $matchingTitles['title'] : NULL,
                    'summary' => isset($matchingSummary['summary']) ? $matchingSummary['summary'] : NULL,
                    'general_note' => isset($matchingGeneralNote['general_note']) ? $matchingGeneralNote['general_note'] : NULL,
                    'call_number' => isset($matchingCallNumber['call_number']) ? $matchingCallNumber['call_number'] : NULL,
                    'publisher' => isset($matchingPublisher['publisher']) ? $matchingPublisher['publisher'] : NULL,
                    'pub_year' => isset($matchingDateOfPublication['date_of_pub']) ? $matchingDateOfPublication['date_of_pub'] : NULL,
                    'joint_authors' => $jointAuthorString,
                    'updated_by' => $user->id,
                ]);
            }

        $record = Catalog::where('id', $id)->first();

        $fieldData = $record->field_data;

        $marcTemplates = MarcTemplate::get();

        $materials = Material::get();

        return view('admin.catalog-edit-record', ['marcTemplates' => $marcTemplates, 'materials' => $materials, 'record' => $record, 'fieldData' => $fieldData,  'message' => 'Record Updated!']);


    }

    public function recordList(Request $request)
    {
        return view('admin.catalog-record-list');
    }

    public function onholdList(Request $request)
    {
        return view('admin.catalog-onhold-list');
    }
    
    public function recordListJson(Request $request)
    {
        $draw = isset($request->filter_search) ? 1 : $request->get('draw');
        $start =  isset($request->filter_search) ? 0 : $request->get("start");
        $rowperpage =  isset($request->filter_search) ? PHP_INT_MAX : $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        if(isset($request->filter_search)) {
            $columnIndex = 0;
            $columnName = 'view_catalogs.id';
            $columnSortOrder = 'desc';
            $searchValue = is_null($search_arr)  ? '' : $search_arr['value'];
            $searchTitle = $request->filter_search;
        } else {
            $columnIndex = is_null($columnIndex_arr) ? 0 : $columnIndex_arr[0]['column']; // Column index
            $columnName = is_null($columnName_arr) ? 'view_catalogs.id' : "view_catalogs." . $columnName_arr[$columnIndex]['data']; // Column name
            $columnName = $columnName == "function" ? "view_catalogs.id" : $columnName;
            $columnSortOrder = is_null($order_arr) ? 'desc' : $order_arr[0]['dir']; // asc or desc
            $searchValue = is_null($search_arr['value']) ? '' : $search_arr['value']; // Search value
            $searchTitle = '';
        }

        // Initialize query
        $query = ViewCatalog::select('*')
            ->where('view_catalogs.deleted_at', null)
            ->where('is_published', 1);

        // Filter conditions
        switch ($searchTitle) {
            case "fulltext-only":
                $query->whereIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('multimedia')
                        ->whereNull('deleted_at');
                });
                break;
            case "coverphoto-only":
                $query->whereIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('frontpages')
                        ->whereNull('deleted_at');
                });
                break;
            case "without-fulltext":
                $query->whereNotIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('multimedia')
                        ->whereNull('deleted_at');
                });
                break;
            case "without-coverphoto":
                $query->whereNotIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('frontpages')
                        ->whereNull('deleted_at');
                });
                break;
            case "both":
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('multimedia')
                        ->whereRaw('multimedia.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('multimedia.deleted_at');
                })->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('frontpages')
                        ->whereRaw('frontpages.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('frontpages.deleted_at');
                });
                break;
            case "without-both":
                $query->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('multimedia')
                        ->whereRaw('multimedia.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('multimedia.deleted_at');
                })->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('frontpages')
                        ->whereRaw('frontpages.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('frontpages.deleted_at');
                });
                break;
        }

        // Search criteria
        $query->where(function ($query) use ($searchValue) {
            $query->where('view_catalogs.material_type', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.catalog_id', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.accession_number', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.title_statement', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.isbn', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.issn', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.pub_year', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.publisher', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.joint_authors', 'like', '%' . $searchValue . '%')
                ->orWhere(DB::raw("DATE_FORMAT(view_catalogs.created_at, '%m/%d/%y')"), 'like', '%' . $searchValue . '%');
        });

        // Add binary fields for coverpage and multimedia
        $query->selectRaw('IF((SELECT COUNT(*) FROM frontpages WHERE frontpages.catalog_id = view_catalogs.catalog_id AND frontpages.deleted_at IS NULL) > 0, 1, 0) AS has_coverpage')
            ->selectRaw('IF((SELECT COUNT(*) FROM multimedia WHERE multimedia.catalog_id = view_catalogs.catalog_id AND multimedia.deleted_at IS NULL) > 0, 1, 0) AS has_multimedia');

        // Sorting and pagination
        $totalRecords = $query->count();
        $totalRecordswithFilter = $query->count();

        $records = $query->orderBy($columnName, $columnSortOrder === 'asc' ? 'desc' : 'desc')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        // Format response
        $resultArr = [];
        foreach ($records as $record) {
            $resultArr[] = [
                'id' => $record->catalog_id,
                'material_type' => $record->material_type,
                'pub_year' => htmlspecialchars($record->pub_year, ENT_QUOTES, 'UTF-8'),
                'publisher' => htmlspecialchars($record->publisher, ENT_QUOTES, 'UTF-8'),
                'authors' => htmlspecialchars($record->joint_authors, ENT_QUOTES, 'UTF-8'),
                'title_statement' => htmlspecialchars($record->title_statement, ENT_QUOTES, 'UTF-8'),
                'has_multimedia' => $record->has_multimedia,
                'has_coverpage' => $record->has_coverpage
            ];
        }

        $response = [
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
        ];

        return response()->json($response);


    }

    public function onholdListJson(Request $request)
    {
        $draw = isset($request->filter_search) ? 1 : $request->get('draw');
        $start =  isset($request->filter_search) ? 0 : $request->get("start");
        $rowperpage =  isset($request->filter_search) ? PHP_INT_MAX : $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        if(isset($request->filter_search)) {
            $columnIndex = 0;
            $columnName = 'view_catalogs.id';
            $columnSortOrder = 'desc';
            $searchValue = is_null($search_arr)  ? '' : $search_arr['value'];
            $searchTitle = $request->filter_search;
        } else {
            $columnIndex = is_null($columnIndex_arr) ? 0 : $columnIndex_arr[0]['column']; // Column index
            $columnName = is_null($columnName_arr) ? 'view_catalogs.id' : "view_catalogs." . $columnName_arr[$columnIndex]['data']; // Column name
            $columnName = $columnName == "function" ? "view_catalogs.id" : $columnName;
            $columnSortOrder = is_null($order_arr) ? 'desc' : $order_arr[0]['dir']; // asc or desc
            $searchValue = is_null($search_arr['value']) ? '' : $search_arr['value']; // Search value
            $searchTitle = '';
        }

        // Initialize query
        $query = ViewCatalog::select('*')
            ->where('view_catalogs.deleted_at', null)
            ->where('is_published', 0);

        // Filter conditions
        switch ($searchTitle) {
            case "fulltext-only":
                $query->whereIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('multimedia')
                        ->whereNull('deleted_at');
                });
                break;
            case "coverphoto-only":
                $query->whereIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('frontpages')
                        ->whereNull('deleted_at');
                });
                break;
            case "without-fulltext":
                $query->whereNotIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('multimedia')
                        ->whereNull('deleted_at');
                });
                break;
            case "without-coverphoto":
                $query->whereNotIn('view_catalogs.catalog_id', function ($query) {
                    $query->select('catalog_id')
                        ->from('frontpages')
                        ->whereNull('deleted_at');
                });
                break;
            case "both":
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('multimedia')
                        ->whereRaw('multimedia.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('multimedia.deleted_at');
                })->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('frontpages')
                        ->whereRaw('frontpages.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('frontpages.deleted_at');
                });
                break;
            case "without-both":
                $query->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('multimedia')
                        ->whereRaw('multimedia.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('multimedia.deleted_at');
                })->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('frontpages')
                        ->whereRaw('frontpages.catalog_id = view_catalogs.catalog_id')
                        ->whereNull('frontpages.deleted_at');
                });
                break;
        }

        // Search criteria
        $query->where(function ($query) use ($searchValue) {
            $query->where('view_catalogs.material_type', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.catalog_id', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.accession_number', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.title_statement', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.isbn', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.issn', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.pub_year', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.publisher', 'like', '%' . $searchValue . '%')
                ->orWhere('view_catalogs.joint_authors', 'like', '%' . $searchValue . '%')
                ->orWhere(DB::raw("DATE_FORMAT(view_catalogs.created_at, '%m/%d/%y')"), 'like', '%' . $searchValue . '%');
        });

        // Add binary fields for coverpage and multimedia
        $query->selectRaw('IF((SELECT COUNT(*) FROM frontpages WHERE frontpages.catalog_id = view_catalogs.catalog_id AND frontpages.deleted_at IS NULL) > 0, 1, 0) AS has_coverpage')
            ->selectRaw('IF((SELECT COUNT(*) FROM multimedia WHERE multimedia.catalog_id = view_catalogs.catalog_id AND multimedia.deleted_at IS NULL) > 0, 1, 0) AS has_multimedia');

        // Sorting and pagination
        $totalRecords = $query->count();
        $totalRecordswithFilter = $query->count();

        $records = $query->orderBy($columnName, $columnSortOrder === 'asc' ? 'desc' : 'desc')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        // Format response
        $resultArr = [];
        foreach ($records as $record) {
            $resultArr[] = [
                'id' => $record->catalog_id,
                'material_type' => $record->material_type,
                'pub_year' => htmlspecialchars($record->pub_year, ENT_QUOTES, 'UTF-8'),
                'publisher' => htmlspecialchars($record->publisher, ENT_QUOTES, 'UTF-8'),
                'authors' => htmlspecialchars($record->joint_authors, ENT_QUOTES, 'UTF-8'),
                'title_statement' => htmlspecialchars($record->title_statement, ENT_QUOTES, 'UTF-8'),
                'has_multimedia' => $record->has_multimedia,
                'has_coverpage' => $record->has_coverpage
            ];
        }

        $response = [
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
        ];

        return response()->json($response);


    }

    public function holdAllPublished()
    {
        $user = Auth::user();
        Catalog::where('is_published', 1)
        ->update([
            'is_published' => 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        ViewCatalog::where('is_published', 1)
        ->update([
            'is_published' => 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['success' => true]);
    }

    public function publishAllOnhold(Request $request)
    {
        $user = Auth::user();
        Catalog::where('is_published', 0)
        ->update([
            'is_published' => 1,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        ViewCatalog::where('is_published', 0)
        ->update([
            'is_published' => 1,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['success' => true]);
    }

    public function publishToOpac(Request $request)
    {
        $user = Auth::user();
        Catalog::where('id', $request->catalog_id)
        ->update([
            'is_published' => 1,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        ViewCatalog::where('catalog_id', $request->catalog_id)
        ->update([
            'is_published' => 1,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['success' => true]);
    }

    public function holdViewFromOpac(Request $request)
    {
        $user = Auth::user();
        Catalog::where('id', $request->catalog_id)
        ->update([
            'is_published' => 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        ViewCatalog::where('catalog_id', $request->catalog_id)
        ->update([
            'is_published' => 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);
        return response()->json(['success' => true]);
    }

    public function togglePublishHoldMaterial(Request $request)
    {
        $user = Auth::user();
        Catalog::where('catalog_import_id', $request->catalog_import_id)
        ->update([
            'is_published' => $request->action == 1 ? 1 : 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        ViewCatalog::where('catalog_import_id', $request->catalog_import_id)
        ->update([
            'is_published' => $request->action == 1 ? 1 : 0,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        return view('admin.batch-list');
    }

    public function uploadCoverPhoto(Request $request)
    {
        if($request->hasFile('coverImage')) {

            $uploadedFile = $request->file('coverImage');

            // Cover photo limit: JPG/PNG/WEBP image, max 10 MB.
            if(!in_array(strtolower($uploadedFile->getClientOriginalExtension()), ['jpg','jpeg','png','webp']) || $uploadedFile->getSize() > 10 * 1024 * 1024) {
                $record = Catalog::select('catalogs.*')->where('catalogs.id',$request->catalog_id)->first();
                $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true, 'uploadError' => 'The cover photo must be a JPG, PNG, or WEBP image no larger than 10 MB.']);
            }

            $fileType = $uploadedFile->getClientOriginalExtension();
            $newFileName = 'cp-'. uniqid() . '.' . $fileType;
            $filePath = 'frontpages/' . $newFileName;

            // Store directly under public/frontpages (where getFrontpageResult serves from).
            $uploadedFile->move(public_path('frontpages'), $newFileName);

            $coverPage = Frontpage::where('catalog_id', $request->catalog_id)->where('deleted_at', null)->first();

            if(is_null($coverPage)) {
                Frontpage::create([
                    'catalog_id' =>  $request->catalog_id,
                    'FrontPageLocation' => $filePath,
                    'FrontPageName' => $newFileName,
                    'FrontPageType' => $fileType
                ]);
            } else {
                Frontpage::where('id', $coverPage->id)
                    ->update([
                        'FrontPageLocation' => $filePath,
                        'FrontPageName' => $newFileName,
                        'FrontPageType' => $fileType,
                        'updated_at' => now(),
                    ]);
            }
            $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 

            $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
            $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);

        }
        $record = Catalog::select('catalogs.*')
        ->where('catalogs.id',$request->catalog_id)
        ->first(); 

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title]);
          
    }

    public function destroyCoverPhoto(Request $request)
    {
        
        $coverPage = Frontpage::where('catalog_id', $request->catalog_id)->whereNull('deleted_at')->first();
        Frontpage::where('id', $coverPage->id)
            ->update([
            'deleted_at' => now()
        ]);
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
        ->where('catalogs.id',$request->catalog_id)
        ->first(); 

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);
    }

    public function uploadFullText(Request $request)
    {
        if($request->hasFile('fullText')) {

            $uploadedFile = $request->file('fullText');

            // Full-text upload limit: PDF only, max 100 MB.
            if(strtolower($uploadedFile->getClientOriginalExtension()) !== 'pdf' || $uploadedFile->getSize() > 100 * 1024 * 1024) {
                $record = Catalog::select('catalogs.*')->where('catalogs.id',$request->catalog_id)->first();
                $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
                return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true, 'uploadError' => 'The full-text file must be a PDF no larger than 100 MB.']);
            }

            $fileType = $uploadedFile->getClientOriginalExtension();
            $newFileName = 'ft-'. uniqid() . '.' . $fileType;
            $filePath = 'pdf/' . $newFileName; 

            $uploadedFile->storeAs('public', $filePath);

            $coverPage = Multimedia::where('catalog_id', $request->catalog_id)->where('deleted_at', null)->first();

            if(is_null($coverPage)) {
                Multimedia::create([
                    'catalog_id' =>  $request->catalog_id,
                    'FileLocation' => $filePath,
                    'FileName' => $newFileName,
                    'FileType' => $fileType,
                    'Extension' => ".$fileType"
                ]);
            } else {
                Multimedia::where('id', $coverPage->id)
                    ->update([
                        'FileLocation' => $filePath,
                        'FileName' => $newFileName,
                        'FileType' => $fileType,
                        'Extension' => ".$fileType",
                        'updated_at' => now(),
                    ]);
            }
            $record = Catalog::select('catalogs.*')
                ->where('catalogs.id',$request->catalog_id)
                ->first(); 

            $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
            $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);

        }
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        
        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title]);

    }

    public function destroyFullText(Request $request)
    {
        $ft = Multimedia::where('catalog_id', $request->catalog_id)->whereNull('deleted_at')->first();
        Multimedia::where('id', $ft->id)
            ->update([
            'deleted_at' => now()
        ]);
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'uploadStatus' => true]);
    }

    public function addCopy(Request $request)
    {

        if(count($request->circulation_number) > 0) {
            for($z=0; $z < count($request->circulation_number); $z++) {
                $checkIfAlreadyAdded = Holdingcopy::where('circulation_number', $request->circulation_number[$z])->first();
                if(is_null($checkIfAlreadyAdded)) {
                
                    Holdingcopy::create([
                        'catalog_id' => $request->catalog_id,
                        'title' => $request->title,
                        'circulation_number' => $request->circulation_number[$z],
                        'source' => $request->source[$z],
                        'status' => $request->status[$z],
                        'location' => $request->location[$z],
                    ]);
                }
            }
            
        }

        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first(); 
        $totalCopies = Holdingcopy::where('catalog_id', $request->catalog_id)->count();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'totalCopies' => $totalCopies, 'copyUpdate' => true]);

    }

    public function copyListJson(Request $request)
    {

        $searchValue = is_null($request->search_value) ? "" : $request->search_value; // Search value
        $totalRecords = Holdingcopy::where('copies.catalog_id', $request->catalog_id)->count();

        // dd($totalRecords);
        $totalRecordswithFilter = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.created_at', 'like', '%' . $searchValue . '%');
        })
        ->where('copies.catalog_id', $request->catalog_id)
        ->count();

        // Fetch records
        $records = Holdingcopy::select('copies.*', 'copies_status.name as status_name', 'copies_status.order_no as status_order_no')
        ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
        ->where(function ($query) use ($searchValue) {
            $query->where('circulation_number', 'like', '%' . $searchValue . '%')
                ->orWhere('copies_status.name', 'like', '%' . $searchValue . '%')
                ->orWhere('location', 'like', '%' . $searchValue . '%')
                ->orWhere('source', 'like', '%' . $searchValue . '%')
                ->orWhere('copies.created_at', 'like', '%' . $searchValue . '%');
        })
        ->where('copies.catalog_id', $request->catalog_id)
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
                "created_at" => $record->created_at,
            );
        }

        $response = array(
            "draw" => intval(1),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        );
 
        return response()->json($response);


    }

    public function getCopyDetailsJson($id)
    {

        $copy = Holdingcopy::where('id', $id)->first();
        $response = array(
            "copy_id" => $copy->id,
            "circulation_number" => $copy->circulation_number,
            "status" => $copy->status,
            "source" => $copy->source,
            "location" => $copy->location,
         );
 
         return response()->json($response); 
    }

    public function updateCopy(Request $request)
    {
        Holdingcopy::where('id',$request->copy_id)
            ->update([
                'circulation_number' => $request->circulation_number,
                'source' => $request->source,
                'location' => $request->location,
                'status' => $request->status,
            ]);
        $coverPage = Frontpage::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',$request->catalog_id)->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',$request->catalog_id)
            ->first();
        $totalCopies = Holdingcopy::where('catalog_id', $request->catalog_id)->count();

        return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => $request->title, 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
    }

    public function destroyCopy($id) 
    {
        $coverPage = Frontpage::where('catalog_id',request('catalog_id'))->whereNull('deleted_at')->first();
        $fullText = Multimedia::where('catalog_id',request('catalog_id'))->whereNull('deleted_at')->first();
        $record = Catalog::select('catalogs.*')
            ->where('catalogs.id',request('catalog_id'))
            ->first();

        try {
            Holdingcopy::destroy($id);
            $totalCopies = Holdingcopy::where('catalog_id', request('catalog_id'))->count();
            return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => request('title'), 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
        } catch (\Exception $e) {
            return view('admin.catalog-view-record', ['record' => $record, 'fullText' => $fullText, 'coverPage' => $coverPage, 'title' => request('title'), 'totalCopies' => $totalCopies, 'copyUpdate' => true]);
        }

    }

    public function getTaskProgress()
    {
        // Get the current progress from the database
        $task = CatalogImportProgress::where('id', 1)->first(); // Find the appropriate task
        // dd($task);
        if ($task) {
            return response()->json(['progress' => $task->progress, 'total_records' => $task->total_records]);
        }
        
        return response()->json(['progress' => 1, 'total_records' => 1]); // Default to 0 if no task found
    }
}
