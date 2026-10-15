<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Circulation;
use App\Models\CirculationStatus;
use App\Models\CirculationFineHistory;
use App\Models\Holding;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionMail;
use App\Listeners\FailedSendingListener;

class CheckOutInController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function checkoutin()
    {
        $fineDescriptions = DB::table('circulation_fines')->select('id', 'name')->get();

        return view('admin.check-out-in', ['fineDescriptions' => $fineDescriptions]);
    }

    public function findMaterial(Request $request)
    {
        $materialObj = new \stdClass();
        
        $materialObj->circulation_number = $request->circulation_number;
        
        $material = searchMaterial($materialObj);

        $searchNotFound = "Material not found";

        $response = [
            'title' => $material ? $material->title : $searchNotFound,
            'circulation_number' => $material ? $material->circulation_number : $searchNotFound,
            'catalog_id' => $material ? $material->catalog_id : $searchNotFound,
            'copy_id' => $material ? $material->id : $searchNotFound,
            'status' => $material ? $material->status : $searchNotFound,
            'status_name' => $material ? $material->status_name : $searchNotFound,
            'status_description' => $material ? $material->status_desc : $searchNotFound,
            'cost' => $material ? $material->cost : 0,
            'material_type' => $material ? $material->material_type : $searchNotFound,
        ];
        
        return response()->json($response); 
    }

    public function findPatron(Request $request)
    {
        $patronObj = new \stdClass();
        
        $patronObj->user_library_code = $request->library_code;
        
        $patron = searchPatron($patronObj);
        
        $patron_circulation = searchPatronCirculation($patronObj);

        $searchNotFound = "Patron not found";
        
        $response = [
            'name' => $patron ? $patron->name: "$searchNotFound" ,
            'email' => $patron ? $patron->email : "$searchNotFound",
            'contactnum' => $patron ? $patron->contactnum : "$searchNotFound", 
            'library_code' => $patron ? $patron->library_code : "$searchNotFound",
            'user_id' => $patron ? $patron->id : "$searchNotFound",
            'total_transaction' => $patron_circulation->total_transaction,
            'total_checkout' => $patron_circulation->total_checkout,
            'total_overdue' => $patron_circulation->total_overdue ? $patron_circulation->total_overdue : 0,
        ];
        
        return response()->json($response); 
    }

    public function checkoutStore(Request $request)
    {

        $transactionCode = generateTransactionCode();

        $adminUser = auth()->user();

        // The patron's account, if one was found (the screen posts placeholder text otherwise).
        $userId = User::whereKey((int) $request->user_id)->value('id');

        // Walk-in check-out: the copy is claimed (Available -> Borrowed) and the loan and its
        // fine record are written in one transaction. The copy is locked first, so a copy that
        // is reserved or already out can't be checked out again.
        $checkedOut = DB::transaction(function () use ($request, $adminUser, $transactionCode, $userId) {
            if (! $request->filled('circulation_number')) return false;
            $copy = DB::table('copies')->where('circulation_number', $request->circulation_number)->lockForUpdate()->first();
            if (! $copy || $copy->status != 1) return false;

            //Update material status to "Borrowed"
            DB::table('copies')->where('id', $copy->id)->update(['status' => 3]);

            Circulation::create([
                'overdue_fine_rate' => fineAmount($request->overdue_fine_rate),
                'due_date' => $request->duedate,
                'transaction_code' => $transactionCode,
                'released_at' => now(),
                'released_by' => $adminUser->name,
                'user_library_code' => $request->patron_library_code,
                'circulation_number' => $copy->circulation_number,
                'catalog_id' => $copy->catalog_id,
                'user_id' => $userId,
                'copy_id' => $copy->id,
                'status' => 3,
            ]);

            CirculationFineHistory::create([
                'transaction_code' => $transactionCode,
            ]);

            return true;
        });

        if (! $checkedOut) {
            return redirect()->route('checkoutin')->with(['error' => 'This copy is not available for check-out. A reserved copy is checked out from its reservation.']);
        }

        if($request->patron_library_code != "N/A") {
            $user = DB::table('users')->where('library_code', $request->patron_library_code)->first();

            // $mailable = new TransactionMail(['transaction_code' => $transactionCode, 'name' => $user->name, 'type' => 'checkout']);

            // Register the FailedSendingListener
            // Mail::getSwiftMailer()->registerPlugin(new FailedSendingListener);

            // $emailSent = Mail::to($user->email)->send($mailable);
        }

        return redirect()->route('checkoutin')->with([
            'success' => '"Check-out complete! Please see your code below."!',
            'transactionCode' => $transactionCode]);
    }

    // Check out an approved reservation (or change the terms of a loan that is already out).
    public function checkoutUpdate(Request $request) {
        $adminUser = auth()->user();

        $updated = DB::transaction(function () use ($request, $adminUser) {
            if (! $request->filled('transaction_code')) return false;
            $ticket = DB::table('circulations')->where('transaction_code', $request->transaction_code)->lockForUpdate()->first();
            if (! $ticket || ! in_array((int) $ticket->status, [2, 3])) return false;

            DB::table('circulations')->where('id', $ticket->id)->update([
                'due_date' => $request->duedate,
                'overdue_fine_rate' => fineAmount($request->overdue_fine_rate),
                'status' => 3,
                'released_at' => now(),
                'released_by' => $adminUser->name,
            ]);

            // The copy held for the reservation is now out on loan (reserved 2 -> borrowed 3).
            if ($ticket->circulation_number) {
                DB::table('copies')->where('circulation_number', $ticket->circulation_number)->where('status', 2)->update(['status' => 3]);
            }
            return true;
        });

        if (! $updated) {
            return redirect()->route('checkoutin')->with(['error' => 'Only an approved reservation or an open loan can be checked out.']);
        }

        return redirect()->route('checkoutin')->with([
            'success' => 'Check-out updated successfully!',
            'transactionCode' => $request->transaction_code]);
    }


    public function checkinStore(Request $request)
    {
        $adminUser = auth()->user();

        // The return, the copy going back on the shelf and the fines are recorded together.
        $received = DB::transaction(function () use ($request, $adminUser) {
            if (! $request->filled('transaction_code')) return false;
            $ticket = DB::table('circulations')->where('transaction_code', $request->transaction_code)->lockForUpdate()->first();
            if (! $ticket) return false;

            $copyNumber = $ticket->circulation_number ?: $request->circulation_code;
            if ($copyNumber) {
                DB::table('copies')->where('circulation_number', $copyNumber)->update(['status' => 1]);
            }

            DB::table('circulations')->where('id', $ticket->id)->update([
                'received_at' => now(),
                'received_by' => $adminUser->name,
                'total_fine_paid' => fineAmount($request->total_fine),
                'waived_amount' => fineAmount($request->waive_amount),
                'status' => 5,
            ]);

            DB::table('circulation_fine_history')->where('transaction_code', $ticket->transaction_code)->update([
                'overdue_fine_amount' => fineAmount($request->overdue_fine),
                'overdue_total_hours' => is_numeric($request->total_hrs_overdue) ? max(0, (int) $request->total_hrs_overdue) : null,
                'other_fine_amount' => fineAmount($request->other_fine),
                // The screen posts its placeholder ("Select description...", or 0) when no reason was picked.
                'circulation_fine_id' => DB::table('circulation_fines')->where('id', (int) $request->circulation_fine_id)->value('id'),
                'other_fine_description' => $request->other_fine_desc,
                'fine_note' => $request->fine_note,
            ]);

            return true;
        });

        if (! $received) {
            return redirect()->route('checkoutin')->with(['error' => 'Transaction not found.']);
        }

        if($request->library_code != "N/A") {

            $user = DB::table('users')->where('library_code', $request->library_code)->first();

            // Notify the patron of the check-in, after the return is recorded. Best-effort only:
            // a mail failure must not undo the return. (Laravel 9+ removed
            // Mailer::getSwiftMailer(); the old SwiftMailer failure listener no longer applies.)
            if($user) {
                try {
                    $mailable = new TransactionMail(['transaction_code' => $request->transaction_code, 'name' => $user->name, 'type' => 'checkin']);
                    Mail::to($user->email)->send($mailable);
                } catch (\Throwable $e) {
                    \Log::warning('Check-in notification email failed: '.$e->getMessage());
                }
            }
        }

        return redirect()->route('checkoutin')->with(['complete' => 'Transaction Complete!']);
    }

    public function checkinSearch(Request $request)
    {
        $transaction = DB::table('circulations')
            ->join('circulation_status', 'circulation_status.order_no', '=', 'circulations.status')
            ->join('circulation_fine_history', 'circulation_fine_history.transaction_code', '=', 'circulations.transaction_code')
            ->where(function ($query) use ($request) {
                $query->where('circulations.transaction_code', $request->search_transaction)
                    ->orWhere(function ($query) use ($request) {
                        $query->where('circulation_number', $request->search_transaction)
                            ->whereNull('received_at');
                    });
            })->first();

        $material = searchMaterial($transaction);

        $patron = searchPatron($transaction);
        
        $patron_circulation = searchPatronCirculation($transaction);



        $result = computeHoursOverdue($transaction);

        $hoursOverdue = $result[1] == "hours_overdue" ? $hoursOverdue = $result[0] : 0;
        
        $overDueFine = $hoursOverdue > 0 ? checkIfFoundInt($transaction, 'overdue_fine_rate') * $hoursOverdue : 0;

        $response = [
            // Material
            'title' => checkIfFound($material, 'title'),
            'circulation_number' => checkIfFound($material, 'circulation_number'),
            'catalog_id' => checkIfFound($material, 'id'),
            'status' => checkIfFound($material, 'status'),
            'status_name' => checkIfFound($material, 'status_name'),
            'status_description' => checkIfFound($material, 'status_desc'),
            'cost' =>  checkIfFound($material, 'cost', 0),
            'material_type' => checkIfFound($material, 'material_type'),
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
            'released_date' => checkIfFound($transaction, 'released_at'),
            'released_by' => checkIfFound($transaction, 'released_by'),
            'overdue_fine_rate' => checkIfFoundInt($transaction, 'overdue_fine_rate'),
            'transaction_code' => checkIfFound($transaction, 'transaction_code'),
            'transaction_status' => checkIfFound($transaction, 'name'),
            'transaction_status_order_no' => checkIfFound($transaction, 'order_no', 5),
            'waived_amount' => checkIfFoundInt($transaction, 'waived_amount'),
            'total_fine_paid' => checkIfFoundInt($transaction, 'total_fine_paid', $overDueFine),
            'other_fine_amount' => checkIfFoundInt($transaction, 'other_fine_amount'),
            'overdue_fine_amount' => checkIfFoundInt($transaction, 'overdue_fine_amount', $overDueFine),
            'overdue_total_hours' => checkIfFoundInt($transaction, 'overdue_total_hours', $hoursOverdue),
            'other_fine_description' => checkIfFound($transaction, 'other_fine_description'),
            'circulation_fine_id' => checkIfFound($transaction, 'circulation_fine_id'),
            'fine_note' => checkIfFound($transaction, 'fine_note'),
        ];
        
        return response()->json($response);
    }

    public function checkoutSearch(Request $request)
    {

        $searchValue = $request->search_transaction;
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

        $response = [
            // Material
            'title' => checkIfFound($material, 'title'),
            'circulation_number' => checkIfFound($material, 'circulation_number'),
            'catalog_id' => checkIfFound($material, 'catalog_id'),
            'copy_id' => checkIfFound($material, 'id'),
            'status' => checkIfFound($material, 'status'),
            'status_name' => checkIfFound($material, 'status_name'),
            'status_description' => checkIfFound($material, 'status_desc'),
            'cost' =>  checkIfFound($material, 'cost', 0),
            'material_type' => checkIfFound($material, 'material_type'),
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
            'released_date' => checkIfFound($transaction, 'released_at'),
            'released_by' => checkIfFound($transaction, 'released_by'),
            'overdue_fine_rate' => checkIfFoundInt($transaction, 'overdue_fine_rate'),
            'transaction_code' => checkIfFound($transaction, 'transaction_code'),
            'transaction_status' => checkIfFound($transaction, 'name'),
            'transaction_status_order_no' => checkIfFound($transaction, 'order_no', 6),
            'waived_amount' => checkIfFoundInt($transaction, 'waived_amount'),
            'total_fine_paid' => checkIfFoundInt($transaction, 'total_fine_paid', $overDueFine),
            'other_fine_amount' => checkIfFoundInt($transaction, 'other_fine_amount'),
            'overdue_fine_amount' => checkIfFoundInt($transaction, 'overdue_fine_amount', $overDueFine),
            'overdue_total_hours' => checkIfFoundInt($transaction, 'overdue_total_hours', $hoursOverdue),
            'other_fine_description' => checkIfFound($transaction, 'other_fine_description'),
            'circulation_fine_id' => checkIfFound($transaction, 'circulation_fine_id'),
            'fine_note' => checkIfFound($transaction, 'fine_note'),
        ];
        
        return response()->json($response);
    }
}
