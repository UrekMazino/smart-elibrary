<?php

namespace App\Http\Controllers;
use  App\Models\News;
use Illuminate\Http\Request;
use App;
use Auth;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function index()
    {
        return view('admin.news');
    }

    public function create()
    {
        return view('admin.news-create');
    }

    public function edit($id)
    {
        $article = News::where('id', $id)->first();
        
        return view('admin.news-edit', ['article' => $article]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'news_title' => 'required|string|max:255',
            'news_body' => 'required|string',
            'news_cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);               
        $news = new News;

        if ($request->hasFile('news_cover')) {
            //COVER PHOTO -> store under public/news_cover (where it is served from) + thumbnail
            $file = $request->file('news_cover');
            $coverName = 'news-'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('news_cover'), $coverName);
            $news->news_cover = 'news_cover/'.$coverName;
            makeThumbnail(public_path('news_cover/'.$coverName), public_path('thumb/'.$coverName));
        }

        $news->news_title = request()->news_title;
        $news->redirect_url = request()->news_url;
        $news->news_body = request()->news_body;
        $news->featured = request()->is_featured ? 'Yes' : 'No';
        $news->created_by = Auth::user()->name;
        $news->save();

        return redirect('/admin/news');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'news_title' => 'required|string|max:255',
            'news_body' => 'required|string',
            'news_cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $article = News::findOrFail($id);
        $article->news_title = request()->news_title;
        $article->redirect_url = request()->news_url;
        $article->news_body = request()->news_body;
        $article->featured = request()->is_featured ? 'Yes' : 'No';
        $article->created_by = Auth::user()->name;
        
        // Check if a new cover image has been uploaded
        if ($request->hasFile('news_cover')) {
            // Store under public/news_cover (where it is served from) + thumbnail
            $file = $request->file('news_cover');
            $coverName = 'news-'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('news_cover'), $coverName);
            $article->news_cover = 'news_cover/'.$coverName;
            makeThumbnail(public_path('news_cover/'.$coverName), public_path('thumb/'.$coverName));
        }

        $article->save();

        return redirect('/admin/news')->with('success', 'News article updated successfully.');
    }
    

    public function destroy(Request $request)
    {
        $user = Auth::user();
        News::where('id', $request->id)
            ->update([
                    'deleted_at' => now(),
                ]);
      
        return redirect('/admin/news');
    }

    public function json(Request $request)
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
        $totalRecords = News::whereNull('deleted_at')->count();
        $totalRecordswithFilter = News::select('count(*) as allcount')->where('news_title', 'like', '%' .$searchValue . '%')->whereNull('deleted_at')->count();

        // Fetch records
        $records = News::orderBy($columnName,$columnSortOrder)
            ->where('news_title', 'like', '%' .$searchValue . '%')
            ->whereNull('deleted_at')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach($records as $record){
            $id = $record->id;
            $news_title = $record->news_title;
            $featured = $record->featured;
            $created_by = $record->created_by;
            $created_at = $record->created_at;
            
 
            $data_arr[] = array(
                "id" => $id,
                "news_title" => $news_title,
                "featured" => $featured,
                "created_by" => $created_by,
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
}
