<?php
session_start();
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once './models/Inventory.php';

// Check authentication and subscription
SubscriptionMiddleware::checkAccess();

// Initialize Inventory model
$inventory = new Inventory();

// Get filters from query parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'status' => $_GET['status'] ?? ''
];

// Get inventory items
$items = $inventory->getAll($_SESSION['user_id'], $filters);

// Get unique categories for filter dropdown
$categories = array_unique(array_column($items, 'category'));

// Get low stock items
$lowStockItems = $inventory->getLowStockItems($_SESSION['user_id']);
if (!empty($lowStockItems)): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <h5 class="alert-heading mb-2">
            <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
        </h5>
        <p class="mb-0">The following items are running low on stock:</p>
        <ul class="mb-0 mt-2">
            <?php foreach ($lowStockItems as $item): ?>
                <li>
                    <strong><?php echo htmlspecialchars($item['name']); ?></strong> - 
                    Current stock: <?php echo $item['quantity']; ?> 
                    (Minimum: <?php echo $item['min_quantity']; ?>)
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include_once '../../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">Inventory Management</h5>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#itemModal">
                                    <i class="fas fa-plus me-1"></i> Add Item
                                </button>
                                <button type="button" class="btn btn-info" onclick="sendInventoryReport()">
                                    <i class="fas fa-file-alt me-1"></i> Send Report
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Search items..." value="<?php echo $filters['search']; ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category; ?>" 
                                            <?php echo $filters['category'] === $category ? 'selected' : ''; ?>>
                                            <?php echo $category; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="low_stock" <?php echo $filters['status'] === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                    <option value="out_of_stock" <?php echo $filters['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </button>
                            </div>
                        </div>

                        <!-- Inventory Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>SKU</th>
                                        <th>Category</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td class="text-center">
                                                <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($item['selling_price'], 2); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = match($item['status']) {
                                                    'active' => 'success',
                                                    'low_stock' => 'warning',
                                                    'out_of_stock' => 'danger',
                                                    'discontinued' => 'secondary',
                                                    default => 'primary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $item['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editItem(<?php echo $item['id']; ?>)" 
                                                            title="Edit Item">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            onclick="addTransaction(<?php echo $item['id']; ?>)" 
                                                            title="Add Transaction">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteItem(<?php echo $item['id']; ?>)" 
                                                            title="Delete Item">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <?php include 'modals/item_form.php'; ?>

    <!-- Transaction Modal -->
    <?php include 'modals/transaction_form.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'active': return 'success';
            case 'low_stock': return 'warning';
            case 'out_of_stock': return 'danger';
            case 'discontinued': return 'secondary';
            default: return 'primary';
        }
    }

    function resetFilters() {
        window.location.href = 'index.php';
    }

    // Initialize modals
    const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
    const transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));

    // Filter handling
    document.getElementById('searchInput').addEventListener('input', debounce(applyFilters, 500));
    document.getElementById('categoryFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);

    function applyFilters() {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        let url = 'index.php?';
        if (search) url += `search=${encodeURIComponent(search)}&`;
        if (category) url += `category=${encodeURIComponent(category)}&`;
        if (status) url += `status=${encodeURIComponent(status)}&`;
        
        window.location.href = url.slice(0, -1);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // CRUD Operations
    function editItem(id) {
        fetch(`api/get_item.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const item = data.item;
                    document.getElementById('itemId').value = item.id;
                    document.getElementById('itemName').value = item.name;
                    document.getElementById('itemSku').value = item.sku;
                    document.getElementById('itemCategory').value = item.category;
                    document.getElementById('itemUnit').value = item.unit;
                    document.getElementById('itemUnitCost').value = item.unit_cost;
                    document.getElementById('itemSellingPrice').value = item.selling_price;
                    document.getElementById('itemMinQuantity').value = item.min_quantity;
                    document.getElementById('itemMaxQuantity').value = item.max_quantity;
                    document.getElementById('itemQuantity').value = item.quantity;
                    document.getElementById('itemDescription').value = item.description;
                    document.getElementById('itemSupplier').value = item.supplier;
                    document.getElementById('itemLocation').value = item.location;
                    document.getElementById('itemNotes').value = item.notes;
                    
                    document.getElementById('itemModalLabel').textContent = 'Edit Item';
                    itemModal.show();
                } else {
                    showAlert('Error', data.message, 'error');
                }
            })
            .catch(error => showAlert('Error', 'Failed to load item details', 'error'));
    }

    function saveItem() {
        const form = document.getElementById('itemForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const url = formData.get('id') ? 'api/update_item.php' : 'api/create_item.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Success', data.message, 'success');
                itemModal.hide();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('Error', data.message, 'error');
            }
        })
        .catch(error => showAlert('Error', 'Failed to save item', 'error'));
    }

    function addTransaction(itemId) {
        document.getElementById('transactionItemId').value = itemId;
        document.getElementById('transactionQuantity').value = '';
        document.getElementById('transactionUnitPrice').value = '';
        document.getElementById('transactionReference').value = '';
        document.getElementById('transactionNotes').value = '';
        transactionModal.show();
    }

    function saveTransaction() {
        const form = document.getElementById('transactionForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        fetch('api/add_transaction.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Success', data.message, 'success');
                transactionModal.hide();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert('Error', data.message, 'error');
            }
        })
        .catch(error => showAlert('Error', 'Failed to save transaction', 'error'));
    }

    function deleteItem(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`api/delete_item.php`, {
                    method: 'POST',
                    body: JSON.stringify({ id: id }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Success', data.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('Error', data.message, 'error');
                    }
                })
                .catch(error => showAlert('Error', 'Failed to delete item', 'error'));
            }
        });
    }

    function showAlert(title, message, icon) {
        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    function sendInventoryReport() {
        if (confirm('Send inventory report to your email?')) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Sending...';

            fetch('api/send_report.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Success', 'Inventory report sent successfully!', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to send report');
                    }
                })
                .catch(error => {
                    showAlert('Error', error.message, 'error');
                })
                .finally(() => {
                    // Reset button state
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
        }
    }
    </script>
</body>
</html> 