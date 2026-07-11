<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MeiliSearch\Client;
use App;
use Auth;

class AcquisitionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function index()
    {
        return view('admin.catalog');
    }

    public function uncatalog()
    {
        return view('admin.uncatalog');
    }

    public function catalogjson(Request $request)
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
        $totalRecords = App\Models\ViewCatalog::count();
        $totalRecordswithFilter = App\Models\ViewCatalog::select('count(*) as allcount')
        ->where('title_statement', 'like', '%' .$searchValue . '%')
        ->Orwhere('joint_authors', 'like', '%' .$searchValue . '%')
        ->orWhere('catalog_id', 'like', '%' .$searchValue . '%')
        ->whereNotNull('catalog_date')->count();

        // Fetch records
        $records = App\Models\ViewCatalog::orderBy($columnName,$columnSortOrder)
            ->where('title_statement', 'like', '%' .$searchValue . '%')
            ->orWhere('joint_authors', 'like', '%' .$searchValue . '%')
            ->orWhere('catalog_id', 'like', '%' .$searchValue . '%')
            ->whereNotNull('catalog_date')
            // ->select('catalog_id','title_statement','material','joint_authors','id','catalog_date','catalog_status','CreatedBy','created_at','consortia')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;
            $catalog_id = $record->catalog_id;
            $title_statement = formatTitle1($record->title_statement);
            $material = $record->material;
            $joint_authors = $record->joint_authors;
            // $catalog_date = $record->catalog_date;
            // $catalog_status = $record->catalog_status;
            $copies = $record->copies;
            $CreatedBy = $record->CreatedBy;
            $created_at = $record->created_at;
            // $consortia = $record->consortia;
            
 
            $data_arr[] = array(
                "id" => $id,
                "catalog_id" => $catalog_id,
                "title_statement" => $title_statement,
                "material" => $material,
                "joint_authors" => $joint_authors,
                // "catalog_date" => $catalog_date,
                // "catalog_status" => $catalog_status,
                "copies" => $copies,
                "CreatedBy" => $CreatedBy,
                "created_at" => $created_at,
                // "consortia" => $consortia,
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

    public function uncatalogjson(Request $request)
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
        $totalRecords = App\Models\View_holding_uncatalog::count();
        $totalRecordswithFilter = App\Models\View_holding_uncatalog::select('count(*) as allcount')
        ->where('title_statement', 'like', '%' .$searchValue . '%')
        ->count();

        // Fetch records
        $records = App\Models\View_holding_uncatalog::orderBy($columnName,$columnSortOrder)
            ->where('title_statement', 'like', '%' .$searchValue . '%')
            // ->select('catalog_id','title_statement','material','joint_authors','id','catalog_date','catalog_status','CreatedBy','created_at','consortia')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;
            $catalog_id = $record->catalog_id;
            $title_statement = formatTitle1($record->title_statement);
            $material = $record->material;
            $joint_authors = $record->joint_authors;
            //$catalog_date = $record->catalog_date;
            //$catalog_status = $record->catalog_status;
            $copies = $record->copies;
            $CreatedBy = $record->CreatedBy;
            $created_at = $record->created_at;
            //$consortia = $record->consortia;
            
 
            $data_arr[] = array(
                "id" => $id,
                "catalog_id" => $catalog_id,
                "title_statement" => $title_statement,
                "material" => $material,
                "joint_authors" => $joint_authors,
                //"catalog_date" => $catalog_date,
                //"catalog_status" => $catalog_status,
                "copies" => $copies,
                "CreatedBy" => $CreatedBy,
                "created_at" => $created_at,
                //"consortia" => $consortia,
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

    public function index2()
    {
        return view('admin.acquisition-new');
    }

    public function create(Request $request)
    {
        //SAVING HOLDINGS ------------------->
        $holding = new App\Models\Holding;

        //REQUIRED FIELD
        $holding->material_type_id  = request()->material_id;
        $holding->title_statement  = request()->title_statement;
        
        $holding->CreatedBy = Auth::user()->name;
        $holding->copy_number  = request()->copy_number;
        $holding->cost_price = request()->cost_price;

        //OPTIONAL
        $holding->ISSN  = request()->issn;
        $holding->ISBN  = request()->isbn;
        $holding->pub_url  = request()->pub_url;
        //$holding->title_remainder  = request()->issn;
        $holding->classifications_system  = request()->classifications_system;
        $holding->classification_number  = request()->classification_number;
        $holding->author_number  = request()->author_number;
        $holding->copyright_date  = date("Y-m-d",strtotime(request()->copyright_date));
        $holding->title_remainder  = request()->title_remainder;
        $holding->statement_responsibility  = request()->statement_responsibility;
        $holding->edition_statement  = request()->edition_statement;
        $holding->edition_statement_remainder  = request()->edition_statement_remainder;
        $holding->imprint  = request()->imprint;
        $holding->type_of_imprint  = request()->type_of_imprint;
        $holding->edition_statement_remainder  = request()->edition_statement_remainder;
        $holding->publication_place  = request()->publication_place;
        $holding->publisher  = request()->publisher;
        $holding->publication_date  = request()->publication_date;
        $holding->extent  = request()->extent;
        $holding->other_physical  = request()->other_physical;
        $holding->dimension  = request()->dimension;
        $holding->frequency  = request()->frequency;
        $holding->volume  = request()->volume;
        $holding->issue_number  = request()->issue_number;
        $holding->series_statement_id  = request()->series_statement_id;
        $holding->bibliography  = request()->bibliography;
        $holding->date_acquired  = date("Y-m-d",strtotime(request()->date_acquired));
        $holding->user_restriction  = request()->user_restriction;
        $holding->item_status  = request()->item_status;
        $holding->temporary_location  = request()->temporary_location;
        $holding->nonpublic_note  = request()->nonpublic_note;
        $holding->summary = request()->summary;
        $holding->general_note = request()->general_note;
        $holding->consortia_id   = 16;
        
        $holding->save();

        // SAVING HOLDINGS COPY ------------------->
        if(count($request->accession_number) > 0) {
            foreach(request()->accession_number as $index => $accNo) {
                $copy = new App\Models\Holdingcopy;
                $copy->catalog_id = $holding->id;
                $copy->accession_number = $request->accession_number[$index];
                $copy->circulation_number = $request->circulation_number[$index];
                $copy->title = $request->title_statement;
                $copy->status = 4; 
                // $copy->location = $request->location[$index];
                $copy->source = $request->source[$index];
                $copy->save();
            }
        }

        //SAVING AUTHOR ------------------->
        if(isset(request()->author_type))
        {
            $author_type = request()->author_type;
        }
        else
        {
            $author_type = 100;
        }

            $author = new App\Models\Author;
            $author->catalog_id  = $holding->id;
            $author->AuthorTag = request()->author_type;
            $author->author_name2 = request()->author_name;
            $author->CreatedBy = Auth::user()->name;
            $author->numeration = request()->numeration;
            $author->title_and_words = request()->title_and_words;
            $author->relator_term = request()->relator_term;
            $author->date_associated = date("Y-m-d",strtotime(request()->date_associated));
            $author->fuller_form = request()->fuller_form;
            $author->affiliation = request()->affiliation;
            $author->save();
        

        //SAVING ADDITIONAL AUTHOR ------------------->
        if(isset(request()->add_author_check))
        {
            if(isset(request()->author_name2))
            {
                $author_type2 = request()->author_name2;
            }
            else
            {
                $author_type = 700;
            }

            $author = new App\Models\Author;
            $author->catalog_id  = $holding->id;
            $author->AuthorTag = $author_type2;
            $author->author_name2 = request()->author_name2;
            $author->CreatedBy = Auth::user()->name;
            $author->numeration = request()->numeration2;
            $author->title_and_words = request()->title_and_words2;
            $author->relator_term = request()->relator_term2;
            $author->date_associated = date("Y-m-d",strtotime(request()->date_associated2));
            $author->fuller_form = request()->fuller_form2;
            $author->affiliation = request()->affiliation2;
            $author->save();
        }

        //CORPORATE AUTHOR
        if(isset(request()->cauthor_type))
        {
            $cauthor = new App\Models\Corporate_author;
            $cauthor->catalog_id  = $holding->id;
            $cauthor->author_type = request()->cauthor_type;
            $cauthor->author_name = request()->cauthor_name;
            $cauthor->subordinate_unit = request()->subordinate_unit;
            $cauthor->location_meeting = request()->location_meeting;
            $cauthor->date_of_meeting = date("Y-m-d",strtotime(request()->date_of_meeting));
            $cauthor->number_of_part = request()->number_of_part;
            $cauthor->save();
        }

        //ADD CORPORATE AUTHOR
        if(isset(request()->cauthor_type2))
        {
            $cauthor = new App\Models\Corporate_author;
            $cauthor->catalog_id  = $holding->id;
            $cauthor->author_type = request()->cauthor_type2;
            $cauthor->author_name = request()->cauthor_name2;
            $cauthor->subordinate_unit = request()->subordinate_unit2;
            $cauthor->location_meeting = request()->location_meeting2;
            $cauthor->date_of_meeting = request()->date_of_meeting2;
            $cauthor->number_of_part = request()->number_of_part2;
            $cauthor->save();
        }

        //COVER PHOTO
        if(isset(request()->formCover))
        {
            $coverName = 'cp-'.uniqid().'.'.request()->file('formCover')->getClientOriginalExtension();
            request()->file('formCover')->move(public_path('frontpages'), $coverName);
            $frontpage_path = 'frontpages/'.$coverName;

            $frontpage = new App\Models\Frontpage;
            $frontpage->catalog_id  = $holding->id;
            $frontpage->FrontPageLocation = $frontpage_path;
            $frontpage->save();
        }
        


        //PDF
        if(isset(request()->formPDF))
        {
            $pdf_path = request()->file('formPDF')->store(
                'pdf/upload', 'public'
            );

            $pdf = new App\Models\Multimedia;
            $pdf->catalog_id  = $holding->id;
            $pdf->FileLocation = $pdf_path;
            $pdf->save();
        }

        return redirect('admin/catalog');
    }

    public function editview()
    {
        //return request()->catalog_id;

        $holdings = App\Models\Holding::where('id',request()->catalog_id)->first();

        // return $holdings;
        //return $holdings['catalog_id'];

        $authors = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[100,103])->first();
        $authors2 = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[700,703])->first();

        
        if(!isset($authors))
            $authors = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);
        
        if(!isset($authors2))
            $authors2 = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);

        $cauthors = App\Models\Corporate_author::where('catalog_id',$holdings['catalog_id'])->whereBetween('author_type',[110,113])->first();
        $cauthors2 = App\Models\Corporate_author::where('catalog_id',$holdings['catalog_id'])->whereBetween('author_type',[710,713])->first();

        if(!isset($cauthors))
        $cauthors = array("id" => null,"author_name" => null,"subordinate_unit" => null,"location_meeting" => null,"date_of_meeting" => null,"number_of_part" => null,"author_type" => null);
    
        if(!isset($cauthors2))
        $cauthors2 = array("id" => null,"author_name" => null,"subordinate_unit" => null,"location_meeting" => null,"date_of_meeting" => null,"number_of_part" => null,"author_type" => null);

        
        $data = [
            "holdings" => $holdings,
            "authors" => $authors,
            "authors2" => $authors2,
            "cauthors" => $cauthors,
            "cauthors2" => $cauthors2,
        ];
        
        return view('admin.acquisition-edit')->with("data",$data);
    }

    public function editviewGet($id)
    {
        //return $id;

        $holdings = App\Models\Holding::where('id',$id)->first();
        $authors = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[100,103])->first();
        $authors2 = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[700,703])->first();

        //return $holdings['catalog_id'];

        if(!isset($authors))
            $authors = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);

        if(!isset($authors2))
            $authors2 = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);

        $cauthors = App\Models\Corporate_author::where('catalog_id',$holdings['catalog_id'])->whereBetween('author_type',[110,113])->first();
        
        $cauthors2 = App\Models\Corporate_author::where('catalog_id',$holdings['catalog_id'])->whereBetween('author_type',[710,713])->first();

        if(!isset($cauthors))
        $cauthors = array("id" => null,"author_name" => null,"subordinate_unit" => null,"location_meeting" => null,"date_of_meeting" => null,"number_of_part" => null,"author_type" => null);
    
        if(!isset($cauthors2))
        $cauthors2 = array("id" => null,"author_name" => null,"subordinate_unit" => null,"location_meeting" => null,"date_of_meeting" => null,"number_of_part" => null,"author_type" => null);
        
        $data = [
            "holdings" => $holdings,
            "authors" => $authors,
            "authors2" => $authors2,
            "cauthors" => $cauthors,
            "cauthors2" => $cauthors2,
        ];
        return view('admin.acquisition-edit')->with("data",$data);
    }

    public function delete()
    {
        //return request()->action_catalog_id;
        App\Models\Holding::where('id',request()->action_catalog_id)->delete();
        return redirect(request()->url);
    }

    public function setas()
    {
        App\Models\Holding::where('id',request()->action_catalog_id)
        ->update([
                    'catalog_date' => request()->catalog_date
                ]);
        return redirect(request()->url);
    }

    public function update()
    {
        $holdings = App\Models\Holding::where('id',request()->catalog_id)->first();
        $acquisition = App\Models\Holding::where('id',request()->catalog_id)
        ->update([

            //TAB 1
            "material_type_id" => request()->material_id,
            "ISSN" => request()->issn,
            "ISBN" => request()->isbn,
            "classifications_system" => request()->classifications_system,
            "author_number" => request()->author_number,
            "copyright_date" => date("Y-m-d",strtotime(request()->copyright_date)),            
            "title_statement" => request()->title_statement,
            "title_remainder" => request()->title_remainder,
            "statement_responsibility" => request()->statement_responsibility,
            "edition_statement" => request()->edition_statement,
            "edition_statement_remainder" => request()->edition_statement_remainder,
            "imprint" => request()->imprint,
            "type_of_imprint" => request()->type_of_imprint,

            //TAB 2
            "publication_place" => request()->publication_place,
            "publisher" => request()->publisher,
            "publication_date" => request()->publication_date,
            "extent" => request()->extent,
            "other_physical" => request()->other_physical,
            "dimension" => request()->dimension,
            "frequency" => request()->frequency,
            "volume" => request()->volume,
            "issue_number" => request()->issue_number,
            "series_statement_id" => request()->series_statement_id,
            "bibliography" => request()->bibliography,
            "accession_number" => request()->accession_number,
            "circulation_number" => request()->circulation_number,
            "cost_price" => request()->cost_price,
            "date_acquired" => date("Y-m-d",strtotime(request()->date_acquired)),
            "acquisition_modes" => request()->acquisition_modes,
            "user_restriction" => request()->user_restriction,
            "item_status" => request()->item_status,
            "temporary_location" => request()->temporary_location,
            "copy_number" => request()->copy_number,
            "nonpublic_note" => request()->nonpublic_note,
            "copies" => request()->no_of_copy,
            "Summary" => request()->pub_summary,
            "general_note" => request()->general_note, 
        ]);

        //TAB 3
        //AUTHOR
        if(isset(request()->author_id))
        {
            $acquisition = App\Models\Author::where('id', request()->author_id)
            ->update([
                "AuthorTag" => request()->author_type,
                "author_name2" => request()->author_name,
                "numeration" => request()->numeration,
                "title_and_words" => request()->title_and_words,
                "relator_term" => request()->relator_term,            
                "date_associated" => date("Y-m-d",strtotime(request()->date_associated)),
                "fuller_form" => request()->fuller_form,
                "affiliation" => request()->affiliation,
            ]);
        }
        else
        {
            //AUTHOR
            if(isset(request()->author_type))
            {
                $author_type = request()->author_type;
            }
            else
            {
                $author_type = 100;
            }

            $author = new App\Models\Author;
            $author->catalog_id  = $holdings['catalog_id'];
            $author->AuthorTag = $author_type;
            $author->author_name2 = request()->author_name;
            $author->CreatedBy = Auth::user()->name;
            $author->numeration = request()->numeration;
            $author->title_and_words = request()->title_and_words;
            $author->relator_term = request()->relator_term;
            $author->date_associated = date("Y-m-d",strtotime(request()->date_associated));
            $author->fuller_form = request()->fuller_form;
            $author->affiliation = request()->affiliation;
            $author->save();
        }

        if(isset(request()->author2_id))
        {
            if(isset(request()->author_type2))
            {
                $author_type2 = request()->author_type2;
            }
            else
            {
                $author_type2 = 100;
            }

            $acquisition = App\Models\Author::where('id', request()->author2_id)
            ->update([
                "AuthorTag" => request()->author_type2,
                "author_name2" => $author_name2,
                "numeration" => request()->numeration2,
                "title_and_words" => request()->title_and_words2,
                "relator_term" => request()->relator_term2,            
                "date_associated" => date("Y-m-d",strtotime(request()->date_associated2)),
                "fuller_form" => request()->fuller_form2,
                "affiliation" => request()->affiliation2,
            ]);
        }
        else
        {
            //AUTHOR
            if(isset(request()->author_type2))
            {
                $author_type2 = request()->author_type2;
            }
            else
            {
                $author_type2 = 100;
            }

            $author = new App\Models\Author;
            $author->catalog_id  = $holdings['catalog_id'];
            $author->AuthorTag = $author_type2;
            $author->author_name2 = request()->author_name2;
            $author->CreatedBy = Auth::user()->name;
            $author->numeration = request()->numeration2;
            $author->title_and_words = request()->title_and_words2;
            $author->relator_term = request()->relator_term2;
            $author->date_associated = date("Y-m-d",strtotime(request()->date_associated2));
            $author->fuller_form = request()->fuller_form2;
            $author->affiliation = request()->affiliation2;
            $author->save();
        }

        //TAB 4
        //CORPORATE AUTHOR
        if(isset(request()->cauthor_id))
        {
            if(isset(request()->cauthor_type))
            {
                $cauthor_type = request()->cauthor_type;
            }
            else
            {
                $cauthor_type = 110;
            }
            
            $cauthor = App\Models\Corporate_author::where('id', request()->cauthor_id)
            ->update([
                "author_type" => request()->cauthor_type,
                "author_name" => request()->cauthor_name,
                "subordinate_unit" => request()->subordinate_unit,
                "location_meeting" => request()->location_meeting,         
                "date_of_meeting" => date("Y-m-d",strtotime(request()->date_of_meeting)),
                "number_of_part" => request()->number_of_part,
            ]);
        }
        else
        {
            if(isset(request()->cauthor_type))
            {
                $cauthor_type = request()->cauthor_type;
            }
            else
            {
                $cauthor_type = 110;
            }
            $cauthor = new App\Models\Corporate_author;
            $cauthor->catalog_id  = $holdings['catalog_id'];
            $cauthor->author_type = $cauthor_type;
            $cauthor->author_name = request()->cauthor_name;
            $cauthor->subordinate_unit = request()->subordinate_unit;
            $cauthor->location_meeting = request()->location_meeting;
            $cauthor->date_of_meeting = date("Y-m-d",strtotime(request()->date_of_meeting));
            $cauthor->number_of_part = request()->number_of_part;
            $cauthor->save();
        }

        if(isset(request()->cauthor2_id))
        {
            if(isset(request()->cauthor_type2))
            {
                $cauthor_type2 = request()->cauthor_type2;
            }
            else
            {
                $cauthor_type2 = 710;
            }

            $cauthor = App\Models\Corporate_author::where('id', request()->cauthor_id2)
            ->update([
                "author_type" => $cauthor_type2,
                "author_name" => request()->cauthor_name2,
                "subordinate_unit" => request()->subordinate_unit2,
                "location_meeting" => request()->location_meeting2,         
                "date_of_meeting" => date("Y-m-d",strtotime(request()->date_of_meeting2)),
                "number_of_part" => request()->number_of_part2,
            ]);
        }
        else
        {
            if(isset(request()->cauthor_type2))
            {
                $cauthor_type2 = request()->cauthor_type2;
            }
            else
            {
                $cauthor_type2 = 710;
            }
            $cauthor = new App\Models\Corporate_author;
            $cauthor->catalog_id  = $holdings['catalog_id'];
            $cauthor->author_type = $cauthor_type2;
            $cauthor->author_name = request()->cauthor_name2;
            $cauthor->subordinate_unit = request()->subordinate_unit2;
            $cauthor->location_meeting = request()->location_meeting2;
            $cauthor->date_of_meeting = date("Y-m-d",strtotime(request()->date_of_meeting2));
            $cauthor->number_of_part = request()->number_of_part2;
            $cauthor->save();
        }

         //COVER PHOTO
         if(isset(request()->formCover))
         {
            //DELETE PREVIOUS RECORD
            App\Models\Frontpage::where('catalog_id',$holdings['catalog_id'])->delete();

             $coverName = 'cp-'.uniqid().'.'.request()->file('formCover')->getClientOriginalExtension();
             request()->file('formCover')->move(public_path('frontpages'), $coverName);
             $frontpage_path = 'frontpages/'.$coverName;

     
             $frontpage = new App\Models\Frontpage;
             $frontpage->catalog_id  = $holdings['catalog_id'];
             $frontpage->FrontPageLocation = $frontpage_path;
             $frontpage->save();
         }
         
 
         //PDF
         if(isset(request()->formPDF))
         {
            //DELETE PREVIOUS RECORD
            App\Models\Multimedia::where('catalog_id',$holdings['catalog_id'])->delete();

             $pdf_path = request()->file('formPDF')->store(
                 'pdf/upload', 'public'
             );
 
             $pdf = new App\Models\Multimedia;
             $pdf->catalog_id  = $holdings['catalog_id'];
             $pdf->FileLocation = $pdf_path;
             $pdf->save();
         }


        $holdings = App\Models\Holding::where('id',request()->catalog_id)->first();
        $authors = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[100,103])->first();
        $authors2 = App\Models\Author::where('catalog_id',$holdings['catalog_id'])->whereBetween('AuthorTag',[700,703])->first();

        if(!isset($authors))
            $authors = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);
        
        if(!isset($authors2))
            $authors2 = array("id" => null,"author_name2" => null,"numeration" => null,"title_and_words" => null,"relator_term" => null,"date_associated" => null,"fuller_form" => null,"affiliation" => null,"AuthorTag" => null);
        
        $data = [
            "holdings" => $holdings,
            "authors" => $authors,
        ];

        return redirect('admin/edit-acquisition-view-get/'.request()->catalog_id)->with("data",$data);
    }

}