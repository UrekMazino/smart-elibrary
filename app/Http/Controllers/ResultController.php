<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use MeiliSearch\Client;
use App;
use Auth;

class ResultController extends Controller
{

    public function index(Request $request)
    {
        // $client = new Client('http://127.0.0.1:7700', 'pwd123');
        // $client->index('view_holdings')->updateFilterableAttributes(['consortia','title_statement','summary','material_type_id']);
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {  
            return redirect('/admin/dashboard');
        }

        if($request['series_statement_id'] != 0)
        {
            $result = App\Models\ViewCatalog::where('series_statement_id',$request['series_statement_id'])->orderBy('pub_year','DESC')->where('deleted_at',null)->where('is_published', 1)->paginate(12);
            // $result->index('view_holdings')->where('series_statement_id',$request['series_statement_id'])->paginate(12);
        }
        else
        {
            if(false)
            {
                $searhType = $request['seachtype'];
                if(count($request['seachtype']) == '1')
                {
                    if($request['seachtype'][0] == 0)
                        if($request['year1'] == $request['year2'])
                            if(isset($request['query']) || $request['query'] == '')
                                $result = App\Models\ViewCatalog::search($request['query'])->where('deleted_at',null)->where('pub_year',$request['year1'] )->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                            else
                                $result = App\Models\ViewCatalog::where('deleted_at',null)->where('pub_year',$request['year1'] )->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        else
                            if(isset($request['query']) || $request['query'] == '')
                                $result = App\Models\ViewCatalog::search($request['query'])->where('deleted_at',null)->where('pub_year','>=',$request['year1'])->where('pub_year','<=',$request['year2'])->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                            else
                                $result = App\Models\ViewCatalog::where('deleted_at',null)->where('pub_year','>=',$request['year1'])->where('pub_year','<=',$request['year2'])->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    else
                        if($request['year1'] == $request['year2'])
                            if(isset($request['query']) || $request['query'] == '')
                                $result = App\Models\ViewCatalog::search($request['query'])->where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('pub_year',$request['year1'] )->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                            else
                                $result = App\Models\ViewCatalog::where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('pub_year',$request['year1'] )->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        else
                            if(isset($request['query']) || $request['query'] == '')
                                $result = App\Models\ViewCatalog::search($request['query'])->where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('pub_year','>=',$request['year1'])->where('pub_year','<=',$request['year2'])->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                            else
                                $result = App\Models\ViewCatalog::where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('pub_year','>=',$request['year1'])->where('pub_year','<=',$request['year2'])->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                }
                else
                {
                    if(isset($request['query']) || $request['query'] == '')
                        $result = App\Models\ViewCatalog::search($request['query'])->whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    else
                        $result = App\Models\ViewCatalog::whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    // $result->index('view_holdings')->search($request['query'])->whereIn('material_type_id',$request['seachtype'])->paginate(12);
                }
            }
            elseif(isset($request['seachtype']) && isset($request['year1']))
            {
                $searhType = $request['seachtype'];
                if(count($request['seachtype']) == '1')
                {
                    if($request['seachtype'][0] == 0)
                        if(isset($request['query']) || $request['query'] == '')
                            $result = App\Models\ViewCatalog::search($request['query'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        else
                            $result = App\Models\ViewCatalog::where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        // $result->index('view_holdings')->search($request['query'])->paginate(12);
                    else
                        if(isset($request['query']) || $request['query'] == '')
                            $result = App\Models\ViewCatalog::search($request['query'])->where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        else
                            $result = App\Models\ViewCatalog::where('material_type_id',$request['seachtype'][0])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        // $result->index('view_holdings')->search($request['query'])->where('material_type_id',$request['seachtype'][0])->paginate(12);
                }
                else
                {
                    if(isset($request['query']) || $request['query'] == '')
                        $result = App\Models\ViewCatalog::search($request['query'])->whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    else
                        $result = App\Models\ViewCatalog::whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    // $result->index('view_holdings')->search($request['query'])->whereIn('material_type_id',$request['seachtype'])->paginate(12);
                }
            }
            else
            {

                // dd($request['seachtype']);
                $searhType = isset($request['seachtype']) ? $request['seachtype'] : ["0"];
                if(isset($request['query']))
                {
                    $ctr = count($request['seachtype']);
                    if($ctr == 1)
                    {
                        if($request['seachtype'][0] == '0')
                        {
                            $result = App\Models\ViewCatalog::search($request['query'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        }
                        else 
                        {
                            $result = App\Models\ViewCatalog::search($request['query'])->whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                        }

                    }
                    else
                    {
                        $result = App\Models\ViewCatalog::search($request['query'])->whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                    }
             
                }                    
                elseif($request['query'] == '')
                {
                    $result = App\Models\ViewCatalog::whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                }
                else
                {
                    $result = App\Models\ViewCatalog::search($request['query'])->whereIn('material_type_id',$request['seachtype'])->where('deleted_at',null)->where('is_published', 1)->orderBy('pub_year','DESC')->paginate(12);
                }
                    
            }
        }

        if($request['layout'] == 'list')
            return view('opac.search-result-list',['result' => $result,'layout' => 'list', 'search_type' => $searhType ]);
        else
            return view('opac.search-result-grid',['result' => $result,'layout' => 'grid', 'search_type' => $searhType ]);

    }

    public function search()
    {
        $result = App\Models\ViewCatalog::search('Banana')->paginate(10);
        return $result;
    }
}
