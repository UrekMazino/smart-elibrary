<?php

function maxTitle($title,$max = 115)
{
	if(strlen($title) >= $max)
		return substr($title, 0, $max).'...'; 
	else
		return $title;
}

function formatTitle1($title)
{
	$title = explode('$c',$title);

	if(isset($title[1]))
		return $title[0].' '.$title[1];
	else
		return $title[0];
}

function formatTitle($title)
{
	$title_fix = explode('$c',$title);

	if(isset($title_fix[1]))
		return $title_fix[0].'<br/><small>'.$title_fix[1].'</small>';
	else
		return $title;

}

function formatText($txt)
{
	// $txt = substr($txt, 7);

	return $txt;
}

function getRandomImage()
{
	$thumb = App\Models\Thumbnail::all()->random(1)->first();
	return $thumb['filename'];
}

function getRandomRating()
{
	$rate = rand(0,5);


	$txt = '<span class="rating">';

	$nostar = 5 - $rate;


	//STARS
	for ($i=0; $i < $rate; $i++) { 
		$txt .= '<i class="icon_star voted"></i>';
	}

	//NO STARS
	for ($i=0; $i < $nostar; $i++) { 
		$txt .= '<i class="icon_star"></i>';
	}

	if($nostar < 5)
		$txt .= ' ('.randomNum().')</span>';
	else
		$txt .= '</span>';

	return $txt;
}

function getRandomFullImage()
{
	$full = App\Models\View_holding_frontpage::where('material_type_id','!=',3)->limit(5)->get()->random(5);
	return $full;
}

function getRandomSingleFullImage()
{
	$full = App\Models\Fullpage::all()->random(1)->first();;
	return $full['filename'];
}

function randomNum()
{
	return rand(100,1000);
}

function randomNum2()
{
	return rand(1,100);
}

function getPublicationDetail($catalog_id)
{
	$pub = App\Models\ViewCatalog::where('catalog_id',$catalog_id)->first();
	// switch ($type) {
	// 	case 'title':
	// 			$item = App\Models\Holding::where('catalog_id',$catalog_id)->first();
	// 			$item = $item['title_statement']; 
	// 		break;
	// 	case 'author':
	// 			$item = App\Models\Author::where('catalog_id',$catalog_id)->get();
	// 			$authors = "";
	// 			foreach ($item as $key => $value) {
	// 				$authors = $value->author_name2.",";
	// 			}

	// 			$item = substr($authors, 0, -1);
	// 		break;
	// 	case 'pubyear':
	// 			$item = App\Models\Publication::where('catalog_id',$catalog_id)->first();
	// 			$item = $item['title_statement']; 
	// 		break;
		
	// 	default:
	// 			$item = null;
	// 		break;
	// }

	return $pub;
}

function getSeriesDesc($id)
{
	$series = App\Models\Series::where('id',$id)->first();
	if($series)
		return $series['description'];
	else
		return "Series ID error";
}

function getTitle($id)
{
	$pub = App\Models\Holding::where('catalog_id',$id)->first();
	if(isset($pub))
	{
		return formatTitle($pub['title_statement']);
	}
	else
	{
		return null;
	}
	
}

function showReview($catalog_id)
{
	$rating = App\Models\View_rating::where('catalog_id',$catalog_id)->get();
	if(count($rating) > 0)
		return $rating;
	else
		return [];
}

function starRatingAve($id)
{
	$rating = showReview($id);

	if(count($rating) > 0)
	{
		//GET AVE RATING
		$sum = 0;
		foreach($rating AS $ratings)
		{
			$sum += $ratings->rating;
		}

		$ave = round($sum / count($rating),1);

		return $ave;	
	}
	else
	{
		return 0;
	}
}

function starRatingBar($total,$val,$catalog_id)
{
	$rating = App\Models\Rating::where('catalog_id',$catalog_id)->where('rating',$val)->count();

	if($rating > 0)
	{
		$percentage = ($rating / $total) * 100; // 20

		return round($percentage);
	}
	else
	{
		return 0;
	}
	
}

function saveView($id)
{
	$view = new App\Models\View;
	$view->ip_view = request()->ip();

    if(Auth::check())
    {
      	$view->catalog_id = $id;
      	$view->is_guest = 'NO';
	    $view->user_id = Auth::user()->id;
    }
    else
    {
	    $view->catalog_id = $id;
	    $view->is_guest = 'YES';
	}

	$view->save();
}


function countNum($type,$id)
{
	switch($type)
	{
		case "view":
			return App\Models\View::where('catalog_id',$id)->count();
		break;
		case "review":
			return App\Models\Rating::where('catalog_id',$id)->count();
		break;
		case "download":
			return App\Models\Download::where('catalog_id',$id)->count();
		break;
	}
}

