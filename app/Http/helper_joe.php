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
		$combinedString = generateTransactionCode();
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

// Total unseen chat messages FROM patrons (drives the admin Reservation menu badge).
function getReservationUnseenMsgCount() {
	return DB::table('reservation_messages')
		->where('sender_role', 'patron')
		->whereNull('seen_at')
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

function getTagDetails($id, $code = null, $col = null)
{
	if(is_null($code)) {
		$marcTag = DB::table('marctags_loc')->select('id','code', 'description')->where('id', $id)->first();
		return $marcTag;
	} else {
		$marcTag = DB::table('marctags_loc')->select('id','code', 'description')->where('code',  'like', '%' . $code . '%')->first();
		
		if(!is_null($marcTag)){
			return !is_null($col) ? $marcTag->$col : $marcTag->id;
		} 
		// id 2500 meaning Marc Tag not Found
		$id = 2500;
		return $id;
		
	}

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
function getFirstIndicatorId($tagId, $indCode = null, $col = null){
	
		$marcTag = DB::table('marctags_loc')->select('first_indictors')->where('id', $tagId)->first();
		
		$ind1 = json_decode($marcTag->first_indictors);
		
		if(count($ind1) > 0) {
			$ind1Collection = DB::table('marctag_indicators_loc')->whereIn('id', $ind1)->get();


			if(!is_null($ind1Collection)) {
				foreach($ind1Collection as $col) {
					$parts = explode(' ', $col->indicator);

					// Get the first element (index 0) from the array
					$code = $parts[0];

					if($code == $indCode) {
						if($col->id != null) {
							return $col == "indicator" ? $col->indicator : $col->id;
						}
					}
				}
			}

			
		}

		return $col == "indicator" ? "# - Undefined" : 1;

}

function getSecondIndicatorId($tagId, $indCode = null)
{
	$marcTag = DB::table('marctags_loc')->select('second_indicators')->where('id', $tagId)->first();
	$ind2 = json_decode($marcTag->second_indicators);
	

	if(count($ind2) > 0) {
		$ind2Collection = DB::table('marctag_indicators_loc')->whereIn('id', $ind2)->get();


		if(!is_null($ind2Collection)) {
			foreach($ind2Collection as $col) {
				$parts = explode(' ', $col->indicator);

				// Get the first element (index 0) from the array
				$code = $parts[0];

				if($code == $indCode) {
					if($col->id != null) {
						return $col->id;
					}
				}
			}
		}

		return 1;
	}
	
}

function getSubfieldId($code)
{
	$subfield = DB::table('marctag_subfields_loc')->select('id')->where('code',  'like', '%' . $code . '%')->first();
	if(is_null($subfield)) {
		return "MARCTAG ($code) not found!";
	}
	return $subfield ? $subfield->id : 0;
}

function getSubfield($code, $col)
{

	$subfield = DB::table('marctag_subfields_loc')->select('id', 'code', 'description')->where('code',  'like', '%' . $code . '%')->first();
	if(is_null($subfield)) {
		return "MARCTAG ($code) not found!";
	}
	return $subfield->$col;
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