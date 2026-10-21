<div class="row">          
    <div class="col-md-6 col-sm-12 offset-md-3">
        <div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-labelledby="viewTransactionModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewTransactionModalTitle">Transaction View</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-12 mb-3">
                                <label for="transaction-code" class="form-label">Transaction Code</label>
                                <input type="text" id="transaction-code" class="form-control" placeholder="Transaction Code" disabled>
                            </div>
                        </div>
                        <div class="row">
                        <div class="col-sm-12 mb-3">
                            <label for="transaction-status" class="form-label">Status</label>
                            <input type="text" id="transaction-status" class="form-control" placeholder="Status" disabled>
                        </div>
                        </div>
                        <div class="row modal-trans-result">
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-due-date" class="form-label">Due Date</label>
                                <input type="text" id="transaction-due-date" class="form-control" placeholder="Due Date" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-overdue-fine-rate" class="form-label">Overdue Fine Rate</label>
                                <input type="text" id="transaction-overdue-fine-rate" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row modal-checkin-result">
                            <div class="col-sm-4 mb-3">
                                <label for="transaction-waived-amount" class="form-label">Waived Amount</label>
                                <input type="text" id="transaction-waived-amount" class="form-control" disabled>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label for="transaction-total-fine" class="form-label">Total Fine</label>
                                <input type="text" id="transaction-total-fine" class="form-control" disabled>
                            </div>
                            <div class="col-sm-4 mb-3">
                                <label for="transaction-total-fine-paid" class="form-label">Total Fine Paid</label>
                                <input type="text" id="transaction-total-fine-paid" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row modal-approved-result">
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-approved-at" class="form-label">Approved Date</label>
                                <input type="text" id="transaction-approved-at" class="form-control" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-approved-by" class="form-label">Approved By</label>
                                <input type="text" id="transaction-approved-by" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row modal-declined-result">
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-declined-at" class="form-label">Declined Date</label>
                                <input type="text" id="transaction-declined-at" class="form-control" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-declined-by" class="form-label">Declined By</label>
                                <input type="text" id="transaction-declined-by" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row modal-trans-result">
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-released-at" class="form-label">Released Date</label>
                                <input type="text" id="transaction-released-at" class="form-control" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-released-by" class="form-label">Released By</label>
                                <input type="text" id="transaction-released-by" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row modal-checkin-result">
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-received-at" class="form-label">Returned Date</label>
                                <input type="text" id="transaction-received-at" class="form-control" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="transaction-received-by" class="form-label">Received By</label>
                                <input type="text" id="transaction-received-by" class="form-control" disabled>
                            </div>
                        </div>
                        

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>