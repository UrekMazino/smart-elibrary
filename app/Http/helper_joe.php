<?php

function checkCSF()
{
	if(Auth::user()) {
		$csf = App\Models\Csf::where('user_id',Auth::user()->id)->count();
		if($csf > 0)
			return true;
		else
			return false;
	}
}


function generateLibraryCode() {
	$year = date("Y");
	$randomString = strtoupper(Str::random(5)); // Generate a random string of length 8
	$randomNumber = random_int(100000, 999999);
	$combinedString = $year . "-". $randomNumber . $randomString ; // Combine the random and unique strings
	$matchCode = App\Models\User::where('library_code', $combinedString)->first();
	if(!is_null($matchCode)) {
		$combinedString = generateLibraryCode();
	}
	return $combinedString;

}

function updateLibraryCode($id) {
	$year = date("Y");
	$randomString = strtoupper(Str::random(5)); // Generate a random string of length 8
	$randomNumber = random_int(100000, 999999);
	$combinedString = $year . "-". $randomNumber . $randomString ; // Combine the random and unique strings
	$matchCode = App\Models\User::where('library_code', $combinedString)->first();
	if(!is_null($matchCode)) {
		$combinedString = generateLibraryCode();
	}

	$result = DB::table('users')->where('id', $id)->update([
		'library_code' => $combinedString,
	]);

	DB::table('circulations')->where('user_id', $id)->update([
		'user_library_code' => $combinedString,
	]);
	return $combinedString;

}


function generateTransactionCode() {
	$timestamp = time();
	$randomString = strtoupper(Str::random(2));
	$combinedString = 'TRN-' . $randomString . $timestamp;
	$matchCode = App\Models\Circulation::where('transaction_code', $combinedString)->first();
	if(!is_null($matchCode)) {
		$combinedString = generateTransactionCode();
	}
	return $combinedString;

}

function generateReservationCode() {
	$timestamp = time();
	$randomString = strtoupper(Str::random(2));
	$combinedString = 'RSV-' . $randomString . $timestamp;
	$matchCode = App\Models\Circulation::where('reservation_code', $combinedString)->first();
	if(!is_null($matchCode)) {
		$combinedString = generateReservationCode();
	}
	return $combinedString;

}

function generateRandomPassword() {
	
	$randomString = strtoupper(Str::random(8));

	return $randomString;

}

function searchMaterial($transaction) {
	if($transaction) {
		$result = DB::table('copies')
            ->select('copies.title', 'copies.circulation_number', 'copies.catalog_id', 'copies.id', 'copies.cost', 'copies.status', 'copies_status.name as status_name', 'copies_status.description as status_desc', 'materials.name as material_type')
            ->join('copies_status', 'copies.status', '=', 'copies_status.order_no')
			->join('catalogs', 'copies.catalog_id', '=', 'catalogs.id')
			->join('materials', 'catalogs.material_type_id', '=', 'materials.id')
            ->where('copies.circulation_number', $transaction->circulation_number)
            ->first();
	} else {
		$result = 0;
	}
	
	return $result;
}

function searchPatron($transaction) {
	if($transaction) {
		$result = DB::table('users')
				->select('name', 'email', 'contactnum', 'library_code', 'id')
				->when($transaction->user_library_code, function ($query) use ($transaction) {
					$query->where('library_code', 'like', '%' . $transaction->user_library_code . '%')
						->orWhere('email', 'like', '%' . $transaction->user_library_code . '%')
						->orWhere('name', 'like', '%' . $transaction->user_library_code . '%')
						->orWhere('contactnum', 'like', '%' . $transaction->user_library_code . '%');
				})
				->when(!$transaction->user_library_code, function ($query) use ($transaction) {
					$query->where('id', $transaction->user_id);
				})
				->first();
	} else {
		$result = 0;
	}
	return $result;
}

