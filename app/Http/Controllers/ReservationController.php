<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Circulation;
use App\Models\CirculationReservation;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    
    public function index()
    {

    }

    public function reservations()
    {
        return view('admin.reservation');
    }

    public function reservation_action(Request $request) 
    {
        $adminUser = auth()->user();
        $request->validate(['reservation_code' => ['required', 'string']]);
        switch($request->reservation_action)
        {
            case 1:
                // approved = 2, and the copy becomes reserved = 2. The copy is claimed with a
                // conditional update (only while still Available), so two staff approving
                // tickets for the same copy at the same moment can't both get it.
                $approved = DB::transaction(function () use ($request, $adminUser) {
                    $ticket = DB::table('circulations')->where('reservation_code', $request->reservation_code)->lockForUpdate()->first();
                    if (! $ticket || $ticket->status != 1 || ! $ticket->catalog_id || ! $request->filled('circulation_number')) return false;

                    $claimed = DB::table('copies')
                        ->where('circulation_number', $request->circulation_number)
                        ->where('catalog_id', $ticket->catalog_id)
                        ->where('status', 1)
                        ->update(['status' => 2]);
                    if (! $claimed) return false;

                    DB::table('circulations')->where('id', $ticket->id)->update([
                        'circulation_number' => $request->circulation_number,
                        'approved_at' => now(),
                        'approved_by' => $adminUser->id,
                        'status' => 2,
                    ]);
                    return true;
                });

                $message = $approved ? "Request approved!" : "Material not available!";
                $header = $approved ? 'Success' : 'Failed';

                break;
            case 2:
                // declined = 4. Only a pending or approved ticket can be declined; an approved
                // one gives its reserved copy back to the shelf.
                DB::transaction(function () use ($request, $adminUser) {
                    $ticket = DB::table('circulations')->where('reservation_code', $request->reservation_code)->lockForUpdate()->first();
                    if (! $ticket || ! in_array((int) $ticket->status, [1, 2])) return;

                    DB::table('circulations')->where('id', $ticket->id)->update([
                        'declined_at' => now(),
                        'declined_by' => $adminUser->id,
                        'status' => 4,
                    ]);
                    releaseReservedCopies([$ticket->circulation_number]);
                });
                $message = "Request declined!";
                $header = 'Done';
                break;
            case 3:
                // PROCEED TO CHECKOUT
                $searchValue = $request->reservation_code;

                $transaction = DB::table('circulations')
                        ->join('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
                        ->join('circulation_fine_history', 'circulation_fine_history.transaction_code', '=', 'circulations.transaction_code')
                        ->where(function ($query) use ($searchValue) {
                            $query->where('circulations.transaction_code', $searchValue)
                                ->orWhere(function ($query) use ($searchValue) {
                                    $query->orWhere('circulation_number', $searchValue)
                                        ->whereNull('received_at');
                                    $query->orWhere('reservation_code', $searchValue)
                                        ->whereNull('received_at');
                                    
                                });
                        })
                        ->first();

                $material = searchMaterial($transaction);

                $patron = searchPatron($transaction);
                
                $patron_circulation = searchPatronCirculation($transaction);

                $result = computeHoursOverdue($transaction);

                $hoursOverdue = $result[1] == "hours_overdue" ? $hoursOverdue = $result[0] : 0;
                
                $overDueFine = $hoursOverdue > 0 ? checkIfFoundInt($transaction, 'overdue_fine_rate') * $hoursOverdue : 0;

                $reservation = [
                    // Material
                    'title' => checkIfFound($material, 'title'),
                    'circulation_number' => checkIfFound($material, 'circulation_number'),
                    'catalog_id' => checkIfFound($material, 'catalog_id'),
                    'copy_id' => checkIfFound($material, 'id'),
                    'status' => checkIfFound($material, 'status'),
                    'status_name' => checkIfFound($material, 'status_name'),
                    'status_description' => checkIfFound($material, 'status_desc'),
                    'cost' =>  checkIfFound($material, 'cost', 0),
                    // Patron
                    'name' => checkIfFound($patron, 'name'),
                    'email' => checkIfFound($patron, 'email'),
                    'contactnum' => checkIfFound($patron, 'contactnum'), 
                    'library_code' => checkIfFound($patron, 'library_code'),
                    'user_id' => checkIfFound($patron, 'id'),
                    'total_transaction' => checkIfFound($patron_circulation, 'total_transaction', 0),
                    'total_checkout' => checkIfFound($patron_circulation, 'total_checkout', 0),
                    'total_overdue' => checkIfFound($patron_circulation, 'total_overdue', 0),
                    // Terms
                    'reservation_code' => checkIfFound($transaction, 'reservation_code') == "N/A" ? "Walk-in" : checkIfFound($transaction, 'reservation_code'),
                    'due_date' => checkIfFound($transaction, 'due_date'),
                    'approved_at' => checkIfFound($transaction, 'approved_at'),
                    'approved_by' => staffName($transaction->approved_by ?? null),
                    'transaction_code' => checkIfFound($transaction, 'transaction_code'),
                    'transaction_status' => checkIfFound($transaction, 'name'),
                    'transaction_status_order_no' => checkIfFound($transaction, 'order_no', 6),
                ];

                $fineDescriptions = DB::table('circulation_fines')->select('id', 'name')->get();

                return view('admin.check-out-in', ['fineDescriptions' => $fineDescriptions, 'reservation' => $reservation]);
                
                break;
            case 4:
                // Revert an approval: the ticket goes back to for approval = 1 and the copy it
                // held (read from the ticket, not the form) back to available = 1.
                DB::transaction(function () use ($request) {
                    $ticket = DB::table('circulations')->where('reservation_code', $request->reservation_code)->lockForUpdate()->first();
                    if (! $ticket || $ticket->status != 2) return;

                    DB::table('circulations')->where('id', $ticket->id)->update([
                        'circulation_number' => NULL,
                        'approved_at' => NULL,
                        'approved_by' => NULL,
                        'status' => 1,
                    ]);
                    releaseReservedCopies([$ticket->circulation_number]);
                });

                $message = "Approval cancelled!";
                $header = 'Done';
                break;
        }

        return redirect()->route('reservations')->with(['message' => $message, 'header' => $header]);
    }
    

    public function reservation_json(Request $request)
    {

        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = $request->get("length"); // Rows display per page

        $columnIndex = $request->input('order.0.column');
        $columnName = $request->input("columns.$columnIndex.data");
        $columnSortOrder = strtolower((string) $request->input('order.0.dir')) === 'desc' ? 'desc' : 'asc';
        $searchValue = (string) $request->input('search.value', '');

        // circulations and circulation_reservations share id, user_id, reservation_code and the
        // timestamps, so every column is qualified: an unqualified one is "ambiguous" to MySQL,
        // and SELECT * silently took the reservation's copy. Only these table columns sort; the
        // rest (email, actions) are computed per row.
        $sortable = [
            'id'               => 'circulation_reservations.id',
            'title_statement'  => 'circulation_reservations.title_statement',
            'requested_by'     => 'circulation_reservations.requested_by',
            'status'           => 'circulations.status',
            'reservation_code' => 'circulations.reservation_code',
            'date_of_visit'    => 'circulation_reservations.visit_date',
        ];
        $sortColumn = $sortable[$columnName] ?? 'circulation_reservations.id';

        $active = Circulation::join('circulation_reservations', 'circulation_reservations.reservation_code', '=', 'circulations.reservation_code')
            ->whereIn('circulations.status', Circulation::ON_RESERVATIONS_PAGE);
        $filtered = (clone $active)->where(function ($query) use ($searchValue) {
            $query->where('circulation_reservations.title_statement', 'like', '%' . $searchValue . '%')
                ->orWhere('circulation_reservations.requested_by', 'like', '%' . $searchValue . '%')
                ->orWhere('circulation_reservations.visit_date', 'like', '%' . $searchValue . '%')
                ->orWhere('circulation_reservations.reservation_code', 'like', '%' . $searchValue . '%')
                ->orWhere('circulations.circulation_number', 'like', '%' . $searchValue . '%');
        });

        // Both totals count the rows the table shows (the filtered one used to count every
        // reservation title, whatever its status, so the pager was wrong).
        $totalRecords = (clone $active)->count();
        $totalRecordswithFilter = (clone $filtered)->count();

        $reservations = $filtered
            ->select('circulations.*', 'circulation_reservations.id as id', 'circulation_reservations.user_id as user_id',
                'circulation_reservations.requested_by', 'circulation_reservations.title_statement',
                'circulation_reservations.visit_date', 'circulation_reservations.visit_time')
            ->orderBy($sortColumn, $columnSortOrder)
            ->when($sortColumn === 'circulation_reservations.visit_date', fn ($q) => $q->orderBy('circulation_reservations.visit_time', $columnSortOrder))
            ->skip((int) $start)
            ->take((int) $rowPerPage)
            ->get();
    
        // Patron email + ban/block state, keyed by user id (kept out of the join above to
        // avoid ambiguous-column collisions between circulations / circulation_reservations).
        $patrons = \App\Models\User::whereIn('id', $reservations->pluck('user_id')->filter()->unique()->values())
                        ->get(['id', 'email', 'is_banned', 'is_blocked'])->keyBy('id');

        // Unseen chat messages from patrons, per reservation (drives the per-row badge).
        $unseen = DB::table('reservation_messages')
                        ->whereIn('reservation_code', $reservations->pluck('reservation_code')->filter()->unique()->values())
                        ->where('sender_role', 'patron')
                        ->whereNull('seen_at')
                        ->select('reservation_code', DB::raw('count(*) as c'))
                        ->groupBy('reservation_code')
                        ->pluck('c', 'reservation_code');

        $data_arr = array();

        foreach($reservations as $reservation){
            $title_statement = formatTitle1($reservation->title_statement);
            $status = getCirculationStatusName($reservation->status);
            $patron = $patrons[$reservation->user_id] ?? null;


            $data_arr[] = array(
                "id" => $reservation->id,
                "user_id" => $reservation->user_id,
                'requested_by'=> $reservation->requested_by,
                "email" => $patron->email ?? '',
                "is_banned" => $patron ? (int) $patron->is_banned : 0,
                "is_blocked" => $patron ? (int) $patron->is_blocked : 0,
                "unseen_msgs" => (int) ($unseen[$reservation->reservation_code] ?? 0),
                "title_statement" => $title_statement,
                "status" => $status,
                'reservation_code' => $reservation->reservation_code,
                "catalog_id" => $reservation->catalog_id,
                "circulation_number" => $reservation->circulation_number,
                "copy_id" => $reservation->copy_id,
                'date_of_visit' => visitWhen($reservation->visit_date, $reservation->visit_time),
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

    public function viewMaterial(Request $request)
    {
        
        $material = DB::table('copies')
            ->join('copies_status', 'copies_status.order_no', '=', 'copies.status')
            ->where('copies.catalog_id', $request->catalog_id)
            ->first();
        
        $circulation_numbers = DB::table('copies')
            ->select('circulation_number')
            ->where('catalog_id', $request->catalog_id)
            ->where('status', 1)
            ->get(); 

        $response = array(
            'catalog_id' => checkIfFound($material, 'catalog_id'),
            'circulation_numbers' => $circulation_numbers,
            'circulation_number' => $request->circulation_number,
            'title' => checkIfFound($material, 'title'),
            'cost' => checkIfFound($material, 'cost'), 
            'status' => checkIfFound($material, 'name'),
            'acquisition_mode' => checkIfFound($material, 'acquisition_mode'),
            'source' => checkIfFound($material, 'source'),
        );

        return response()->json($response); 
    }

    public function viewPatron(Request $request) 
    {
        $patron = DB::table('users')
            ->where('id', $request->user_id)
            ->first();


        $response = array(
            'name' => checkIfFound($patron, 'name'),
            'email' => checkIfFound($patron, 'email'),
            'contact_no' => checkIfFound($patron, 'contactnum'),
            'birthdate' => ! empty($patron->birthdate) ? Carbon::parse($patron->birthdate)->format('F j, Y') : 'Not found',
            'location' => checkIfFound($patron, 'location'),
            'position' => checkIfFound($patron, 'position1'),
            'sex' => checkIfFound($patron, 'sex'),
            'library_code' => checkIfFound($patron, 'library_code'),
        );

        return response()->json($response); 
    }

    public function viewTransaction(Request $request)
    {
        $transaction = DB::table('circulations')
        ->join('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
        ->where('transaction_code', $request->transaction_code)
        ->first();


        $response = array(
            'transaction_code' => checkIfFound($transaction, 'transaction_code'),
            'reservation_code' => checkIfFound($transaction, 'reservation_code'),
            'status' => checkIfFound($transaction, 'name'),
            'due_date' => checkIfFound($transaction, 'due_date'),
            'overdue_fine_rate' => checkIfFound($transaction, 'overdue_fine_rate'),
            'waived_amount' => checkIfFound($transaction, 'waived_amount'),
            'total_fine' => checkIfFound($transaction, 'total_fine'),
            'total_fine_paid' => checkIfFound($transaction, 'total_fine_paid'),
            'approved_at' => checkIfFound($transaction, 'approved_at'),
            'approved_by' => staffName($transaction->approved_by ?? null),
            'declined_at' => checkIfFound($transaction, 'declined_at'),
            'declined_by' => staffName($transaction->declined_by ?? null),
            'released_at' => checkIfFound($transaction, 'released_at'),
            'released_by' => staffName($transaction->released_by ?? null),
            'received_at' => checkIfFound($transaction, 'received_at'),
            'received_by' => staffName($transaction->received_by ?? null),
        );

        return response()->json($response); 
    }

    public function viewReservation(Request $request)
    {
        $reservation = DB::table('circulation_reservations')
            ->where('reservation_code', $request->reservation_code)
            ->first();

        $response = array(
            'visit_date' => ! empty($reservation->visit_date) ? Carbon::parse($reservation->visit_date)->format('F j, Y') : 'Not found',
            'visit_time' => ! empty($reservation->visit_time) ? Carbon::parse($reservation->visit_time)->format('g:i A') : 'Not found',
            'reservation_code' => checkIfFound($reservation, 'reservation_code'),
        );

        return response()->json($response);
    }

    // ---- Per-reservation admin<->patron chat (mirrors the inquiry reply thread) ----

    // Return the message thread for one reservation, oldest first.
    public function reservationChatJson($reservation_code)
    {
        $messages = DB::table('reservation_messages')
            ->leftJoin('users', 'users.id', '=', 'reservation_messages.sender_id')
            ->where('reservation_messages.reservation_code', $reservation_code)
            ->orderBy('reservation_messages.created_at')
            // What the chat shows (OWASP ASVS 15.3.1).
            ->select('reservation_messages.sender_role', 'reservation_messages.message', 'reservation_messages.created_at', 'users.name as sender_name')
            ->get();

        // Opening the thread marks the patron's messages as seen (clears the admin badge).
        DB::table('reservation_messages')
            ->where('reservation_code', $reservation_code)
            ->where('sender_role', 'patron')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        return response()->json($messages);
    }

    // Admin posts a reply into a reservation's thread.
    public function reservationChatReply(Request $request)
    {
        $request->validate([
            'reservation_code' => 'required|string|max:100',
            'message'          => 'required|string|max:5000',
        ]);

        // Resolve the patron this reservation belongs to (for thread ownership); no message for a
        // reservation that doesn't exist.
        $reservation = DB::table('circulation_reservations')
            ->where('reservation_code', $request->reservation_code)
            ->first();
        abort_unless($reservation, 404);

        DB::table('reservation_messages')->insert([
            'reservation_code' => $request->reservation_code,
            'user_id'          => $reservation->user_id ?? null,
            'sender_id'        => auth()->id(),
            'sender_role'      => 'admin',
            'message'          => $request->message,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['ok' => true]);
    }

}
