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
	// Titles live in view_catalogs; the holdings table is empty, so query ViewCatalog.
	$pub = App\Models\ViewCatalog::where('catalog_id',$id)->first();
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

/**
 * Is this request an automated client (search crawler, link-preview fetcher, scraper, monitor)?
 *
 * Used to keep bots out of the view counter. Once the OPAC is public, a single Googlebot pass over
 * the sitemap would otherwise add one view per catalogue record — thousands of rows in one sweep —
 * which would swamp real usage and turn the homepage "Most Viewed" carousel into "most recently
 * crawled". A missing user agent counts as automated: every real browser sends one.
 *
 * False positives (a human not counted) are cheap; false negatives (a bot counted) silently corrupt
 * the data, so this errs towards excluding.
 */
function isCrawler($agent = null): bool
{
	$agent = trim((string) ($agent ?? request()->userAgent()));

	if ($agent === '') {
		return true;
	}

	return (bool) preg_match(
		'~(bot|crawl|spider|scrap|slurp|curl|wget|python-requests|go-http-client|okhttp|java/|'
		. 'headless|phantomjs|puppeteer|playwright|lighthouse|'
		. 'facebookexternalhit|embedly|quora link preview|whatsapp|telegram|skypeuripreview|'
		. 'pinterest|vkshare|yandex|baidu|sogou|ia_archiver|archive\.org|'
		. 'semrush|ahrefs|mj12|petal|chatgpt|anthropic|perplexity|bytespider|'
		. 'pingdom|uptimerobot|w3c_validator|feedburner)~i',
		$agent
	);
}

/**
 * Record a view of a material. Crawler hits are deliberately NOT recorded — see isCrawler().
 * The agent is stored so a mis-classification can be audited later.
 */
