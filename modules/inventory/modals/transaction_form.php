<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalLabel">Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" name="item_id" id="transactionItemId">
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="transaction_type" id="transactionType" required>
                            <option value="purchase">Purchase (Stock In)</option>
                            <option value="sale">Sale (Stock Out)</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="return">Return</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" id="transactionQuantity"
                               min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Unit Price</label>
                        <input type="number" class="form-control" name="unit_price" id="transactionUnitPrice"
                               step="0.01">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number" id="transactionReference">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="transactionNotes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTransaction()">Save Transaction</button>
            </div>
        </div>
    </div>
</div> 