<?php
require_once __DIR__ . '/../../../config/database.php';

class Appointment {
    private $conn;
    private $table = 'appointments';
    
    // Properties
    public $id;
    public $user_id;
    public $patient_id;
    public $title;
    public $date;
    public $time;
    public $duration;
    public $status;
    public $notes;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // Get all appointments for a user
    public function getAll($userId, $filters = []) {
        $query = "SELECT a.*, 
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                 FROM " . $this->table . " a
                 JOIN patients p ON a.patient_id = p.id 
                 WHERE a.user_id = :user_id";
        
        // Add date filter if provided
        if (!empty($filters['date'])) {
            $query .= " AND a.date = :date";
        }
        
        // Add status filter if provided
        if (!empty($filters['status'])) {
            $query .= " AND a.status = :status";
        }
        
        $query .= " ORDER BY a.date ASC, a.time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if (!empty($filters['date'])) {
            $stmt->bindParam(':date', $filters['date']);
        }
        if (!empty($filters['status'])) {
            $stmt->bindParam(':status', $filters['status']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new appointment
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (user_id, patient_id, title, date, time, duration, status, notes)
                VALUES
                (:user_id, :patient_id, :title, :date, :time, :duration, :status, :notes)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind data
        $this->bindParams($stmt);
        
        return $stmt->execute();
    }
    
    // Update appointment
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET title = :title,
                    date = :date,
                    time = :time,
                    duration = :duration,
                    status = :status,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind data
        $this->bindParams($stmt);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Delete appointment
    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    private function bindParams($stmt) {
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':patient_id', $this->patient_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':date', $this->date);
        $stmt->bindParam(':time', $this->time);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':notes', $this->notes);
    }
    
    // Add this new method
    public function validateWorkingHours($userId) {
        $query = "SELECT working_hours_start, working_hours_end, working_days, holidays 
                 FROM clinic_settings 
                 WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            return ['valid' => true]; // Allow if no settings are found
        }
        
        // Check if the day is a working day
        $appointmentDay = date('N', strtotime($this->date)); // 1 (Monday) to 7 (Sunday)
        $workingDays = json_decode($settings['working_days'], true);
        
        // If working days are not set, default to Monday-Friday (1-5)
        if (empty($workingDays)) {
            $workingDays = [1, 2, 3, 4, 5];
        }
        
        if (!in_array($appointmentDay, $workingDays)) {
            return ['valid' => false, 'message' => 'Selected day is not a working day'];
        }
        
        // Check if the day is not a holiday
        $holidays = json_decode($settings['holidays'], true) ?? [];
        if (isset($holidays[$this->date])) {
            return ['valid' => false, 'message' => 'Selected day is a holiday'];
        }
        
        // Check if time is within working hours
        $appointmentTime = strtotime($this->time);
        $workStart = strtotime($settings['working_hours_start'] ?? '09:00');
        $workEnd = strtotime($settings['working_hours_end'] ?? '17:00');
        
        if ($appointmentTime < $workStart || $appointmentTime > $workEnd) {
            return ['valid' => false, 'message' => 'Selected time is outside working hours'];
        }
        
        return ['valid' => true];
    }
    
    public function updateStatus() {
        $query = "UPDATE " . $this->table . "
                  SET status = :status
                  WHERE id = :id AND user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }
    
    public function getById($id, $userId) {
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                  FROM " . $this->table . " a
                  JOIN patients p ON a.patient_id = p.id 
                  WHERE a.id = :id AND a.user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAllPaginated($user_id, $filters = [], $offset = 0, $per_page = 10) {
        $query = "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                 FROM " . $this->table . " a
                 JOIN patients p ON a.patient_id = p.id 
                 WHERE a.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (CONCAT(p.first_name, ' ', p.last_name) LIKE :search1 
                       OR a.title LIKE :search2)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
        }

        $query .= " ORDER BY a.date ASC, a.time ASC LIMIT :limit OFFSET :offset";
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
                 FROM " . $this->table . " a
                 JOIN patients p ON a.patient_id = p.id 
                 WHERE a.user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (CONCAT(p.first_name, ' ', p.last_name) LIKE :search1 
                       OR a.title LIKE :search2)";
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