function saveView($id)
{
	if (isCrawler()) {
		return;
	}

	$view = new App\Models\View;
	$view->ip_view = request()->ip();
	$view->user_agent = substr((string) request()->userAgent(), 0, 512);

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

/**
 * Materials a patron still needs to review: everything they have downloaded OR read,
 * minus everything they have already rated. One review clears a material for good, and
 * it covers both the download and the read of that material. Returns a query builder
 * yielding rows of { catalog_id, last_at } (distinct per material).
 */
function reviewPendingQuery($userId)
{
	$engaged = \DB::table('downloads')->where('user_id', $userId)->select('catalog_id', 'created_at')
		->unionAll(\DB::table('reads')->where('user_id', $userId)->select('catalog_id', 'created_at'));

	return \DB::query()->fromSub($engaged, 'engaged')
		->select('catalog_id', \DB::raw('MAX(created_at) as last_at'))
		->whereNotIn('catalog_id', function ($q) use ($userId) {
			$q->select('catalog_id')->from('ratings')->where('user_id', $userId);
		})
		->groupBy('catalog_id');
}

function getNotif($type)
{
	if(Auth::check())
	{
		$ctr = 0;

	
		switch($type)
			{
				case "review":
					// Distinct materials awaiting review = (downloaded or read) minus rated.
					$list = reviewPendingQuery(Auth::user()->id)->get()->count();
				break;

				case "inquiry":
					$list = App\Models\View_inquiry_thread::where('user_id',Auth::user()->id)->where('replied_by','!=',Auth::user()->id)->whereNull('seen')->count();
				break;

				case "reservation":
					// Unseen messages from the library (admin) in this patron's reservation chats.
					$list = \DB::table('reservation_messages')
						->where('user_id',Auth::user()->id)
						->where('sender_role','admin')
						->whereNull('seen_at')
						->count();
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
			$ctr += reviewPendingQuery(Auth::user()->id)->get()->count();

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

/**
 * Centralised in-app notifications for the current user (patron or staff),
 * computed once per request. See App\Services\NotificationService.
 * Returns ['count' => int, 'items' => [ ['icon','color','title','text','url'], ... ]].
 */
function notifications()
{
	static $cache = null;
	if ($cache !== null) return $cache;
	if (! Auth::check()) return $cache = ['count' => 0, 'items' => []];
	return $cache = App\Services\NotificationService::for(Auth::user());
}

/**
 * Read an app setting (settings table), cached per request. Falls back to $default
 * if the table/key is missing (e.g. before the settings table exists).
 */
function setting($key, $default = null)
{
	// Memoised per request. Held in the container rather than a function static so it can
	// be flushed (settingsFlush) — a `static` would survive for the life of the process,
	// which is fine under mod_php/FPM but wrong anywhere the app is long-lived (Octane,
	// queue workers, tests) and makes a just-saved setting unreadable until a restart.
	if (! app()->bound('app.settings')) {
		try { $cache = App\Models\Setting::pluck('value', 'key')->all(); }
		catch (\Throwable $e) { $cache = []; }
		app()->instance('app.settings', $cache);
	}

	$cache = app('app.settings');

	return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/** Drop the memoised settings so the next setting() re-reads the table. */
function settingsFlush()
{
	app()->forgetInstance('app.settings');
}

/** A boolean app setting: true unless explicitly '0'. */
function settingEnabled($key, $default = true)
{
	$v = setting($key, $default ? '1' : '0');
	return $v === '1' || $v === 1 || $v === true;
}

/**
 * Effective cover FrontPageLocation for a catalog (or null = no cover), honoring the
 * "enable placeholder coverpage" toggle: generated placeholders (gen-*) are hidden when off.
 */
function coverBase($catalog_id)
{
	$img = App\Models\Frontpage::where('catalog_id', $catalog_id)->first();
	if ($img && $img['FrontPageLocation']) {
		if (strpos((string) $img['FrontPageName'], 'gen-') === 0 && !settingEnabled('enable_placeholder_covers')) {
			return null;
		}
		return $img['FrontPageLocation'];
	}
	return null;
}

function getFrontpageResult($catalog_id)
{
	$url = coverBase($catalog_id) ?: 'frontpages/no-preview.jpg';
	return '<img src="'.url('/')."/".$url.'" class="img-fluid" alt="">';
}

/**
 * Make sure a directory under public/ exists and can be written to before saving into it.
 *
 * public/thumb, public/pdf, public/news_cover, ... are symlinks into storage/app/public
 * (config/filesystems.php `links`). On a fresh checkout `storage:link` creates the links but
 * not their targets, and a plain is_dir()/mkdir() can't fix that: is_dir() is false for a
 * dangling link and mkdir() fails because the link itself exists. So create the link's
 * TARGET when the link is dangling, otherwise the directory itself.
 */
function ensurePublicDir($dir)
{
	if (is_dir($dir)) return true;

	if (is_link($dir)) {
		$target = readlink($dir);
		if ($target !== false && $target !== '' && $target[0] !== '/') {
			$target = dirname($dir) . '/' . $target;   // relative link
		}
		return $target !== false && (is_dir($target) || @mkdir($target, 0775, true));
	}

	return @mkdir($dir, 0775, true) || is_dir($dir);
}

/**
 * Create a downscaled thumbnail copy of an image (JPG/PNG/WEBP).
 * Scales down to $maxWidth (never upscales), preserving aspect ratio + transparency.
 */
function makeThumbnail($sourcePath, $destPath, $maxWidth = 250)
{
	if (!extension_loaded('gd') || !file_exists($sourcePath)) return false;
	$info = @getimagesize($sourcePath);
	if (!$info) return false;
	[$w, $h] = $info;

	switch ($info['mime']) {
		case 'image/jpeg': $src = @imagecreatefromjpeg($sourcePath); break;
		case 'image/png':  $src = @imagecreatefrompng($sourcePath); break;
		case 'image/webp': $src = @imagecreatefromwebp($sourcePath); break;
		default: return false;
	}
	if (!$src) return false;

	if ($w > $maxWidth) {
		$nw = $maxWidth;
		$nh = (int) round($h * ($maxWidth / $w));
	} else {
		$nw = $w; $nh = $h;   // don't upscale
	}

	$thumb = imagecreatetruecolor($nw, $nh);
	if ($info['mime'] === 'image/png' || $info['mime'] === 'image/webp') {
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
	}
	imagecopyresampled($thumb, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

	if (!ensurePublicDir(dirname($destPath))) return false;
	switch ($info['mime']) {
		case 'image/jpeg': imagejpeg($thumb, $destPath, 85); break;
		case 'image/png':  imagepng($thumb, $destPath, 6); break;
		case 'image/webp': imagewebp($thumb, $destPath, 85); break;
	}
	imagedestroy($src);
	imagedestroy($thumb);
	return true;
}

/**
 * <img> for a catalog's cover thumbnail (public/thumb), falling back to the
 * full cover, then the no-preview placeholder.
 */
function getThumbnailResult($catalog_id)
{
	$loc = coverBase($catalog_id);
	if ($loc) {
		$thumb = 'thumb/'.basename($loc);
		$url = file_exists(public_path($thumb)) ? $thumb : $loc;
	} else {
		$url = 'frontpages/no-preview.jpg';
	}

	return '<img src="'.url('/')."/".$url.'" class="img-fluid" alt="">';
}

/**
 * URL (string) of a catalog's cover thumbnail, falling back to the full cover,
 * then no-preview. Used by the instant-search JSON endpoint.
 */
function getThumbnailLink($catalog_id)
{
	$loc = coverBase($catalog_id);
	if ($loc) {
		$thumb = 'thumb/'.basename($loc);
		$url = file_exists(public_path($thumb)) ? $thumb : $loc;
	} else {
		$url = 'frontpages/no-preview.jpg';
	}

	return url('/')."/".$url;
}

function getFrontpageLink($catalog_id)
{
	$url = coverBase($catalog_id) ?: 'frontpages/no-preview.jpg';
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

/**
 * Parse a catalog's stored MARC field_data into readable "Full Record" rows for the OPAC.
 * Skips the Leader + 00X control fields (pure MARC plumbing) and the title/main-author
 * tags (already shown at the top of the page), so it lists the *other* available details.
 * Returns an array of ['label' => ..., 'value' => ..., 'justify' => bool], only for fields that have
 * a value. 'justify' is set when any subfield was saved with the editor's Justify button.
 */
function getFullRecordFields($catalog_id)
{
	$cat = \App\Models\Catalog::find($catalog_id);
	if(!$cat || !$cat->field_data) return [];

	$data = json_decode($cat->field_data, true);
	if(!is_array($data)) return [];

	$skip = ['LDR','001','003','005','006','007','008','245','100','700'];
	$labels = [
		'020' => 'ISBN', '022' => 'ISSN', '041' => 'Language',
		'110' => 'Corporate Author', '111' => 'Meeting Name',
		'250' => 'Edition', '260' => 'Publication', '264' => 'Publication',
		'300' => 'Physical Description', '336' => 'Content Type',
		'490' => 'Series', '500' => 'Note', '504' => 'Bibliography Note',
		'505' => 'Contents', '520' => 'Summary', '546' => 'Language Note',
		'650' => 'Subject', '651' => 'Subject', '653' => 'Subject',
		'710' => 'Corporate Author', '830' => 'Series', '852' => 'Location',
		'856' => 'URL',
	];

	$out = [];
	foreach($data as $f)
	{
		$code = $f['marctag']['code'] ?? '';
		if($code === '' || in_array($code, $skip, true)) continue;

		$vals = [];
		$justify = false;
		foreach(($f['subfield'] ?? []) as $s)
		{
			$v = trim((string)($s['value'] ?? ''));
			if($v !== '') {
				$vals[] = $v;
				$justify = $justify || (($s['align'] ?? null) === 'justify');
			}
		}
		if(!$vals) continue;

		$label = $labels[$code] ?? ($f['marctag']['description'] ?? $code);
		$out[] = ['label' => $label, 'value' => implode(' ', $vals), 'justify' => $justify];
	}
	return $out;
}

/**
 * Root-relative web path to a catalog's PDF (e.g. "/pdf/ft-xxx.pdf"), but only when the
 * DB row exists AND the file is actually present on disk. Returns '' otherwise, so callers
 * (grid Read/Download buttons, PDF.js reader) never render an action that would 404.
 */
function getReadPdfUrl($catalog_id)
{
	$pdf = App\Models\Multimedia::where('catalog_id',$catalog_id)->first();
	if(!$pdf) return '';

	$loc = ltrim((string)$pdf['FileLocation'], '/');
	if($loc === '' || !is_file(public_path($loc))) return '';

	return '/'.$loc;
}

/**
 * Guarded reader URL. Returns the /read/{id} route (streamed through HoldingController::read,
 * which denies banned accounts) when a readable file exists, else ''. Use this for the
 * in-browser reader instead of the raw public path.
 */
function getReadUrl($catalog_id)
{
	// Reuse existence check; only expose the guarded route when a file is actually present.
	if(getReadPdfUrl($catalog_id) === '') return '';

	return url('/read/'.$catalog_id);
}

/**
 * Build formatted citations (APA / MLA / Chicago) from a catalog record.
 * Dependency-free and reusable — intended to also back a future citation API so the
 * Project Y RAG can cite eLibrary materials in the same formats. $record needs:
 * title_statement, joint_authors, publisher, pub_year, edition.
 */
function buildCitations($record)
{
	if (!$record) return [];

	$title = rtrim(trim((string)($record->title_statement ?? '')), " .");
	if ($title === '') return [];

	$year      = trim((string)($record->pub_year ?? '')) ?: 'n.d.';
	$publisher = trim((string)($record->publisher ?? ''));
	$edition   = trim((string)($record->edition ?? ''));
	$authors   = array_values(array_filter(array_map('trim', preg_split('#\s*/\s*#', (string)($record->joint_authors ?? '')))));

	$ed     = $edition ? ' ('.e($edition).')' : '';
	$titleI = '<em>'.e($title).'</em>';
	$pub    = $publisher !== '' ? e($publisher) : '';

	// APA:     Authors (Year). Title (Edition). Publisher.
	$apa = (citeAuthors($authors, 'apa') ? citeAuthors($authors, 'apa').' ' : '')."($year). ".$titleI.$ed.'.'.($pub ? ' '.$pub.'.' : '');
	// MLA:     Authors. Title. Publisher, Year.
	$mla = (citeAuthors($authors, 'mla') ? citeAuthors($authors, 'mla').'. ' : '').$titleI.'.'.($pub ? ' '.$pub.',' : '')." $year.";
	// Chicago: Authors. Title. Publisher, Year.
	$chi = (citeAuthors($authors, 'chicago') ? citeAuthors($authors, 'chicago').'. ' : '').$titleI.'.'.($pub ? ' '.$pub.',' : '')." $year.";

	$fix = function ($s) { return rtrim(trim($s), '.').'.'; };   // exactly one trailing period
	return ['APA' => $fix($apa), 'MLA' => $fix($mla), 'Chicago' => $fix($chi)];
}

/**
 * Join an author list ("Surname, First") per citation style (values are HTML-escaped).
 */
function citeAuthors(array $authors, $style)
{
	$authors = array_values(array_filter(array_map(function ($a) { return e(rtrim(trim($a), ' .')); }, $authors)));
	$n = count($authors);
	if ($n === 0) return '';
	if ($n === 1) return $authors[0];
	if ($style === 'mla' && $n > 2) return $authors[0].', et al';
	$conj = $style === 'apa' ? '&amp;' : 'and';
	if ($n === 2) return $authors[0].' '.$conj.' '.$authors[1];
	$last = array_pop($authors);
	return implode(', ', $authors).', '.$conj.' '.$last;
}

/**
 * Generate a title-based placeholder book cover (colored background + wrapped title +
 * first author + material type) for a material that has no real cover. Deterministic
 * color per title. Returns true on success. Temporary/removable via the covers command.
 */
function generatePlaceholderCover($vc, $destPath, $w = 400, $h = 520)
{
	if (!extension_loaded('gd') || !$vc) return false;

	$palette = [
		[44,62,80],[39,60,117],[106,44,112],[125,60,52],[27,94,89],
		[64,64,122],[130,88,159],[45,52,54],[87,101,116],[120,40,64],
	];
	$title  = trim((string)($vc->title_statement ?? 'Untitled')) ?: 'Untitled';
	$c      = $palette[abs(crc32($title)) % count($palette)];

	$im    = imagecreatetruecolor($w, $h);
	imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, $c[0], $c[1], $c[2]));
	imagefilledrectangle($im, 0, 0, $w, 6, imagecolorallocate($im, min($c[0]+60,255), min($c[1]+60,255), min($c[2]+60,255)));
	$white = imagecolorallocate($im, 246,246,246);
	$muted = imagecolorallocate($im, 205,205,205);

	$fontB = coverFontPath(true);
	$fontR = coverFontPath(false);

	if ($fontB && $fontR) {
		$lines = array_slice(coverWrapLines($fontB, 22, $title, $w - 60), 0, 8);
		$y = 100;
		foreach ($lines as $ln) {
			$bb = imagettfbbox(22, 0, $fontB, $ln);
			imagettftext($im, 22, 0, (int)(($w - ($bb[2]-$bb[0])) / 2), $y, $white, $fontB, $ln);
			$y += 34;
		}
		$author = trim(explode('/', (string)($vc->joint_authors ?? ''))[0]);
		if ($author !== '') {
			$bb = imagettfbbox(15, 0, $fontR, $author);
			imagettftext($im, 15, 0, (int)(($w - ($bb[2]-$bb[0])) / 2), $h - 74, $muted, $fontR, $author);
		}
		$mat = strtoupper(trim((string)($vc->material_type ?? '')));
		if ($mat !== '') {
			$bb = imagettfbbox(12, 0, $fontR, $mat);
			imagettftext($im, 12, 0, (int)(($w - ($bb[2]-$bb[0])) / 2), $h - 42, $muted, $fontR, $mat);
		}
	} else {
		imagestring($im, 5, 20, 40, wordwrap($title, 34, "\n", true), $white);
	}

	if (!ensurePublicDir(dirname($destPath))) { imagedestroy($im); return false; }
	imagejpeg($im, $destPath, 88);
	imagedestroy($im);
	return true;
}

function coverFontPath($bold)
{
	$c = $bold
		? ['C:/Windows/Fonts/arialbd.ttf', 'C:/Windows/Fonts/segoeuib.ttf']
		: ['C:/Windows/Fonts/arial.ttf',   'C:/Windows/Fonts/segoeui.ttf'];
	foreach ($c as $f) { if (is_file($f)) return $f; }
	return null;
}

function coverWrapLines($font, $size, $text, $maxWidth)
{
	$words = preg_split('/\s+/', $text);
	$lines = []; $line = '';
	foreach ($words as $wd) {
		$try = $line === '' ? $wd : $line.' '.$wd;
		$bb  = imagettfbbox($size, 0, $font, $try);
		if (($bb[2] - $bb[0]) > $maxWidth && $line !== '') { $lines[] = $line; $line = $wd; }
		else { $line = $try; }
	}
	if ($line !== '') $lines[] = $line;
	return $lines;
}

/**
 * Ensure a newly-created record has a cover: if it has none and the "enable placeholder
 * coverpage" setting is on, generate a gen-* placeholder (image + thumbnail + Frontpage row).
 * Safe/idempotent — call it right after creating a record (batch, single, or parse).
 */
function ensurePlaceholderCover($catalog_id)
{
	// A placeholder is decoration: it runs AFTER the record is saved, so it must never turn a
	// successful save into an error page (which invites a retry and a duplicate record).
	try {
		if (!$catalog_id || !settingEnabled('enable_placeholder_covers')) return false;
		if (App\Models\Frontpage::where('catalog_id', $catalog_id)->whereNull('deleted_at')->exists()) return false;

		$vc = App\Models\ViewCatalog::where('catalog_id', $catalog_id)->first();
		if (!$vc) return false;

		$file = 'gen-'.$catalog_id.'.jpg';
		if (generatePlaceholderCover($vc, public_path('frontpages/'.$file))) {
			makeThumbnail(public_path('frontpages/'.$file), public_path('thumb/'.$file));
			App\Models\Frontpage::create([
				'catalog_id'        => $catalog_id,
				'FrontPageName'     => $file,
				'FrontPageType'     => 'jpg',
				'FrontPageLocation' => 'frontpages/'.$file,
			]);
			return true;
		}
	} catch (\Throwable $e) {
		report($e);   // logged for the admin; the record itself is fine
	}
	return false;
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
	return cache()->remember('home_latest_news', 600, function () {
		return App\Models\News::where('featured', 'Yes')->orderBy('created_at','DESC')->limit(12)->get();
	});
}

/**
 * Bust the 10-minute homepage/sidebar caches so content changes show immediately
 * (news add/edit/delete, material create/publish/update/delete, transferring an
 * acquisition from the on-hold list to the catalog). File cache has no tag support,
 * so the specific keys are forgotten. home_shelves is keyed by limit — the homepage
 * uses getShelves(45).
 */
function clearHomeCache()
{
	foreach (homeCacheKeys() as $key) {
		cache()->forget($key);
	}
}

/** The homepage/sidebar cached panels. */
function homeCacheKeys()
{
	return [
		'home_latest_news',
		'home_latest_material',
		'home_shelves_45',      // keyed by limit; the homepage uses getShelves(45)
		'home_top_downloaded',
		'home_top_viewed',
		'home_top_rated',
	];
}

/**
 * Every cache key this app owns — what the Settings "Clear cache" button drops.
 * ADD NEW KEYS HERE when you add a cache, or the button silently won't clear it.
 *
 * Enumerated rather than Cache::flush() on purpose: the cache store also holds the
 * rate limiter's counters, so flushing would reset LOGIN brute-force protection for
 * every IP (and reset the clear-cache endpoint's own throttle, making it unlimitable).
 */
function appCacheKeys()
{
	return array_merge(homeCacheKeys(), [
		'dash_views_traffic_6m',   // dashboard "Number of Views" chart (AdminController)
	]);
}

function getLatestNewsList()
{
	$news = App\Models\News::orderBy('created_at','DESC')->limit(5)->get();
	return $news;
}

function getLatestMaterial()
{
	return cache()->remember('home_latest_material', 600, function () {
		return App\Models\ViewCatalog::whereNotNull('created_at')
			->where('view_catalogs.deleted_at', null)
			->where('is_published', 1)
			->orderBy('created_at','DESC')
			->limit(20)->get();
	});
}

/**
 * Shape aggregate rows (each with catalog_id + title_statement) into homepage ranked-carousel
 * items: cover thumbnail, link, title, and a hover "detail" string built by $detailFn.
 */
function rankItemsMap($rows, callable $detailFn)
{
	return $rows->map(function ($r) use ($detailFn) {
		return [
			'catalog_id' => $r->catalog_id,
			'title'      => $r->title_statement,
			'thumb'      => getThumbnailLink($r->catalog_id),
			'url'        => recordUrl($r),
			'detail'     => $detailFn($r),
		];
	})->all();
}

// Top-N most downloaded published materials (with total download count).
function getMostDownloaded($limit = 10)
{
	return cache()->remember('home_top_downloaded', 600, function () use ($limit) {
		$rows = DB::table('downloads as d')
			->join('view_catalogs as v', 'v.catalog_id', '=', 'd.catalog_id')
			->where('v.is_published', 1)->whereNull('v.deleted_at')
			->select('d.catalog_id', DB::raw('COUNT(*) as total'), 'v.title_statement')
			->groupBy('d.catalog_id', 'v.title_statement')
			->orderByDesc('total')->limit($limit)->get();
		return rankItemsMap($rows, function ($r) { return number_format($r->total) . ' download' . ($r->total == 1 ? '' : 's'); });
	});
}

// Top-N most viewed published materials (with total view count).
function getMostViewed($limit = 10)
{
	return cache()->remember('home_top_viewed', 600, function () use ($limit) {
		$rows = DB::table('views as vw')
			->join('view_catalogs as v', 'v.catalog_id', '=', 'vw.catalog_id')
			->where('v.is_published', 1)->whereNull('v.deleted_at')
			->select('vw.catalog_id', DB::raw('COUNT(*) as total'), 'v.title_statement')
			->groupBy('vw.catalog_id', 'v.title_statement')
			->orderByDesc('total')->limit($limit)->get();
		return rankItemsMap($rows, function ($r) { return number_format($r->total) . ' view' . ($r->total == 1 ? '' : 's'); });
	});
}

// Top-N highest average rating (published), tie-broken by number of votes.
function getHighestRated($limit = 10)
{
	return cache()->remember('home_top_rated', 600, function () use ($limit) {
		$rows = DB::table('ratings as r')
			->join('view_catalogs as v', 'v.catalog_id', '=', 'r.catalog_id')
			->where('v.is_published', 1)->whereNull('v.deleted_at')
			->select('r.catalog_id', DB::raw('ROUND(AVG(r.rating),1) as rating'), DB::raw('COUNT(*) as votes'), 'v.title_statement')
			->groupBy('r.catalog_id', 'v.title_statement')
			->orderByDesc('rating')->orderByDesc('votes')->limit($limit)->get();
		return rankItemsMap($rows, function ($r) { return '★ ' . number_format($r->rating, 1) . ' · ' . $r->votes . ' rating' . ($r->votes == 1 ? '' : 's'); });
	});
}

/**
 * Category shelves for the home-page shelf browser: one shelf per material type that
 * actually has published records (empty types are skipped, most-populated first).
 * Thumbnails only. Each shelf:
 *   ['key' => ..., 'label' => ..., 'items' => [ ['thumb','url','title'], ... ]].
 *
 * Every shelf is catalog-driven, so each item links to its record.
 */
function getShelves($limit = 45)
{
    return cache()->remember('home_shelves_' . $limit, 600, function () use ($limit) {
    $shelves = [];

    // Only material types that have published records (most-populated first), so no empty shelves.
    $populated = App\Models\ViewCatalog::where('is_published', 1)->whereNull('deleted_at')
        ->select('material_type_id', DB::raw('count(*) as c'))
        ->groupBy('material_type_id')->orderByDesc('c')->get();

    $names = DB::table('materials')->pluck('name', 'id');

    foreach ($populated as $p) {
        if (!$p->material_type_id) continue;

        $rows = App\Models\ViewCatalog::where('material_type_id', $p->material_type_id)
            ->where('is_published', 1)->whereNull('deleted_at')
            ->orderByDesc('created_at')->limit($limit)
            ->get(['catalog_id', 'title_statement']);
        if ($rows->isEmpty()) continue;

        $items = $rows->map(function ($r) {
            return [
                'thumb' => getThumbnailLink($r->catalog_id),
                'url'   => recordUrl($r),
                'title' => $r->title_statement,
            ];
        })->all();

        $shelves[] = [
            'key'   => 'type-' . $p->material_type_id,
            'label' => $names[$p->material_type_id] ?? 'Other materials',
            'items' => $items,
        ];
    }

    // Arrange the shelves alphabetically by label (case-insensitive).
    usort($shelves, function ($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });

    return $shelves;
    });
}


/* ==========================================================================
 * SEO helpers (OPAC public pages) — see OPAC_SEO_PLAN.md workstream A.
 * The OPAC layout used to emit one hardcoded <title> + description for every
 * page; these build per-page values so each record is distinguishable.
 * ========================================================================== */

/**
 * Squash whitespace and drop trailing ISBD punctuation ("Title /", "Author :", "Publisher ;").
 * MARC fields carry these marks as field separators; they read as typos outside a catalogue card.
 * Trailing periods are deliberately kept — they are often meaningful ("Jatropha curcas L.").
 */
function seoTrimIsbd($text): string
{
    $t = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $text)));
    $t = preg_replace('/\s*[\/:;,=]+\s*$/u', '', (string) $t);

    return trim((string) $t);
}

/**
 * A clean page title from a MARC 245 title statement.
 *
 * Casing is deliberately NOT changed: titles in this catalogue are often ALL CAPS, but title-casing
 * would mangle acronyms (DOST, PCAARRD) and botanical authorities ("Jatropha curcas L.") — wrong
 * data is worse than shouty data.
 */
function seoTitle($title, int $max = 65): string
{
    $t = seoTrimIsbd($title);

    return $t === '' ? '' : \Illuminate\Support\Str::limit($t, $max, '…');
}

/**
 * Record title for <title>. The site name is appended only when there is room for it: Google shows
 * roughly 60 characters, and for a long MARC title the words matter more than the branding.
 */
function seoPageTitle($title, string $site = 'DOST-PCAARRD eLibrary'): string
{
    $t = seoTitle($title, 65);
    if ($t === '') {
        return $site;
    }

    return mb_strlen($t) <= 36 ? $t . ' — ' . $site : $t;
}

/** Squash any text into a meta-description-sized, tag-free single line. */
function seoDescription($text, int $max = 155): string
{
    $t = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $text)));
    $t = trim((string) $t);

    return $t === '' ? '' : \Illuminate\Support\Str::limit($t, $max, '…');
}

