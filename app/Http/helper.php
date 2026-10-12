<?php

function maxTitle($title,$max = 115)
{
	// Character-based (mb_*): a byte cut could split "ñ" / "’" into an invalid sequence.
	if(mb_strlen((string) $title) > $max)
		return rtrim(mb_substr((string) $title, 0, $max)).'…';
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

/**
 * Library Material Types for admin lists (Reports, acquisitions): the active ones, managed in
 * Administration → Utilities → Material Types. (Was hard-coded to id < 8, so types added later
 * never appeared.) MARC record-type codes (LDR/06) are not library categories — excluded.
 */
function getMaterials()
{
	return App\Models\Material::where('is_active', true)->where('is_marc_type', false)->orderBy('name')->get();
}

/**
 * Material Types for the OPAC search filters: active library types that have at least one
 * published record, so a patron never filters into an empty category.
 */
function opacMaterials()
{
	$withRecords = App\Models\ViewCatalog::where('is_published', 1)->whereNull('deleted_at')
		->distinct()->pluck('material_type_id')->filter()->all();

	return App\Models\Material::where('is_active', true)->where('is_marc_type', false)
		->whereIn('id', $withRecords)->orderBy('name')->get();
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
 * Skips the Leader + 00X control fields (pure MARC plumbing), the title and the author tags the
 * page header already shows (see marcAuthors()), so it lists the *other* available details.
 * Returns an array of ['label' => ..., 'value' => ..., 'justify' => bool], only for fields that have
 * a value. 'justify' is set when any subfield was saved with the editor's Justify button.
 */
function getFullRecordFields($catalog_id)
{
	$cat = \App\Models\Catalog::find($catalog_id);
	if(!$cat || !$cat->field_data) return [];

	$data = json_decode($cat->field_data, true);
	if(!is_array($data)) return [];

	// Authors: the page header shows the main entries (100 / 110) or, when there are none, the added
	// entries (700 / 710) — see marcAuthors(). Skip what it shows; list any other authors here.
	$authors = marcAuthorGroups($data);
	$skip = ['LDR','001','003','005','006','007','008','245','100','110'];
	if (!$authors['main']) array_push($skip, '700', '710');
	$labels = [
		'020' => 'ISBN', '022' => 'ISSN', '041' => 'Language',
		'111' => 'Meeting Name',
		'250' => 'Edition', '260' => 'Publication', '264' => 'Publication',
		'300' => 'Physical Description', '336' => 'Content Type',
		'490' => 'Series', '500' => 'Note', '504' => 'Bibliography Note',
		'505' => 'Contents', '520' => 'Summary', '546' => 'Language Note',
		'650' => 'Subject', '651' => 'Subject', '653' => 'Subject',
		'830' => 'Series', '852' => 'Location',
		'856' => 'URL',
	];

	$out = [];
	foreach($data as $f)
	{
		$code = $f['marctag']['code'] ?? '';
		if($code === '' || in_array($code, $skip, true)) continue;
		// 9XX are local / vendor fields (Koha's 942 item type, 952 items, 999 ids): system data, not
		// bibliographic description — they showed as junk rows like "2500 | lcc FN".
		if(strlen((string) $code) === 3 && $code[0] === '9') continue;

		// Added authors: names only ("Fabian, E. F.; Payumo, F. M."), not every subfield ($e roles, dates).
		if($code === '700' || $code === '710') {
			$names = marcAuthorGroups([$f])['added'];
			if($names) $out[] = ['label' => $code === '700' ? 'Other Authors' : 'Corporate Author', 'value' => implode('; ', $names), 'justify' => false];
			continue;
		}

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

	// Title + subtitle from 245 WITHOUT $c: the citation prints the authors itself, and the
	// stored title_statement now carries the statement of responsibility too.
	$fieldData = !empty($record->catalog_id) ? \App\Models\Catalog::whereKey($record->catalog_id)->value('field_data') : null;
	$title = rtrim(trim((string)(marcTitle($fieldData, false) ?? seoTrimIsbd($record->title_statement ?? ''))), " .");
	if ($title === '') return [];

	$year      = trim((string)($record->pub_year ?? '')) ?: 'n.d.';
	$publisher = seoTrimIsbd($record->publisher ?? '');   // drop ISBD's trailing "," / ":" (260$b "Publisher,")
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
		// The full 245 title (incl. subtitle and statement of responsibility) can be long:
		// step the font down until it all fits above the author/material block, and only cut
		// with "…" if even the smallest size can't hold it.
		$top = 100; $bottom = $h - 150;
		foreach ([22, 20, 18, 16, 14] as $size) {
			$lh = (int) round($size * 1.5);
			$maxLines = (int) floor(($bottom - $top) / $lh) + 1;
			$lines = coverWrapLines($fontB, $size, $title, $w - 60);
			if (count($lines) <= $maxLines) break;
		}
		$lines = coverFitLines($lines, $maxLines, $fontB, $size, $w - 60);
		$y = $top;
		foreach ($lines as $ln) {
			imagettftext($im, $size, 0, (int)(($w - coverTextWidth($fontB, $size, $ln)) / 2), $y, $white, $fontB, $ln);
			$y += $lh;
		}
		$author = trim(explode('/', (string)($vc->joint_authors ?? ''))[0]);
		if ($author !== '') {
			// Up to two lines, bottom line just above the material type; long lists end in "…".
			$aLines = coverFitLines(coverWrapLines($fontR, 15, $author, $w - 60), 2, $fontR, 15, $w - 60);
			$ay = $h - 74 - (count($aLines) - 1) * 22;
			foreach ($aLines as $ln) {
				imagettftext($im, 15, 0, (int)(($w - coverTextWidth($fontR, 15, $ln)) / 2), $ay, $muted, $fontR, $ln);
				$ay += 22;
			}
		}
		$mat = strtoupper(trim((string)($vc->material_type ?? '')));
		if ($mat !== '') {
			$bb = imagettfbbox(12, 0, $fontR, $mat);
			imagettftext($im, 12, 0, (int)(($w - ($bb[2]-$bb[0])) / 2), $h - 42, $muted, $fontR, $mat);
		}
	} else {
		// No TrueType font anywhere: GD's built-in bitmap font. It draws ONE line per call (a
		// "\n" comes out as a stray glyph) and only Latin-2, so wrap + transliterate + draw
		// each line, centred.
		$font  = 5;
		$cw    = imagefontwidth($font);
		$lh    = imagefontheight($font) + 6;
		$ascii = function_exists('iconv') ? (@iconv('UTF-8', 'ASCII//TRANSLIT', $title) ?: $title) : $title;
		$wrap  = explode("\n", wordwrap($ascii, (int) floor(($w - 40) / $cw), "\n", true));
		$lines = array_slice($wrap, 0, 10);
		if (count($wrap) > 10) $lines[9] = rtrim(substr($lines[9], 0, max(0, strlen($lines[9]) - 3))) . '...';
		$y = 90;
		foreach ($lines as $ln) {
			imagestring($im, $font, (int) (($w - strlen($ln) * $cw) / 2), $y, $ln, $white);
			$y += $lh;
		}
	}

	if (!ensurePublicDir(dirname($destPath))) { imagedestroy($im); return false; }
	imagejpeg($im, $destPath, 88);
	imagedestroy($im);
	return true;
}

/**
 * TrueType font for placeholder covers. The bundled Liberation Sans (metric-compatible with
 * Arial, SIL OFL — shipped with the PDF viewer) comes first so covers look the same on every
 * OS; then Windows' Arial/Segoe UI and common Linux system fonts. Previously only the Windows
 * paths were tried, so on Linux covers fell back to GD's bitmap font (one unwrapped line).
 */
function coverFontPath($bold)
{
	$bundled = public_path('pdfjs/web/standard_fonts/LiberationSans-' . ($bold ? 'Bold' : 'Regular') . '.ttf');
	$c = $bold
		? [$bundled, 'C:/Windows/Fonts/arialbd.ttf', 'C:/Windows/Fonts/segoeuib.ttf',
		   '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
		: [$bundled, 'C:/Windows/Fonts/arial.ttf', 'C:/Windows/Fonts/segoeui.ttf',
		   '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];
	foreach ($c as $f) { if (is_file($f)) return $f; }
	return null;
}

function coverTextWidth($font, $size, $text)
{
	$bb = imagettfbbox($size, 0, $font, $text);
	return $bb[2] - $bb[0];
}

/** Word-wrap to $maxWidth; a single word too long for a line is split across lines. */
function coverWrapLines($font, $size, $text, $maxWidth)
{
	$words = preg_split('/\s+/', trim($text));
	$lines = []; $line = '';
	foreach ($words as $wd) {
		// Hard-break an over-long word (URLs, codes, long compounds) by characters.
		while (coverTextWidth($font, $size, $wd) > $maxWidth && mb_strlen($wd) > 1) {
			$cut = mb_strlen($wd) - 1;
			while ($cut > 1 && coverTextWidth($font, $size, mb_substr($wd, 0, $cut)) > $maxWidth) $cut--;
			if ($line !== '') { $lines[] = $line; $line = ''; }
			$lines[] = mb_substr($wd, 0, $cut);
			$wd = mb_substr($wd, $cut);
		}
		$try = $line === '' ? $wd : $line.' '.$wd;
		if (coverTextWidth($font, $size, $try) > $maxWidth && $line !== '') { $lines[] = $line; $line = $wd; }
		else { $line = $try; }
	}
	if ($line !== '') $lines[] = $line;
	return $lines;
}

/** Keep at most $max lines; when the title is cut, end the last kept line with an ellipsis. */
function coverFitLines(array $lines, $max, $font, $size, $maxWidth)
{
	if (count($lines) <= $max) return $lines;
	$lines = array_slice($lines, 0, $max);
	$last  = $lines[$max - 1];
	while ($last !== '' && coverTextWidth($font, $size, $last . '…') > $maxWidth) {
		$last = rtrim(mb_substr($last, 0, -1));
	}
	$lines[$max - 1] = $last . '…';
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
/**
 * The full title from MARC 245 (all its display subfields, in the cataloger's order), e.g.
 *   $a "2021 expanded national nutrition survey monograph series :"  $b "the food ... of Cebu /"
 *   $c "Department of Science and Technology - FNRI"
 *   -> "2021 expanded national nutrition survey monograph series : the food ... of Cebu /
 *       Department of Science and Technology - FNRI"
 *
 * - $6/$7/$8 are linkage/control subfields, never display text, so they're skipped.
 * - ISBD punctuation is usually already typed at the end of the preceding subfield; when it's
 *   missing, the conventional separator is inserted (" : " before $b, " / " before $c, ...).
 * - $withResponsibility = false drops $c (statement of responsibility) — for citations, which
 *   print the authors themselves.
 * Trailing ISBD separators (a dangling " /" or " :") are removed. Returns null without a 245.
 *
 * $fieldData: catalogs.field_data (JSON string or decoded array, the MarcEditor shape).
 */
function marcTitle($fieldData, bool $withResponsibility = true): ?string
{
	$fields = is_array($fieldData) ? $fieldData : json_decode((string) $fieldData, true);
	if (!is_array($fields)) return null;

	$f245 = null;
	foreach ($fields as $f) {
		if (is_array($f) && (string) ($f['marctag']['code'] ?? '') === '245') { $f245 = $f; break; }
	}
	if (!$f245) return null;

	$sep = ['b' => ' : ', 'c' => ' / ', 'n' => '. ', 'p' => '. ', 's' => '. ', 'f' => ', ', 'g' => ', ', 'k' => ' : ', 'h' => ' '];
	$out = ''; $prev = null;
	foreach (($f245['subfield'] ?? []) as $s) {
		$code = (string) ($s['code'] ?? '');
		$letter = strtolower(substr($code, strrpos($code, '$') === false ? 0 : strrpos($code, '$') + 1, 1));
		if ($letter === '' || in_array($letter, ['6', '7', '8'], true)) continue;
		if ($letter === 'c' && !$withResponsibility) continue;

		$value = trim(preg_replace('/\s+/u', ' ', (string) ($s['value'] ?? '')));
		if ($value === '' || $value === 'Enter value') continue;

		if ($out === '') {
			$out = $value;
		} elseif (preg_match('/[:;=\/.,!?]$/u', $out)) {
			$out .= ' ' . $value;                       // cataloger already typed the punctuation
		} else {
			$glue = $sep[$letter] ?? ' ';
			if ($letter === 'p' && $prev === 'n') $glue = ', ';   // "Part 2, Title of part"
			$out .= $glue . $value;
		}
		$prev = $letter;
	}

	$out = trim(preg_replace('/\s*[\/:;,=]+\s*$/u', '', $out));
	return $out === '' ? null : $out;
}

/**
 * Authors for joint_authors, joined with " / " (the separator citations, the OPAC and SEO split on):
 * the MARC main entries 100 (personal name, $a) and 110 (corporate name, $a + subordinate units $b),
 * in record order. A record with neither falls back to its added entries, 700 / 710 (same subfields)
 * — many catalogued items carry their authors only there.
 *
 * Each $a starts a new name and $b extends the one before it, so a field holding several names as
 * repeated $a (common in imported 700 / 710: "Fabian, E. F." $a "Payumo, F. M.") gives one author each.
 *
 * $fieldData: catalogs.field_data (JSON string or decoded array, the MarcEditor shape).
 */
function marcAuthors($fieldData): ?string
{
	$names = marcAuthorGroups($fieldData);
	$list  = $names['main'] ?: $names['added'];
	return $list ? implode(' / ', $list) : null;
}

/**
 * The author names of a record, split into main entries (100 / 110) and added entries (700 / 710):
 * ['main' => [...], 'added' => [...]], each in record order without duplicates. See marcAuthors().
 */
function marcAuthorGroups($fieldData): array
{
	$fields = is_array($fieldData) ? $fieldData : json_decode((string) $fieldData, true);
	if (!is_array($fields)) return ['main' => [], 'added' => []];

	$want  = ['100' => ['a'], '110' => ['a', 'b'], '700' => ['a'], '710' => ['a', 'b']];
	$names = ['main' => [], 'added' => []];
	foreach ($fields as $f) {
		$tag = is_array($f) ? (string) ($f['marctag']['code'] ?? '') : '';
		if (!isset($want[$tag])) continue;
		$group = $tag[0] === '1' ? 'main' : 'added';

		$found = [];
		foreach (($f['subfield'] ?? []) as $s) {
			$code = (string) ($s['code'] ?? '');
			$letter = substr($code, strrpos($code, '$') === false ? 0 : strrpos($code, '$') + 1, 1);
			if (!in_array($letter, $want[$tag], true)) continue;
			// Square brackets mark cataloger-supplied text ("[Author not identified]"); shown without them.
			$v = trim(preg_replace('/\s+/u', ' ', str_replace(['[', ']'], '', (string) ($s['value'] ?? ''))));
			if ($v === '' || $v === 'Enter value') continue;
			if ($letter === 'a' || !$found) {
				$found[] = $v;
			} else {
				// "Institute." + "Nutrition Division" -> "Institute. Nutrition Division"
				$last = array_pop($found);
				$found[] = preg_match('/[.,;:]$/u', $last) ? "$last $v" : "$last. $v";
			}
		}
		foreach ($found as $name) {
			$name = rtrim($name, " ,;:/=");   // ISBD trailing separators (a final "." of initials stays)
			if ($name !== '' && !in_array($name, $names[$group], true)) $names[$group][] = $name;
		}
	}

	return $names;
}

/**
 * Subject headings for view_catalogs.subjects and the search index: MARC 600, 610, 650, 651 and
 * 653 $a, in record order, without duplicates (ignoring case). Each $a is one heading (653
 * repeats $a for several keywords). See cleanSubject() for the punctuation clean-up.
 *
 * $fieldData: catalogs.field_data (JSON string or decoded array, the MarcEditor shape).
 */
function marcSubjects($fieldData): array
{
	$fields = is_array($fieldData) ? $fieldData : json_decode((string) $fieldData, true);
	if (!is_array($fields)) return [];

	$tags = ['600', '610', '650', '651', '653'];
	$out  = [];
	foreach ($fields as $f) {
		$tag = is_array($f) ? (string) ($f['marctag']['code'] ?? '') : '';
		if (!in_array($tag, $tags, true)) continue;
		foreach (($f['subfield'] ?? []) as $s) {
			$code = (string) ($s['code'] ?? '');
			if (substr($code, strrpos($code, '$') === false ? 0 : strrpos($code, '$') + 1, 1) !== 'a') continue;
			$v = cleanSubject($s['value'] ?? '');
			if ($v !== '') $out[mb_strtolower($v)] ??= $v;
		}
	}
	return array_values($out);
}

/**
 * One subject heading without its ISBD punctuation, so "Nutrition," and "Nutrition" are the same
 * subject. Brackets are dropped like in marcAuthors(); a closing "etc." keeps its period.
 */
function cleanSubject($value): string
{
	$v = trim(preg_replace('/\s+/u', ' ', str_replace(['[', ']'], '', (string) $value)));
	if ($v === 'Enter value') return '';
	$v = rtrim($v, " ,;:/=");
	if (!preg_match('/\betc\.$/iu', $v)) $v = rtrim($v, '. ');
	return trim($v);
}

/**
 * The publication year as a number, from pub_year's free text ("[1960]", "c1978", "1978-1980"
 * -> first year). Null when there is no plausible year (none, or later than next year).
 */
function pubYearNumber($pubYear): ?int
{
	if (!preg_match('/(?<!\d)(1[4-9]\d\d|20\d\d)(?!\d)/', (string) $pubYear, $m)) return null;
	$year = (int) $m[1];
	return $year <= (int) date('Y') + 1 ? $year : null;
}

/**
 * A search result value with its matched words in <mark>. Meilisearch wraps matches in private-use
 * markers (CatalogSearch::HL_PRE/HL_POST); the text is escaped first, then the markers become tags,
 * so catalog text can never inject HTML.
 */
function searchHighlight($text): string
{
	return str_replace([\App\Services\CatalogSearch::HL_PRE, \App\Services\CatalogSearch::HL_POST], ['<mark>', '</mark>'], e((string) $text));
}

/** Sort key for "Title A–Z": lower case, without a leading "The", "A" or "An" or opening bracket. */
function searchTitleSort($title): string
{
	$t = mb_strtolower(trim(strip_tags((string) $title)));
	$t = preg_replace('/^[\s\[\("\'“‘]+/u', '', $t);
	$t = preg_replace('/^(the|a|an)\s+/u', '', $t);
	return mb_substr($t, 0, 200);
}

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
