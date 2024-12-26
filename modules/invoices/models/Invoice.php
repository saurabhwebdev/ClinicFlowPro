<?php
require_once __DIR__ . '/../../../config/database.php';

class Invoice {
    private $conn;
    private $table = 'invoices';

    public $id;
    public $user_id;
    public $patient_id;
    public $appointment_id;
    public $invoice_number;
    public $status;
    public $subtotal;
    public $tax_rate;
    public $tax_amount;
    public $discount_type;
    public $discount_value;
    public $discount_amount;
    public $total_amount;
    public $payment_method;
    public $payment_date;
    public $due_date;
    public $notes;
    public $items;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function generateInvoiceNumber() {
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $query = "SELECT invoice_number FROM " . $this->table . " 
                 WHERE invoice_number LIKE ? 
                 ORDER BY id DESC LIMIT 1";
        
        $prefix = "INV-{$year}{$month}-";
        $searchPattern = $prefix . '%';
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$searchPattern]);
        
        if ($stmt->rowCount() > 0) {
            $lastNumber = $stmt->fetch(PDO::FETCH_ASSOC)['invoice_number'];
            $lastSequence = intval(substr($lastNumber, -4));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    public function create() {
        // Generate invoice number first
        $this->invoice_number = $this->generateInvoiceNumber();

        $query = "INSERT INTO " . $this->table . "
                (user_id, patient_id, appointment_id, invoice_number, status, 
                 subtotal, tax_rate, tax_amount, discount_type, discount_value,
                 discount_amount, total_amount, payment_method, payment_date,
                 due_date, notes, items)
                VALUES
                (:user_id, :patient_id, :appointment_id, :invoice_number, :status,
                 :subtotal, :tax_rate, :tax_amount, :discount_type, :discount_value,
                 :discount_amount, :total_amount, :payment_method, :payment_date,
                 :due_date, :notes, :items)";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':patient_id', $this->patient_id);
        $stmt->bindParam(':appointment_id', $this->appointment_id);
        $stmt->bindParam(':invoice_number', $this->invoice_number);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':tax_rate', $this->tax_rate);
        $stmt->bindParam(':tax_amount', $this->tax_amount);
        $stmt->bindParam(':discount_type', $this->discount_type);
        $stmt->bindParam(':discount_value', $this->discount_value);
        $stmt->bindParam(':discount_amount', $this->discount_amount);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':items', $this->items);

        return $stmt->execute();
    }

    public function getAll($userId, $filters = []) {
        $query = "SELECT i.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.email as patient_email,
                         a.date as appointment_date,
                         a.time as appointment_time
                  FROM " . $this->table . " i
                  LEFT JOIN patients p ON i.patient_id = p.id
                  LEFT JOIN appointments a ON i.appointment_id = a.id
                  WHERE i.user_id = :user_id";
        
        // Add date filter
        if (!empty($filters['date'])) {
            $query .= " AND DATE(i.created_at) = :date";
        } elseif (!empty($filters['date_range'])) {
            $query .= " AND DATE(i.created_at) BETWEEN :start_date AND :end_date";
        }
        
        // Add status filter
        if (!empty($filters['status'])) {
            $query .= " AND i.status = :status";
        }
        
        // Add patient filter
        if (!empty($filters['patient_id'])) {
            $query .= " AND i.patient_id = :patient_id";
        }
        
        $query .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if (!empty($filters['date'])) {
            $stmt->bindParam(':date', $filters['date']);
        } elseif (!empty($filters['date_range'])) {
            $stmt->bindParam(':start_date', $filters['date_range']['start']);
            $stmt->bindParam(':end_date', $filters['date_range']['end']);
        }
        
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        if (!empty($filters['patient_id'])) {
            $stmt->bindParam(':patient_id', $filters['patient_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId) {
        $query = "SELECT i.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.email as patient_email,
                         a.date as appointment_date,
                         a.time as appointment_time
                  FROM " . $this->table . " i
                  LEFT JOIN patients p ON i.patient_id = p.id
                  LEFT JOIN appointments a ON i.appointment_id = a.id
                  WHERE i.id = :id AND i.user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $invoiceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invoiceData) {
            // Recalculate totals
            $this->subtotal = $invoiceData['subtotal'];
            $this->tax_rate = $invoiceData['tax_rate'];
            $this->discount_type = $invoiceData['discount_type'];
            $this->discount_value = $invoiceData['discount_value'];
            
            // Calculate tax amount
            if ($this->tax_rate > 0) {
                $invoiceData['tax_amount'] = $this->subtotal * ($this->tax_rate / 100);
            }
            
            // Calculate discount amount
            if ($this->discount_value > 0) {
                if ($this->discount_type === 'percentage') {
                    $invoiceData['discount_amount'] = $this->subtotal * ($this->discount_value / 100);
                } else {
                    $invoiceData['discount_amount'] = $this->discount_value;
                }
            }
            
            // Update total amount
            $invoiceData['total_amount'] = $this->subtotal + $invoiceData['tax_amount'] - $invoiceData['discount_amount'];
        }
        
        return $invoiceData;
    }

    public function update() {
        $query = "UPDATE " . $this->table . "
                 SET status = :status,
                     subtotal = :subtotal,
                     tax_rate = :tax_rate,
                     tax_amount = :tax_amount,
                     discount_type = :discount_type,
                     discount_value = :discount_value,
                     discount_amount = :discount_amount,
                     total_amount = :total_amount,
                     payment_method = :payment_method,
                     payment_date = :payment_date,
                     due_date = :due_date,
                     notes = :notes,
                     items = :items
                 WHERE id = :id AND user_id = :user_id";
                 
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':subtotal', $this->subtotal);
        $stmt->bindParam(':tax_rate', $this->tax_rate);
        $stmt->bindParam(':tax_amount', $this->tax_amount);
        $stmt->bindParam(':discount_type', $this->discount_type);
        $stmt->bindParam(':discount_value', $this->discount_value);
        $stmt->bindParam(':discount_amount', $this->discount_amount);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_date', $this->payment_date);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':items', $this->items);
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }

    public function calculateTotals() {
        $items = json_decode($this->items, true);
        $this->subtotal = 0;
        $this->tax_amount = 0;
        $this->discount_amount = 0;
        
        // Calculate subtotal
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $this->subtotal += $itemTotal;
        }
        
        // Calculate tax on subtotal
        $this->tax_rate = floatval($this->tax_rate ?? 0);
        if ($this->tax_rate > 0) {
            $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        }
        
        // Calculate discount
        $this->discount_value = floatval($this->discount_value ?? 0);
        if ($this->discount_value > 0) {
            if ($this->discount_type === 'percentage') {
                $this->discount_amount = $this->subtotal * ($this->discount_value / 100);
            } else {
                $this->discount_amount = $this->discount_value;
            }
        }
        
        // Calculate total
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount;
        
        return [
            'subtotal' => $this->subtotal,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount
        ];
    }

    public function updateStatus($status) {
        $query = "UPDATE " . $this->table . "
                 SET status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }

    public function getAllPaginated($user_id, $filters = [], $offset = 0, $per_page = 10) {
        $sql = "SELECT i.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                FROM invoices i 
                LEFT JOIN patients p ON i.patient_id = p.id 
                WHERE i.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (i.invoice_number LIKE :search1 
                     OR CONCAT(p.first_name, ' ', p.last_name) LIKE :search2
                     OR i.total_amount LIKE :search3)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
        }

        $sql .= " ORDER BY i.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$per_page;
        $params[':offset'] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        
        // Bind each parameter with its specific type
        foreach ($params as $key => &$value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCount($user_id, $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM invoices i 
                LEFT JOIN patients p ON i.patient_id = p.id 
                WHERE i.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (i.invoice_number LIKE :search1 
                     OR CONCAT(p.first_name, ' ', p.last_name) LIKE :search2
                     OR i.total_amount LIKE :search3)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
        }

        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Add other necessary methods (update, delete, getById, getAll, etc.)
    // Similar to the Prescription model
} 