/**
 * Per-record meta description: the 520 summary when there is one, else the general note, else a
 * factual one-liner built from the record itself. The fallback matters — plenty of records have an
 * empty summary, and without it they would all inherit the site-wide description again.
 */
function seoRecordDescription($record): string
{
    foreach ([$record->summary ?? null, $record->general_note ?? null] as $candidate) {
        $d = seoDescription($candidate);
        if ($d !== '') {
            return $d;
        }
    }

    // Each part is ISBD-trimmed too, or MARC separators ("Author /") leak into the snippet.
    // joint_authors separates names with "/" ("Cruz, J. /Santos, M."); read better as a list.
    $authors = preg_replace('#\s*/\s*#u', '; ', seoTrimIsbd($record->joint_authors ?? null));
    $imprint = seoTrimIsbd(implode(' ', array_filter([$record->publisher ?? null, $record->pub_year ?? null])));
    $bits = array_filter([
        seoTitle($record->title_statement ?? '', 90),
        trim((string) $authors, '; '),
        $imprint,
        seoTrimIsbd($record->material_type ?? null),
    ]);

    return seoDescription(implode('. ', $bits));
}

/* ---- Structured data (schema.org JSON-LD) — OPAC_SEO_PLAN.md workstream B ---------------- */

/**
 * Map a catalogue material type to a schema.org type. Values come from `materials.name`
 * (Books, Thesis/Dissertations, Serials, Technical Reports, Analytics, Vertical Files,
 * Non-Prints, Investigatory Projects, Language material).
 */