function searchPatronCirculation($transaction) {
	if($transaction) {
		$result = DB::table('circulations')
			->select(
				DB::raw('count(id) as total_transaction'),
				DB::raw('count(CASE WHEN status = 3 THEN 1 END) as total_checkout'),
				DB::raw('SUM(CASE WHEN status = 3 AND due_date < NOW() THEN 1 ELSE 0 END) as total_overdue')
			)
			->whereIn('status', [3, 5])
			->where('user_library_code', $transaction->user_library_code)
			->first();
	} else {
		$result = 0;
	} 
	return $result;
}

function checkIfFound($result, $column, $message = 'Not found') {
	if($result) {
		if(is_null($result->$column)) {
			if($message == 'Walk-in') {
				return $message;
			}
			return $message = 'N/A';
		} 
		return $result->$column;
	} else {
		return $message;
	}
}

function checkIfFoundInt($result, $column, $message = 0) {
	if($result) {
		
		return is_null($result->$column) ? $message : $result->$column;

	} else 
	{
		return $message;
	}
}


function computeHoursOverdue($transaction) {
	
	if($transaction) {
		$dueDate = Carbon\Carbon::parse($transaction->due_date);

		$currentDateTime = Carbon\Carbon::now();
		
		$hrsDiff = $currentDateTime->diffInHours($dueDate);

		if ($dueDate->isPast()) {

			$overdue = abs($hrsDiff) + 1;

			$result = [$overdue, "hours_overdue"];
			
			return $result;

		} else {

			$remaining = $hrsDiff;	

			$result = [$remaining, "hours_remaining"];
			
			return $result;
		}
	}
	return [0, "hours_overdue"];

}

function getCirculationStatusName($orderNo) {
	
	$status =  DB::table('circulation_status')->select('name')->where('order_no', $orderNo)->first();

	return $status->name;
}

function getStatus() {
	
	$status =  DB::table('copies_status')->select('order_no', 'name')->get();

	return $status;
}

function getReservationCount() {

	$totalRecords = App\Models\Circulation::whereIn('status', [1, 2])->count();

	return $totalRecords;
}

// Records on the On-Hold list: not published yet (new records start there), not deleted.
function getOnHoldCount() {
	return App\Models\ViewCatalog::where('is_published', 0)->whereNull('deleted_at')->count();
}

// Put copies held for an approved reservation (copies.status 2 = Reserved) back on the shelf
// (1 = Available) when that ticket is declined, reverted or its patron banned. Without this the
// copy stays Reserved for nobody. Copies in any other state are left alone.
function releaseReservedCopies($circulationNumbers) {
	$numbers = collect($circulationNumbers)->filter()->unique()->values();
	if ($numbers->isEmpty()) return 0;

	return DB::table('copies')->whereIn('circulation_number', $numbers)->where('status', 2)->update(['status' => 1]);
}

// A reservation's visit, e.g. "Oct 20, 2026, 9:00 AM" (visit_date is a DATE, visit_time a TIME).
function visitWhen($date, $time) {
	$parts = array_filter([
		$date ? Carbon\Carbon::parse($date)->format('M j, Y') : null,
		$time ? Carbon\Carbon::parse($time)->format('g:i A') : null,
	]);
	return $parts ? implode(', ', $parts) : '—';
}

// The name of the staff account behind a *_by column (circulations.approved_by etc. hold user
// ids). Deleted accounts still show their name.
function staffName($userId, $default = 'Not found') {
	if (! $userId) return $default;
	return App\Models\User::withTrashed()->whereKey($userId)->value('name') ?? $default;
}

// Money posted by the check-out / check-in screens as a number, or null when blank. The screen
// posts 'NaN' when an amount box is left empty.
function fineAmount($value) {
	return is_numeric($value) ? round((float) $value, 2) : null;
}

