<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" onsubmit="event.preventDefault(); saveItem();">
                    <input type="hidden" id="itemId" name="id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="itemName" name="name" required
                                   placeholder="Enter item name (e.g., Dental Floss)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" class="form-control" id="itemSku" name="sku" required
                                   placeholder="Enter unique SKU (e.g., DFL-001)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" id="itemCategory" name="category" required
                                   placeholder="Enter category (e.g., Dental Supplies)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" class="form-control" id="itemUnit" name="unit" required
                                   placeholder="Enter unit (e.g., pieces, boxes, packs)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit Cost</label>
                            <input type="number" class="form-control" id="itemUnitCost" name="unit_cost" 
                                   step="0.01" required placeholder="Enter purchase price per unit">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price</label>
                            <input type="number" class="form-control" id="itemSellingPrice" name="selling_price" 
                                   step="0.01" required placeholder="Enter selling price per unit">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Current Quantity</label>
                            <input type="number" class="form-control" id="itemQuantity" name="quantity" 
                                   required placeholder="Enter current stock quantity">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Minimum Quantity</label>
                            <input type="number" class="form-control" id="itemMinQuantity" name="min_quantity" 
                                   required placeholder="Enter minimum stock level for alerts">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maximum Quantity</label>
                            <input type="number" class="form-control" id="itemMaxQuantity" name="max_quantity" 
                                   required placeholder="Enter maximum stock capacity">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="itemDescription" name="description" rows="2"
                                    placeholder="Enter detailed item description"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <input type="text" class="form-control" id="itemSupplier" name="supplier"
                                   placeholder="Enter supplier name or company">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" id="itemLocation" name="location"
                                   placeholder="Enter storage location (e.g., Shelf A-1)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="itemNotes" name="notes" rows="2"
                                    placeholder="Enter any additional notes or special instructions"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveItem()">Save Item</button>
            </div>
        </div>
    </div>
</div> 