<?php
require_once __DIR__ . '/../../../config/database.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php';

class Patient {
    private $conn;
    private $table = 'patients';
    
    // Properties
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $date_of_birth;
    public $gender;
    public $address;
    public $medical_history;
    public $notes;
    public $user_id;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // Get all patients for a user
    public function getAll($userId, $filters = '') {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE user_id = :user_id";
        
        // Handle date range filter
        if (is_array($filters) && isset($filters['date_range'])) {
            $query .= " AND DATE(created_at) BETWEEN :start_date AND :end_date";
        } 
        // Handle text search filter
        elseif (!empty($filters) && is_string($filters)) {
            $query .= " AND (first_name LIKE :search 
                      OR last_name LIKE :search 
                      OR email LIKE :search 
                      OR phone LIKE :search)";
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        // Bind parameters based on filter type
        if (is_array($filters) && isset($filters['date_range'])) {
            $stmt->bindParam(':start_date', $filters['date_range']['start']);
            $stmt->bindParam(':end_date', $filters['date_range']['end']);
        } 
        elseif (!empty($filters) && is_string($filters)) {
            $searchTerm = "%{$filters}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get single patient
    public function getOne($id) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id AND user_id = :user_id";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create patient
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (first_name, last_name, email, phone, date_of_birth, 
                 gender, address, medical_history, notes, user_id)
                VALUES
                (:first_name, :last_name, :email, :phone, :date_of_birth,
                 :gender, :address, :medical_history, :notes, :user_id)";
                
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Bind data
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':medical_history', $this->medical_history);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Update patient
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    address = :address,
                    medical_history = :medical_history,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Bind data
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':date_of_birth', $this->date_of_birth);
        $stmt->bindParam(':gender', $this->gender);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':medical_history', $this->medical_history);
        $stmt->bindParam(':notes', $this->notes);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Delete patient
    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                 WHERE id = :id AND user_id = :user_id";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
    
    // Send email to patient
    public function sendEmail($subject, $message) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, 'ClinicFlow PRO');
            $mail->addAddress($this->email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Failed to send email: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllPaginated($user_id, $search = '', $offset = 0, $per_page = 10) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($search)) {
            $query .= " AND (first_name LIKE :search1 
                       OR last_name LIKE :search2 
                       OR email LIKE :search3 
                       OR phone LIKE :search4)";
            $searchTerm = "%{$search}%";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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

    public function getCount($user_id, $search = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                  WHERE user_id = :user_id";
        
        $params = [':user_id' => $user_id];

        if (!empty($search)) {
            $query .= " AND (first_name LIKE :search1 
                       OR last_name LIKE :search2 
                       OR email LIKE :search3 
                       OR phone LIKE :search4)";
            $searchTerm = "%{$search}%";
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
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