// A news article's body as the editor may hold it: the formatting the TinyMCE toolbar produces
// (headings, lists, links, images, tables, alignment) and nothing else. Scripts, event handlers,
// iframes, embedded media, comments and TinyMCE's own data-mce-* / mce:protected markup are
// dropped: those are how the known TinyMCE 6 XSS advisories reach a staff member's browser when
// a crafted body is opened in the editor. Applied on save and again when the editor loads.
function cleanNewsHtml(?string $html): string {
	static $sanitizer = null;
	$sanitizer ??= new Symfony\Component\HtmlSanitizer\HtmlSanitizer(
		(new Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
			->allowSafeElements()
			->allowLinkSchemes(['http', 'https', 'mailto'])
			->allowMediaSchemes(['http', 'https'])
			->allowRelativeLinks()
			->allowRelativeMedias()
			->allowAttribute('style', ['p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'table', 'td', 'th', 'li'])
			->forceAttribute('a', 'rel', 'noopener noreferrer')
			->withMaxInputLength(200000)
	);
	return $sanitizer->sanitize((string) $html);
}

// Unseen chat messages FROM patrons on the reservations the Reservations page lists (drives its
// menu badge). A declined or closed reservation's messages count under Inquiries instead.
function getReservationUnseenMsgCount() {
	return DB::table('reservation_messages as m')
		->join('circulations as c', 'c.reservation_code', '=', 'm.reservation_code')
		->whereIn('c.status', App\Models\Circulation::ON_RESERVATIONS_PAGE)
		->where('m.sender_role', 'patron')
		->whereNull('m.seen_at')
		->count();
}

function getTitleFromHoldingCopy($id)
{
	$record = DB::table('copies')->where('id', $id)->select('title')->first();
	if(isset($record)) 
	{
		return  formatTitle1($record->title);
	}
	else
	{
		return 'Title not found';
	}
}

function getUserName($user_id)
{
	$user = DB::table('users')->select('name')->where('id', $user_id)->first();

	return $user == null ? "Can't find Patron" : $user->name;
}


function getIndDesc($id)
{
	$indicator = DB::table('marctag_indicators_loc')->select('id','indicator')->where('id', $id)->first();

	return $indicator;
}

// The MARC 21 reference tables (marctags_loc, marctag_subfields_loc, marctag_indicators_loc) never
// change while the app runs, and importing one record asks about 300 questions about the same few
// dozen codes. Each answer is kept for the rest of the request: the database is still asked once
// per distinct question, exactly as before, instead of every time.
function marcLookup(string $question, Closure $answer)
{
	if (! app()->bound('marc.lookups')) app()->instance('marc.lookups', new ArrayObject());
	$memo = app('marc.lookups');
	if (! $memo->offsetExists($question)) $memo[$question] = $answer();
	return $memo[$question];
}

// marctags_loc row 2500 ("999 · Marctag not found") stands in for a tag the tables don't have (a local
// one outside 900–999 such as 090 or 590, an obsolete one): its fields get this id (for the indicator
// lookups) and their tag number as name. 900–999 have rows of their own, also named by number.
const MARCTAG_NOT_FOUND_ID = 2500;

function getTagDetails($id, $code = null, $col = null)
{
	return marcLookup(__FUNCTION__ . json_encode(func_get_args()), function () use ($id, $code, $col) {
		if(is_null($code)) {
			$marcTag = DB::table('marctags_loc')->select('id','code', 'description')->where('id', $id)->first();
			return $marcTag;
		} else {
			// The placeholder row carries the code 999 too; the real 999 is a local tag of its own.
			$marcTag = DB::table('marctags_loc')->select('id','code', 'description')->where('id', '<>', MARCTAG_NOT_FOUND_ID)
				->where('code',  'like', '%' . $code . '%')->first();

			// Not in the tag tables (a local tag outside 900–999, an obsolete one): the tag number is its name.
			if (is_null($marcTag)) {
				return in_array($col, ['code', 'description'], true) ? $code : MARCTAG_NOT_FOUND_ID;
			}
			return !is_null($col) ? $marcTag->$col : $marcTag->id;

		}
	});
}

function getSubfieldDetails($id)
{
	$subfield = DB::table('marctag_subfields_loc')->select('id','code', 'description')->where('id', $id)->first();

	return $subfield;
}

function getIndicatorDetails($id)
{
	$indicator = DB::table('marctag_indicators_loc')->select('id','indicator')->where('id', $id)->first();

	return $indicator;
}

function getMaterialData($id)
{
	$material = DB::table('materials')->where('id', $id)->first();

	return $material;
}

function getMaterialDataCUsingCode($code)
{
	$material = DB::table('materials')->where('code', $code)->first();

	return $material;
}
/**
 * Map a MARC indicator character as read from a record to the leading character used in the
 * marctag_indicators_loc labels. File_MARC returns a space ' ' for a blank indicator, but the
 * reference table writes blank as '#' (LoC's documentation convention), so a blank in a parsed
 * record must be canonicalised to '#' to resolve to the "# - ..." row instead of falling through
 * to the default. Mirrors the editor-side normaliser (marcIndicatorChar), which stores blank as
 * a space; here the match target is the table's '#'.
 */
function marcFileIndToTableChar($ch)
{
	$ch = (string) $ch;
	return ($ch === '' || $ch === ' ' || $ch === '\\') ? '#' : substr($ch, 0, 1);
}

function getFirstIndicatorId($tagId, $indCode = null, $col = null){
	return marcLookup(__FUNCTION__ . json_encode(func_get_args()), function () use ($tagId, $indCode, $col) {
		$marcTag = DB::table('marctags_loc')->select('first_indictors')->where('id', $tagId)->first();

		$ind1 = json_decode($marcTag->first_indictors);

		if(count($ind1) > 0) {
			$ind1Collection = DB::table('marctag_indicators_loc')->whereIn('id', $ind1)->get();


			if(!is_null($ind1Collection)) {
				foreach($ind1Collection as $col) {
					$parts = explode(' ', $col->indicator);

					// Get the first element (index 0) from the array
					$code = $parts[0];

					if($code == marcFileIndToTableChar($indCode)) {
						if($col->id != null) {
							return $col == "indicator" ? $col->indicator : $col->id;
						}
					}
				}
			}


		}

		return $col == "indicator" ? "# - Undefined" : 1;
	});
}

function getSecondIndicatorId($tagId, $indCode = null)
{
	return marcLookup(__FUNCTION__ . json_encode(func_get_args()), function () use ($tagId, $indCode) {
		$marcTag = DB::table('marctags_loc')->select('second_indicators')->where('id', $tagId)->first();
		$ind2 = json_decode($marcTag->second_indicators);
	

		if(count($ind2) > 0) {
			$ind2Collection = DB::table('marctag_indicators_loc')->whereIn('id', $ind2)->get();


			if(!is_null($ind2Collection)) {
				foreach($ind2Collection as $col) {
					$parts = explode(' ', $col->indicator);

					// Get the first element (index 0) from the array
					$code = $parts[0];

					if($code == marcFileIndToTableChar($indCode)) {
						if($col->id != null) {
							return $col->id;
						}
					}
				}
			}

			return 1;
		}
	});
}

function getSubfieldId($code)
{
	return marcLookup(__FUNCTION__ . json_encode(func_get_args()), function () use ($code) {
		$subfield = DB::table('marctag_subfields_loc')->select('id')->where('code',  'like', '%' . $code . '%')->first();
		return $subfield?->id;   // null for a subfield of a tag outside MARC 21: no row to point at
	});
}

function getSubfield($code, $col)
{
	return marcLookup(__FUNCTION__ . json_encode(func_get_args()), function () use ($code, $col) {
		$subfield = DB::table('marctag_subfields_loc')->select('id', 'code', 'description')->where('code',  'like', '%' . $code . '%')->first();
		if(is_null($subfield)) {
			return $col === 'id' ? null : $code;   // a subfield of a tag the tables don't have: its code ("090$a") is its name
		}
		return $subfield->$col;
	});
}

function getCopiesStatus()
{
	$status = DB::table('copies_status')->get();

	return $status;
}

function getPermissions()
{

	$permissions = DB::table('permissions')->select('id', 'display_name')->orderBy('display_name', 'asc')->get();
	
	return $permissions;
}

function getRoles()
{

	$roles = DB::table('roles')->select('id', 'display_name')->get();
	
	return $roles;
}