function seoSchemaType($materialType): string
{
    $t = strtolower(trim((string) $materialType));

    return match (true) {
        str_contains($t, 'thesis'), str_contains($t, 'dissertation') => 'Thesis',
        str_contains($t, 'serial')                                   => 'Periodical',
        str_contains($t, 'technical report'), $t === 'report'        => 'Report',
        str_contains($t, 'analytic'), str_contains($t, 'investigatory') => 'ScholarlyArticle',
        str_contains($t, 'book'), str_contains($t, 'language material') => 'Book',
        default                                                      => 'CreativeWork',
    };
}

/** joint_authors ("Cruz, J. /Santos, M.") -> schema.org Person list. */
function seoAuthorList($jointAuthors): array
{
    $raw = seoTrimIsbd($jointAuthors);
    if ($raw === '') {
        return [];
    }

    $out = [];
    foreach (preg_split('#\s*/\s*#u', $raw, -1, PREG_SPLIT_NO_EMPTY) as $name) {
        $name = seoTrimIsbd($name);
        if ($name !== '') {
            $out[] = ['@type' => 'Person', 'name' => $name];
        }
    }

    return $out;
}

/** Encode JSON-LD safely for inlining in a <script> tag (JSON_HEX_TAG blocks a </script> breakout). */
function seoJsonLdEncode(array $data): string
{
    return (string) json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
    );
}

