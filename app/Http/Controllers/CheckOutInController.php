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

        //Update material status to "Borrowed"
        DB::table('copies')->where('circulation_number', $request->circulation_number)->update(['status' => 3]);

        $emailSent = NULL;

        if($request->patron_library_code != "N/A") {
            $user = DB::table('users')->where('library_code', $request->patron_library_code)->first();

            // $mailable = new TransactionMail(['transaction_code' => $transactionCode, 'name' => $user->name, 'type' => 'checkout']);

            // Register the FailedSendingListener
            // Mail::getSwiftMailer()->registerPlugin(new FailedSendingListener);

            // $emailSent = Mail::to($user->email)->send($mailable);
        }

        // if(is_null($emailSent)) {
            $transaction = Circulation::create([
                'overdue_fine_rate' => $request->overdue_fine_rate,
                'due_date' => $request->duedate,
                'transaction_code' => $transactionCode,
                'released_at' => now(),
                'released_by' => $adminUser->name,
                'user_library_code' => $request->patron_library_code,
                'circulation_number' => $request->circulation_number,
                'catalog_id' =>  $request->catalog_id,
                'user_id' => $request->user_id,
                'copy_id' => $request->copy_id,
                'status' => 3,
            ]);

            CirculationFineHistory::create([
                'transaction_code' => $transactionCode,
            ]);
        // }  else {
        //     return redirect()->route('checkoutin')->with(['error' => 'Error! Transaction failed.']);     
        // }
        return redirect()->route('checkoutin')->with([
            'success' => '"Check-out complete! Please see your code below."!',
            'transactionCode' => $transactionCode]);
    }

    public function checkoutUpdate(Request $request) {
        $adminUser = auth()->user();

        DB::table('circulations')->where('transaction_code', $request->transaction_code)->update([
            'due_date' => $request->duedate,
            'overdue_fine_rate' => $request->overdue_fine_rate,
            'status' => 3,
            'released_at' => now(),
            'released_by' => $adminUser->name,
        ]);

        return redirect()->route('checkoutin')->with([
            'success' => 'Check-out updated successfully!',
            'transactionCode' => $request->transaction_code]);
    }


    public function checkinStore(Request $request)
    {
        $adminUser = auth()->user();

        DB::table('copies')->where('circulation_number', $request->circulation_code)->update(['status' => 1]);

        if($request->library_code != "N/A") {

            $user = DB::table('users')->where('library_code', $request->library_code)->first();

            // Notify the patron of the check-in. Best-effort only: a mail failure must not
            // block recording the return. (Laravel 9+ removed Mailer::getSwiftMailer(); the
            // old SwiftMailer failure listener no longer applies.)
            if($user) {
                try {
                    $mailable = new TransactionMail(['transaction_code' => $request->transaction_code, 'name' => $user->name, 'type' => 'checkin']);
                    Mail::to($user->email)->send($mailable);
                } catch (\Throwable $e) {
                    \Log::warning('Check-in notification email failed: '.$e->getMessage());
                }
            }
        }

        // Record the return regardless of whether the notification email went out.
        DB::table('circulations')->where('transaction_code', $request->transaction_code)->update([
            'received_at' => now(),
            'received_by' => $adminUser->name,
            'total_fine_paid' => $request->total_fine,
            'waived_amount' => $request->waive_amount,
            'status' => 5,
        ]);

        DB::table('circulation_fine_history')->where('transaction_code', $request->transaction_code)->update([
            'transaction_code' => $request->transaction_code,
            'overdue_fine_amount' => $request->overdue_fine,
            'overdue_total_hours' => $request->total_hrs_overdue,
            'other_fine_amount' => $request->other_fine,
            'circulation_fine_id' => $request->circulation_fine_id,
            'other_fine_description' => $request->other_fine_desc,
            'fine_note' => $request->fine_note,
        ]);

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