function getNotif($type)
{
	if(Auth::check())
	{
		$ctr = 0;

	
		switch($type)
			{
				case "review":
					$list = App\Models\View_to_review::where('user_id',Auth::user()->id)->whereNull('to_review')->groupBy(['user_id','to_review'])->count();
				break;

				case "inquiry":
					$list = App\Models\View_inquiry_thread::where('user_id',Auth::user()->id)->where('replied_by','!=',Auth::user()->id)->whereNull('seen')->count();
				break;
			}

		if($type != 'overall')
		{
			if($list > 0)
				return '<span class="badge rounded-pill bg-danger">'.$list.'</span>';
		}
		else
		{
			//TO REVIEW
			$ctr += App\Models\View_to_review::where('user_id',Auth::user()->id)->whereNull('to_review')->groupBy(['user_id','to_review'])->count();

			if($ctr > 0)
			{
				return '<span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
				    <span class="visually-hidden">New alerts</span>
				  </span>';
			}
		}
	}
	
}

function getMaterials()
{
	$list = App\Models\Material::where('id','<',8)->orderBy('name')->get();
	return $list;
}

function getFrontpageResult($catalog_id)
{
	$img = App\Models\Frontpage::where('catalog_id',$catalog_id)->first();

	if($img)
		$url = $img['FrontPageLocation'];
	else
		$url =  "frontpages/no-preview.jpg";

	return '<img src="'.url('/')."/".$url.'" class="img-fluid" alt="">';
}

function getFrontpageLink($catalog_id)
{
	$img = App\Models\Frontpage::where('catalog_id',$catalog_id)->first();

	if($img)
		$url = $img['FrontPageLocation'];
	else
		$url =  "frontpages/no-preview.jpg";

	return url('/')."/".$url;
}

function getPDFLink($catalog_id)
{
	$pdf = App\Models\Multimedia::where('catalog_id',$catalog_id)->first();

	if($pdf)
		$url = $pdf['FileLocation'];
	else
		$url =  "frontpages/no-preview.jpg";

	return url('/')."/".$url;
}

function getFrontpage($catalog_id)
{
	$img = App\Models\Frontpage::where('catalog_id',$catalog_id)->first();

	if($img)
		$url = $img['FrontPageLocation'];
	else
		$url =  "frontpages/no-preview.jpg";

	return '<img src="'.url('/')."/".$url.'" class="img-fluid img-cover fill" alt="">';
}

function checkIfHasPDF($catalog_id)
{
	$pdf = App\Models\Multimedia::where('catalog_id',$catalog_id)->first();
	if($pdf)
		return true;
	else
		return false;
}


function pubYear($yr)
{

	$date = $yr."-01-01";
	
	if(isValid($date))
	{
		return "<b>Year : </b>".$yr."<br/>";
	}
	else
	{
		return $yr;
	}
}

function isValid($date, $format = 'Y-m-d'){
    $dt = DateTime::createFromFormat($format, $date);
    return $dt && $dt->format($format) === $date;
  }

function getFirstInquiry($inq_id)
{
	$msg = App\Models\Inquiry::where('id',$inq_id)->first();
	return $msg;
}

function getReplies($reply)
{
	if($reply > 0){
		if($reply == 1)
			return '<span class="badge rounded-pill bg-success">1&nbsp new reply</span>';
		else
			return '<span class="badge rounded-pill bg-success">'.$reply.'&nbsp new replies</span>';
	}

	//$msg = App\Models\Inquiry::where('id',$inq_id)->first();
	//return $msg;
}

function getUser($id,$col)
{
	$user = App\Models\User::where('id',$id)->first();
	return $user->$col;
}

function getConsortia($nopcaarrd = null)
{
	if($nopcaarrd == 'all')
		$list = App\Models\Consortia::orderBy('id','DESC')->get();
	else
		$list = App\Models\Consortia::where('id','!=',16)->get();

	return $list;
}

function getImprint()
{
	$list = App\Models\Imprint::get();
	return $list;
}

function getImprintType()
{
	$list = App\Models\Type_of_imprint::get();
	return $list;
}

function getSeries()
{
	$list = App\Models\Series::get();
	return $list;
}


function getLatestNews()
{
	$news = App\Models\News::where('featured', 'Yes')->orderBy('created_at','DESC')->limit(3)->get();
	return $news;
}

function getLatestNewsList()
{
	$news = App\Models\News::orderBy('created_at','DESC')->limit(5)->get();
	return $news;
}

function getLatestMaterial()
{
	$list = App\Models\ViewCatalog::whereNotNull('created_at')
		->where('view_catalogs.deleted_at', null)
		->where('is_published', 1)
		->orderBy('created_at','DESC')
		->limit(5)->get();
	return $list;
}

function getFeaturedMaterials()
{
    $path = public_path('featured');
    $files = File::files($path);

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];

    $sources = [];

    foreach ($files as $file) {
        if (in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            $sources[] = url('featured/' . $file->getFilename());
        }
    }

    return $sources;
}

function getAnnualReports()
{
    $path = public_path('annual_report');
    $files = File::files($path);

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'tif', 'tiff'];

    $sources = [];

    foreach ($files as $file) {
        if (in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            $sources[] = url('annual_report/' . $file->getFilename());
        }
    }

    return $sources;
}

