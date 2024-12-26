<?php
require_once __DIR__ . '/../../../config/database.php';

class Prescription {
    private $conn;
    private $table = 'prescriptions';
    
    // Properties
    public $id;
    public $user_id;
    public $patient_id;
    public $appointment_id;
    public $diagnosis;
    public $medications;
    public $instructions;
    public $notes;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // Get all prescriptions for a user
    public function getAll($userId, $filters = []) {
        $query = "SELECT p.*, 
                         CONCAT(pat.first_name, ' ', pat.last_name) as patient_name,
                         pat.email as patient_email,
                         a.date as appointment_date,
                         a.time as appointment_time
                  FROM " . $this->table . " p
                  LEFT JOIN patients pat ON p.patient_id = pat.id
                  LEFT JOIN appointments a ON p.appointment_id = a.id
                  WHERE p.user_id = :user_id";
        
        // Add date filter if provided
        if (!empty($filters['date'])) {
            $query .= " AND DATE(p.created_at) = :date";
        } elseif (!empty($filters['date_range'])) {
            $query .= " AND DATE(p.created_at) BETWEEN :start_date AND :end_date";
        }
        
        // Add patient filter if provided
        if (!empty($filters['patient_id'])) {
            $query .= " AND p.patient_id = :patient_id";
        }
        
        $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if (!empty($filters['date'])) {
            $stmt->bindParam(':date', $filters['date']);
        } elseif (!empty($filters['date_range'])) {
            $stmt->bindParam(':start_date', $filters['date_range']['start']);
            $stmt->bindParam(':end_date', $filters['date_range']['end']);
        }
        
        if (!empty($filters['patient_id'])) {
            $stmt->bindParam(':patient_id', $filters['patient_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get single prescription
    public function getById($id, $userId) {
        $query = "SELECT p.*, 
                         CONCAT(pat.first_name, ' ', pat.last_name) as patient_name,
                         pat.email as patient_email,
                         a.date as appointment_date,
                         a.time as appointment_time
                  FROM " . $this->table . " p
                  LEFT JOIN patients pat ON p.patient_id = pat.id
                  LEFT JOIN appointments a ON p.appointment_id = a.id
                  WHERE p.id = :id AND p.user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create prescription
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (user_id, patient_id, appointment_id, diagnosis, 
                 medications, instructions, notes)
                VALUES
                (:user_id, :patient_id, :appointment_id, :diagnosis,
                 :medications, :instructions, :notes)";
                
        $stmt = $this->conn->prepare($query);
        
        // Bind data
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':patient_id', $this->patient_id);
        $stmt->bindParam(':appointment_id', $this->appointment_id);
        $stmt->bindParam(':diagnosis', $this->diagnosis);
        $stmt->bindParam(':medications', $this->medications);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':notes', $this->notes);
        
        return $stmt->execute();
    }
    
    // Update prescription
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET diagnosis = :diagnosis,
                    medications = :medications,
                    instructions = :instructions,
                    notes = :notes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        // Bind data
        $stmt->bindParam(':diagnosis', $this->diagnosis);
        $stmt->bindParam(':medications', $this->medications);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Delete prescription
    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    public function getAllPaginated($user_id, $filters = [], $offset = 0, $per_page = 10) {
        $query = "SELECT p.*, 
                         CONCAT(pat.first_name, ' ', pat.last_name) as patient_name,
                         pat.email as patient_email
                  FROM " . $this->table . " p
                  LEFT JOIN patients pat ON p.patient_id = pat.id
                  WHERE p.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (CONCAT(pat.first_name, ' ', pat.last_name) LIKE :search1 
                       OR p.diagnosis LIKE :search2)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
        }

        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = (int)$per_page;
        $params[':offset'] = (int)$offset;

        $stmt = $this->conn->prepare($query);
        
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
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " p
                  LEFT JOIN patients pat ON p.patient_id = pat.id
                  WHERE p.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (CONCAT(pat.first_name, ' ', pat.last_name) LIKE :search1 
                       OR p.diagnosis LIKE :search2)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
} 