/**
 * Per-record structured data: the record itself plus a breadcrumb trail. Emitted as a JSON array
 * (valid JSON-LD) so one <script> covers both. Only fields the record actually has are included —
 * an empty or invented property is worse than an absent one.
 */
function seoRecordJsonLd($record): string
{
    $url  = recordUrl($record);
    $type = seoSchemaType($record->material_type ?? null);

    // pub_year is free text in MARC ("c2010", "2010."); schema.org wants a bare year.
    $year = preg_match('/\d{4}/', (string) ($record->pub_year ?? ''), $m) ? $m[0] : null;

    $publisher = seoTrimIsbd($record->publisher ?? null);
    $series    = seoTrimIsbd($record->series_description ?? null);
    $edition   = seoTrimIsbd($record->edition ?? null);
    $pubUrl    = trim((string) ($record->pub_url ?? ''));

    $work = array_filter([
        '@context'      => 'https://schema.org',
        '@type'         => $type,
        '@id'           => $url,
        'url'           => $url,
        'name'          => seoTrimIsbd($record->title_statement ?? null),
        'description'   => seoRecordDescription($record),
        'author'        => seoAuthorList($record->joint_authors ?? null),
        'publisher'     => $publisher !== '' ? ['@type' => 'Organization', 'name' => $publisher] : null,
        'datePublished' => $year,
        'isbn'          => seoTrimIsbd($record->isbn ?? null) ?: null,
        'issn'          => seoTrimIsbd($record->issn ?? null) ?: null,
        'bookEdition'   => ($type === 'Book' && $edition !== '') ? $edition : null,
        'isPartOf'      => $series !== '' ? ['@type' => 'CreativeWork', 'name' => $series] : null,
        'image'         => getFrontpageLink($record->catalog_id),
        'identifier'    => seoTrimIsbd($record->accession_number ?? null) ?: null,
        'sameAs'        => filter_var($pubUrl, FILTER_VALIDATE_URL) ? $pubUrl : null,
    ], fn ($v) => $v !== null && $v !== '' && $v !== []);

    $breadcrumb = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => seoTitle($record->title_statement ?? '', 65), 'item' => $url],
        ],
    ];

    return seoJsonLdEncode([$work, $breadcrumb]);
}

