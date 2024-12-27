<?php
require_once __DIR__ . '/../../../config/database.php';

class Inventory {
    private $conn;
    private $table = 'inventory_items';
    private $transactions_table = 'inventory_transactions';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getAll($user_id, $filters = []) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        
        // Add search filter
        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR sku LIKE :search OR category LIKE :search)";
        }

        // Add category filter
        if (!empty($filters['category'])) {
            $query .= " AND category = :category";
        }

        // Add status filter
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
        }

        $query .= " ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        if (!empty($filters['search'])) {
            $searchTerm = "%" . $filters['search'] . "%";
            $stmt->bindParam(':search', $searchTerm);
        }

        if (!empty($filters['category'])) {
            $stmt->bindParam(':category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $user_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                (user_id, name, sku, category, description, quantity, unit, 
                 min_quantity, max_quantity, unit_cost, selling_price, supplier, 
                 location, notes, status)
                VALUES
                (:user_id, :name, :sku, :category, :description, :quantity, :unit,
                 :min_quantity, :max_quantity, :unit_cost, :selling_price, :supplier,
                 :location, :notes, :status)";

        // Set status based on quantity and min_quantity
        $status = 'active';
        if ($data['quantity'] <= 0) {
            $status = 'out_of_stock';
        } elseif ($data['quantity'] <= ($data['min_quantity'] ?? 0)) {
            $status = 'low_stock';
        }

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sku', $data['sku']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':max_quantity', $data['max_quantity']);
        $stmt->bindParam(':unit_cost', $data['unit_cost']);
        $stmt->bindParam(':selling_price', $data['selling_price']);
        $stmt->bindParam(':supplier', $data['supplier']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    public function update($data) {
        $query = "UPDATE " . $this->table . " SET
                name = :name,
                sku = :sku,
                category = :category,
                description = :description,
                quantity = :quantity,
                unit = :unit,
                min_quantity = :min_quantity,
                max_quantity = :max_quantity,
                unit_cost = :unit_cost,
                selling_price = :selling_price,
                supplier = :supplier,
                location = :location,
                notes = :notes,
                status = :status
                WHERE id = :id AND user_id = :user_id";

        // Set status based on quantity and min_quantity
        $status = 'active';
        if ($data['quantity'] <= 0) {
            $status = 'out_of_stock';
        } elseif ($data['quantity'] <= ($data['min_quantity'] ?? 0)) {
            $status = 'low_stock';
        }

        $stmt = $this->conn->prepare($query);
        
        // Bind all parameters
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sku', $data['sku']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_quantity', $data['min_quantity']);
        $stmt->bindParam(':max_quantity', $data['max_quantity']);
        $stmt->bindParam(':unit_cost', $data['unit_cost']);
        $stmt->bindParam(':selling_price', $data['selling_price']);
        $stmt->bindParam(':supplier', $data['supplier']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        return $stmt->execute();
    }

    public function addTransaction($data) {
        try {
            $this->conn->beginTransaction();

            // Insert transaction record
            $query = "INSERT INTO " . $this->transactions_table . "
                    (user_id, item_id, transaction_type, quantity, unit_price, 
                     total_amount, reference_number, notes)
                    VALUES
                    (:user_id, :item_id, :transaction_type, :quantity, :unit_price,
                     :total_amount, :reference_number, :notes)";

            $stmt = $this->conn->prepare($query);
            
            // Calculate total amount
            $total_amount = $data['quantity'] * ($data['unit_price'] ?? 0);

            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':item_id', $data['item_id']);
            $stmt->bindParam(':transaction_type', $data['transaction_type']);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':unit_price', $data['unit_price']);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':reference_number', $data['reference_number']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();

            // Update item quantity
            $quantity_change = $data['quantity'];
            if (in_array($data['transaction_type'], ['sale', 'adjustment'])) {
                $quantity_change = -$quantity_change;
            }

            $query = "UPDATE " . $this->table . "
                    SET quantity = quantity + :quantity_change
                    WHERE id = :item_id AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity_change', $quantity_change);
            $stmt->bindParam(':item_id', $data['item_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getLowStockItems($user_id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND quantity <= min_quantity 
                  AND status != 'discontinued'";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 