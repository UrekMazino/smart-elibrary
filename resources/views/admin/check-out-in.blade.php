@extends("admin.layouts.master")

@section('css')
    <style>
      #popup-message {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 10px 20px;
        background-color: var(--ui-surface-2, #f1f1f1);
        border: 1px solid var(--ui-border, #ccc);
        border-radius: 4px;
        font-size: 1rem;
      }

      #checkin-transaction-status {
        color: #fff;
        background-color: #7987a1;
        border-color: #7987a1;
        
      }
      #overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black */
        z-index: 9999; /* Ensure it appears above other elements */
        display: none; /* Initially hidden */
    }

    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>
    <div id="overlay"></div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0 text-secondary">Check-out / Check-in</h6>
                    </div>

                    
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">
                            <a class="nav-link active" id="check-out-tab" data-bs-toggle="tab" href="#check-out" role="tab" aria-controls="home" aria-selected="true">Check-Out</a>
                            </li>
                            <li class="nav-item">
                            <a class="nav-link" id="check-in-tab" data-bs-toggle="tab" href="#check-in" role="tab" aria-controls="pub" aria-selected="false">Check-In</a>
                            </li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3" id="myTabContent">
                            <div><p class='text-danger text-center'>{{ session('error') ? session('error') : "" }}</p></div>
                            <!-- CHECKOUT -->
                            <div class="tab-pane fade show active" id="check-out" role="tabpanel" aria-labelledby="check-out-tab">
                                <form enctype="mform-data" class="search-form">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body">    
                                                <div class="row">    
                                                    <div class="col-md-6 offset-md-2 mb-2">
                                                        <div class="input-group">
                                                            <div class="input-group-text">
                                                                <i data-feather="search"></i>
                                                            </div>
                                                            <input type="text" class="form-control" id="input-search-checkout" placeholder="Search existing checkouts...">  
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 text-center">
                                                        <button type="button" class="btn btn-primary mb-1 mb-md-0" style="width:100%;" onclick="searchCheckout()">Search</button>    
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </form>
                                <div class="row mb-2"><h6 class="text-secondary text-center" id="checkout-title">{{ isset($reservation) ? 'CHECK-OUT RESERVATION' : 'ADD NEW CHECK-OUT';}}</h6></div>
                                <!-- Add new check-out Form -->
                                <form  method="post" action="{{ isset($reservation) ? url('/admin/check-out/update') : url('/admin/check-out/store'); }}" id="check-out-form" enctype="form-data" class="check-out-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 grid-margin stretch-card">
                                            <div class="card">
                                                <div class="card-body">

                                                    <h6 class="card-title text-center">Material</h6>

                                                    <div class="row mb-3">  
                                                        <label for="findCirculationNo" class="col-sm-2 col-form-label text-primary">Find</label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" style="width:90%; display: inline;" id="findCirculationNo" placeholder="Enter Circulation number...">
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <button type="button" class="btn btn-sm btn-primary" id="find-material"><i class="link-icon" data-feather="search"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="input-title" class="col-sm-3 col-form-label">Title</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="input-title" autocomplete="off" placeholder="Title" value="{{ isset($reservation) ? $reservation['title'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="input-mat-type" class="col-sm-3 col-form-label">Type</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="input-mat-type" autocomplete="off" placeholder="Material Type" value="{{ isset($reservation) ? $reservation['title'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="input-circulation-no" class="col-sm-3 col-form-label">Circulation Number</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="input-circulation-no" placeholder="Circulation No." value="{{ isset($reservation) ? $reservation['circulation_number'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="input-status" class="col-sm-3 col-form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Availability">Status <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="input-status" placeholder="Status" value="{{ isset($reservation) ? $reservation['status_name'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="input-cost" class="col-sm-3 col-form-label">Cost</label>
                                                        <div class="col-sm-9">
                                                            <input type="number" step="0.01" class="form-control" id="input-cost" placeholder="Cost" value="{{ isset($reservation) ? $reservation['cost'] : '' }}" disabled>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 grid-margin stretch-card">
                                            <div class="card">
                                                <div class="card-body">

                                                    <h6 class="card-title text-center">Patron</h6>

                                                    <div class="row mb-3">  
                                                        <label for="findLibraryCode" class="col-sm-2 col-form-label text-primary">Find</label>
                                                        <div class="col-sm-8">
                                                            <input type="text" class="form-control" style="width:90%; display: inline;" id="findLibraryCode" placeholder="Enter patron library code or email...">
                                                            
                                                        </div>
                                                        <div class="col-sm-2">
                                                            <button type="button" class="btn btn-sm btn-primary" id="find-patron"><i class="link-icon" data-feather="search"></i></button>
                                                        
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="inputName" class="col-sm-3 col-form-label">Name</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="inputName" placeholder="Fullname" value="{{ isset($reservation) ? $reservation['name'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="inputEmail" class="col-sm-3 col-form-label">Email</label>
                                                        <div class="col-sm-9">
                                                            <input type="email" class="form-control" id="inputEmail" autocomplete="off" placeholder="Email" value="{{ isset($reservation) ? $reservation['email'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <label for="inputContactNo" class="col-sm-3 col-form-label">Mobile</label>
                                                        <div class="col-sm-9">
                                                            <input type="text" class="form-control" id="inputContactNo" placeholder="Mobile number" value="{{ isset($reservation) ? $reservation['contactnum'] : '' }}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-4">
                                                            <div class="mb-3">
                                                                <label for="inputTransactions" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Total transactions">Transaction/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                <input type="number" class="form-control" id="inputTransactions" placeholder="Total transactions" value="{{ isset($reservation) ? $reservation['total_transaction'] : '' }}" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <div class="mb-3">
                                                                <label for="inputCheckouts" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Active checked-outs">Check-out/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                <input type="number" class="form-control" id="inputCheckouts" placeholder="Active checked-outs" value="{{ isset($reservation) ? $reservation['total_checkout'] : '' }}" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <div class="mb-3">
                                                                <label for="inputOverdue" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Active overdue materials">Overdue/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                <input type="number" class="form-control" id="inputOverdue" placeholder="Active overdue..." value="{{ isset($reservation) ? $reservation['total_overdue'] : '' }}" disabled>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                    
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 stretch-card">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title text-center">Terms</h6>
                                                    
                                                        <div class="row">
                                                            <div class="col-sm-4">
                                                                <div class="mb-3">
                                                                    <label for="input-due-date" class="form-label">Due Date</label>
                                                                    <input name="duedate" type="datetime-local" id="input-due-date" class="form-control">
                                                                </div>
                                                            </div><!-- Col -->
                                                            <div class="col-sm-4">
                                                                <div class="mb-3">
                                                                    <label for="noDays" class="form-label">Days Allowed</label>
                                                                    <input type="number" id="noDays" class="form-control" placeholder="No. days" disabled>
                                                                </div>
                                                            </div><!-- Col -->
                                                            <div class="col-sm-4">
                                                                <div class="mb-3">
                                                                    <label for="fineRatePerHr" class="form-label">Overdue Fine Rate / Hour</label>
                                                                    <input name="overdue_fine_rate" id="fineRatePerHr" type="number" class="form-control" value=0 placeholder="Fine per hour">
                                                                </div>
                                                            </div><!-- Col -->
                                                        </div><!-- Row -->
                                                        <input type="hidden" name="catalog_id" id="hidden-catalog-id" value="{{ isset($reservation) ? $reservation['catalog_id'] : '' }}">
                                                        <input type="hidden" name="circulation_number" id="hidden-circulation-number" value="{{ isset($reservation) ? $reservation['circulation_number'] : '' }}">
                                                        <input type="hidden" name="patron_library_code" id="hidden-patron-library-code" value="{{ isset($reservation) ? $reservation['library_code'] : '' }}">
                                                        <input type="hidden" name="user_id" id="hidden-user-id" value="{{ isset($reservation) ? $reservation['user_id'] : '' }}">
                                                        <input type="hidden" name="copy_id" id="hidden-copy-id" value="{{ isset($reservation) ? $reservation['copy_id'] : '' }}">
                                                        <input type="hidden" name="transaction_code" id="hidden-checkout-transaction-code" value="{{ isset($reservation) ? $reservation['transaction_code'] : '' }}">
                                                        <div class="row mt-3">
                                                            <div class="col-sm-4 offset-sm-4 text-center">
                                                                <button type="button" class="btn btn-primary me-2" onclick="checkoutTransaction()" id="check-out-button" {{ isset($reservation) ? '' : 'disabled';}}>
                                                                    Check-out
                                                                </button>
                                                                <a href="{{ url('/admin/check-out-in') }}" class="btn btn-secondary">Clear</a>
                                                            </div>
                                                        </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                                <!-- transactionCode modal -->
                                <div class="modal fade transactionCode-modal" tabindex="-1" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-l">
                                    <div class="modal-content">

                                        <div class="modal-header alert alert-success">
                                        <h5 class="modal-title text-center" id="transactionCodeModalLabel">{{ session('transactionCode') ? session('success') : "" }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close">
                                        </button>
                                        </div>
                                        <div class="modal-body" style="margin: auto;">
                                        <div><h5 class="text-center text-uppercase mb-3">Transaction Code</h5></div>
                                        <div class="mb-3">{!! DNS2D::getBarcodeHTML(session('transactionCode') ? session('transactionCode') : "0000000", 'QRCODE') !!}</div>
                                        <div class="text-center">
                                            <div class="alert alert-success" id="popup-message">Text copied!</div>
                                            <input type="text" class="form-control text-center mb-2" disabled value="{{ session('transactionCode') ? session('transactionCode') : '0000000' }}" id="transactionCodeInput">

                                            <!-- The button used to copy the text -->
                                            <button class="btn btn-primary text-center" onclick="copyToClipBoard()" id="copy-btn">Copy Code</button>
                                        </div>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                            <!-- CHECKIN -->
                            <div class="tab-pane fade" id="check-in" role="tabpanel" aria-labelledby="check-in-tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <form enctype="form-data" class="search-form">
                                            <div class="card mb-3">
                                                <div class="card-body">

                                                    <div class="row">
                                                        <div class="col-md-6 offset-md-2 mb-2">
                                                            <div class="input-group">
                                                                <div class="input-group-text">
                                                                    <i data-feather="search"></i>
                                                                </div>
                                                                <input type="text" name="search_transaction" class="form-control" id="input-search-transaction" placeholder="Search transaction...">  
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 text-center">
                                                            <button type="button" id="search-transaction" class="btn btn-primary mb-1 mb-md-0" style="width:100%;">Search</button>    
                                                        </div>  
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        <!-- CHECK-IN FORM START FORM HERE -->
                                        <div class="row mb-2"><h6 class="text-secondary text-center">TRANSACTION DETAILS</h6></div>
                                        <form  method="POST" action="{{ url('/admin/check-in/store') }}" id="check-in-form" enctype="form-data" class="check-in-form">
                                            @csrf
                                            <div class="row mb-3">
                                                <div class="col-md-12 stretch-card">
                                                    <div class="card">
                                                        <div class="card-body">
                                                                <h6 class="card-title text-center">Terms</h6>
                                                            
                                                                <div class="row">
                                                                    <div class="col-md-7 col-sm-12">
                                                                        <div class="container">
                                                                            <div class="row">
                                                                                <div class="col-md-6 col-sm-12">
                                                                                    <div class="mb-3">
                                                                                        <label for="checkin-duedate" class="form-label">Due Date</label>
                                                                                        <input type="datetime-local" id="checkin-duedate" class="form-control" disabled>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                                <div class="col-md-3 col-sm-12">
                                                                                    <div class="mb-3">
                                                                                        <label for="checkin-days-allowed" id="label-days-allowed" class="form-label">Days Allowed</label>
                                                                                        <input type="number" id="checkin-days-allowed" class="form-control" placeholder="No. days" disabled>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                                <div class="col-md-3 col-sm-12">
                                                                                    <div class="mb-3">
                                                                                        <label for="checkin-transaction-status" class="form-label text-danger font-weight-bold"><strong>Status</strong></label>
                                                                                        <input type="text" id="checkin-transaction-status" class="form-control" placeholder="Status" disabled>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                            </div>
        
                                                                            <div class="row">
                                                                                <div class="col-md-6 col-sm-12">
                                                                                    <div class="mb-3">
                                                                                        <label for="checkin-released-date" class="form-label">Released Date</label>
                                                                                        <input type="datetime-local" id="checkin-released-date" class="form-control" disabled>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                                <div class="col-md-6 col-sm-12">
                                                                                    <div class="mb-3">
                                                                                        <label for="checkin-released-by" class="form-label">Released By</label>
                                                                                        <input type="text" id="checkin-released-by" class="form-control" placeholder="Released by..." disabled>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6 col-sm-12 mb-3">
                                                                                    <div class="row">
                                                                                        <div class="col-md-12 col-sm-12 mb-3">
                                                                                            <label for="checkin-transaction-code" class="form-label">Transaction Code</label>
                                                                                            <input type="text" id="checkin-transaction-code" class="form-control" placeholder="Transaction code..." disabled>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row">
                                                                                        <div class="col-md-12 col-sm-12 mb-3">
                                                                                            <label for="checkin-arrangement" class="form-label">Reservation/Walk-in</label>
                                                                                            <input type="text" id="checkin-arrangement" class="form-control" placeholder="Transaction arrangement..." disabled>
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="row">          
                                                                                        <div class="col-md-6 col-sm-12 offset-md-3">
                                                                                            <!--Add other fine / waive Modal -->
                                                                                            <div class="modal fade" id="addFineModal" tabindex="-1" aria-labelledby="addFineModalTitle" aria-hidden="true">
                                                                                                <div class="modal-dialog modal-dialog-centered">
                                                                                                    <div class="modal-content">
                                                                                                        <div class="modal-header">
                                                                                                            <h5 class="modal-title" id="addFineModalTitle">Add Fine / Waive</h5>
                                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                                                                                                        </div>
                                                                                                        <div class="modal-body">
                                                                                                            <div class="row">
                                                                                                                <div class="col-sm-6 mb-3">
                                                                                                                    <label for="fine-description" class="form-label">Reason of fine</label>
                                                                                                                    <select class="form-select mb-3" id="fine-description">
                                                                                                                        <option>Select description...</option>
                                                                                                                        @foreach($fineDescriptions as $description)
                                                                                                                            <option value="{{ $description->id }}">{{ $description->name }}</option>
                                                                                                                        @endforeach
                                                                                                                        <option value="0"></option>
                                                                                                                    </select>
                                                                                                                </div>
                                                                                                                <div class="col-sm-6 mb-3">
                                                                                                                    <label for="input-other-fine-amount" class="form-label">Fine Amount</label>
                                                                                                                    <input type="number" id="input-other-fine-amount" class="form-control" value=0>
                                                                                                                </div>
                                                                                                      
                                                                                                            </div>
                                                                                                            <div class="row">
                                                                                                                <div class="col-sm-12 mb-3">
                                                                                                                    <label for="input-waive-amount" class="form-label">Waive Amount</label>
                                                                                                                    <input type="number" id="input-waive-amount" class="form-control" value=0>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="row">
                                                                                                                <div class="col-sm-12">
                                                                                                                    <label for="textarea-fine-note" class="form-label">Note</label>
                                                                                                                    <textarea class="form-control" id="textarea-fine-note" maxlength="100" rows="6" placeholder="Add note here..."></textarea>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        <div class="modal-footer">
                                                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                                            <button type="button" class="btn btn-primary" onclick="applyAddedTerm()" id="save-added-term" data-bs-toggle="modal">Apply</button>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div><!-- Col -->
                                                                                <div class="col-md-6 col-sm-12 mb-3">
                                                                                    <label for="input-fine-note" class="form-label">Note</label>
                                                                                    <textarea name="fine_note" class="form-control" id="input-fine-note" maxlength="100" rows="5" placeholder="This textarea has a limit of 100 chars." readonly></textarea>
                                                                                </div>
                                                                            </div>

                                                                        </div><!-- Container End -->
                                                                    </div>
                                                                    <div class="col-md-5 col-sm-12">
                                                                        <div class="table-responsive">
                                                                            <table class="table">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td>Total Hours Overdue</td>
                                                                                        <td class="text-end" style="font-size:0.75rem;" id="total-hrs-overdue">0</td>
                                                                                        <td></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>Overdue Fine Rate / Hour</td>
                                                                                        <td class="text-end" style="font-size:0.75rem;" id="overdue-fine-rate">0.00</td>
                                                                                        <td></td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td class="text-bold-800">Overdue Fine</td>
                                                                                        <td></td>
                                                                                        <td class="text-bold-800 text-end" id="overdue-fine">&#8369; 0.00</td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>Other Fine</td>
                                                                                        <td id="label-fine-desc"></td>
                                                                                        <td class="text-end" id="other-fine">&#8369; 0.00</td>
                                                                                    </tr>
                                                                                    <tr>
                                                                                        <td>Waive</td>
                                                                                        <td></td>
                                                                                        <td class="text-end text-danger" id="waive-amount">&#8369; -0.00</td>
                                                                                    </tr>
                                                                                    <tr class="bg-light">
                                                                                        <td class="text-bold-800">Total Fine</td>
                                                                                        <td></td>
                                                                                        <td class="text-success text-bold-800 text-end" id="total-fine">&#8369; 0.00</td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    
                                                                    </div>

                                                                </div><!-- Row -->

                                                                <input type="hidden" name="transaction_code" id="hidden-transaction-code">
                                                                <input type="hidden" name="circulation_code" id="hidden-circulation-code">
                                                                <input type="hidden" name="total_hrs_overdue" id="hidden-total-hrs-overdue">
                                                                <input type="hidden" name="overdue_fine" id="hidden-overdue-fine">
                                                                <input type="hidden" name="other_fine" id="hidden-other-fine">
                                                                <input type="hidden" name="circulation_fine_id" id="hidden-circulation-fine-id">
                                                                <input type="hidden" name="other_fine_desc" id="hidden-other-fine-desc">
                                                                <input type="hidden" name="waive_amount" id="hidden-waive-amount">
                                                                <input type="hidden" name="total_fine" id="hidden-total-fine">
                                                                <input type="hidden" name="library_code" id="hidden-library-code">
                                                                <div class="row mt-3">
                                                                    <div class="col-sm-4 offset-sm-4 text-center">
                                                                        <button type="button" id="check-in-btn" class="btn btn-primary me-2 mb-2" onclick="checkinTransaction()" disabled="disabled">Check-in</button>
                                                                        <button type="button" id="add-fine-btn" class="btn btn-inverse-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#addFineModal" disabled="disabled">
                                                                            Add fine / waive
                                                                        </button>
                                                                        <a href="{{ url('/admin/check-out-in') }}" class="btn btn-secondary">Clear</a>
                                                                    </div>
                                                                </div>
                                                        </div>
                                                    </div>
                                                </div>

                             
                                            </div>
                                            <div class="row">

                                                <div class="col-md-6 grid-margin stretch-card">
                                                    <div class="card">
                                                        <div class="card-body">

                                                            <h6 class="card-title text-center">Material</h6>
                                                            <div class="row mb-3">
                                                                <label for="checkin-input-title" class="col-sm-3 col-form-label">Title</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-input-title" autocomplete="off" placeholder="Title" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-input-mat-type" class="col-sm-3 col-form-label">Type</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-input-mat-type" autocomplete="off" placeholder="Material Type" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-input-circulation-no" class="col-sm-3 col-form-label">Circulation Number</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-input-circulation-no" placeholder="Circulation No." disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-input-status" class="col-sm-3 col-form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Availability">Material Status <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-input-status" placeholder="Status." disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-input-cost" class="col-sm-3 col-form-label">Cost</label>
                                                                <div class="col-sm-9">
                                                                    <input type="number" step="0.01" class="form-control" id="checkin-input-cost" placeholder="Cost" disabled>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 grid-margin stretch-card">
                                                    <div class="card">
                                                        <div class="card-body">

                                                            <h6 class="card-title text-center">Patron</h6>
                                                            <div class="row mb-3">
                                                                <label for="checkin-inputName" class="col-sm-3 col-form-label">Name</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-inputName" placeholder="Fullname" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-inputEmail" class="col-sm-3 col-form-label">Email</label>
                                                                <div class="col-sm-9">
                                                                    <input type="email" class="form-control" id="checkin-inputEmail" autocomplete="off" placeholder="Email" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <label for="checkin-inputContactNo" class="col-sm-3 col-form-label">Mobile</label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" id="checkin-inputContactNo" placeholder="Mobile number" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-sm-4">
                                                                    <div class="mb-3">
                                                                        <label for="checkin-input-total-transaction" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Total transactions">Transaction/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                        <input type="number" class="form-control" id="checkin-input-total-transaction" placeholder="Total transactions" disabled>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-4">
                                                                    <div class="mb-3">
                                                                        <label for="checkin-input-active-checkout" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Active checked-outs">Check-out/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                        <input type="number" class="form-control" id="checkin-input-active-checkout" placeholder="Active checked-outs" disabled>
                                                                    </div>
                                                                </div>
                                                                <div class="col-sm-4">
                                                                    <div class="mb-3">
                                                                        <label for="checkin-input-active-overdue" class="form-label" data-bs-toggle="tooltip" data-bs-placement="top" title="Active overdue materials">Overdue/s <span class="badge rounded-pill bg-info">&#8505;</span></label>
                                                                        <input type="number" class="form-control" id="checkin-input-active-overdue" placeholder="Active overdue..." disabled>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        </form>
                                        <!-- CHECK-IN FORM END HERE! -->
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- transactionCode modal -->
@endsection

@section('js')
<script src="{{ asset('js/admin/check-out-in.js') }}"></script>
<script nonce="{{ cspNonce() }}"> 
    let findMaterialUrl = '{{ url("/admin/check-out-in/find-material") }}';
    let csrf ='{{ csrf_token() }}';
    let findPatronUrl = '{{ url("/admin/check-out-in/find-patron") }}';
    let searchTransactionUrl = '{{ url("/admin/check-in/search") }}';
    let searchCheckoutUrl = '{{ url("/admin/check-out/search") }}';
    let updateCheckout = "{{ url('/admin/check-out/update') }}";
    let storeCheckout = "{{ url('/admin/check-out/store') }}";
</script> 
<script nonce="{{ cspNonce() }}">
    function refreshPage()
    {
        location.reload();
    }

    $(document).ready(function() {
        @if(session('transactionCode')) {
          $('.transactionCode-modal').modal('show');
        }
        @endif
        @if(session('complete'))
            Swal.fire({
                position: 'middle',
                icon: 'success',
                title: 'Transaction Complete',
                showConfirmButton: false,
                timer: 1500
            })
        @endif

        @if(isset($reservation))
        var offset = 78;
        $('html, body').animate({
            scrollTop: $('#checkout-title').offset().top - offset
        }, 100); // Adjust the duration (in milliseconds) as needed
        @endif
    });
</script>

<script src="{{ asset('js/custom.js') }}"></script>
@endsection