/**
 * Site-level structured data for the homepage: the WebSite (with a search action, which is what
 * makes a sitelinks search box possible) and the publishing Organization.
 */
function seoSiteJsonLd(): string
{
    $home = url('/home');   // '/' redirects here; use the URL that actually renders

    return seoJsonLdEncode([
        [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => 'DOST-PCAARRD eLibrary',
            'url'             => $home,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => url('/results') . '?query={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => 'DOST-PCAARRD eLibrary',
            'url'      => $home,
            'logo'     => asset('assets/images/elibrary-02.png'),
        ],
    ]);
}

/* ---- Record URLs (ID-prefixed slugs) — OPAC_SEO_PLAN.md workstream D ---------------------- */

/**
 * The URL segment for a record: "10435-fruit-color-as-an-indicator-of-seed-germination".
 *
 * The numeric catalog_id stays the lookup key and the slug is decorative. That matters here:
 *  - MARC 245 titles get edited, and an ID prefix means a corrected typo never breaks a link;
 *  - library catalogues legitimately hold duplicate titles, so pure slugs would need collision
 *    handling;
 *  - catalog_id remains the join key the RAG stack depends on (see RAG_CATALOG_SYNC_PLAN.md).
 *
 * Accepts a record object/array, or an id plus a title.
 */
function seoRecordSegment($record, $title = null): string
{
	if (is_object($record) || is_array($record)) {
		$r = (object) $record;
		$id = (int) ($r->catalog_id ?? 0);
		$title = $title ?? ($r->title_statement ?? '');
	} else {
		$id = (int) $record;
	}

	$slug = \Illuminate\Support\Str::slug(seoTrimIsbd($title));

	// Keep URLs a sane length, cutting back to the last whole word so a long MARC title
	// doesn't end mid-syllable ("...performance-of-jatr").
	if (strlen($slug) > 80) {
		$slug = substr($slug, 0, 80);
		$lastWord = strrpos($slug, '-');
		if ($lastWord !== false && $lastWord > 20) {
			$slug = substr($slug, 0, $lastWord);
		}
	}
	$slug = trim($slug, '-');

	return $slug === '' ? (string) $id : $id . '-' . $slug;
}

/** Absolute canonical URL for a record page. */
function recordUrl($record, $title = null): string
{
	return url('/full-description/' . seoRecordSegment($record, $title));
}
