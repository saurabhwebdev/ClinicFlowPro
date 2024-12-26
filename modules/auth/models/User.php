<?php
require_once __DIR__ . '/../../../config/database.php';

class User {
    protected $conn;
    private $table = 'users';
    
    public $id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $is_verified;
    public $verification_token;
    public $subscription_status;
    public $trial_ends_at;
    public $subscription_ends_at;
    public $subscription_plan_id;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (email, password, first_name, last_name, verification_token, 
                subscription_status, trial_ends_at)
                VALUES (:email, :password, :first_name, :last_name, :verification_token,
                'trial', DATE_ADD(NOW(), INTERVAL 14 DAY))";
                
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->verification_token = bin2hex(random_bytes(32));
        
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':verification_token', $this->verification_token);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function verifyEmail($token) {
        $query = "UPDATE " . $this->table . "
                 SET is_verified = true,
                     verification_token = NULL
                 WHERE verification_token = :token";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        
        return $stmt->execute();
    }
    
    public function checkSubscriptionStatus() {
        $query = "SELECT subscription_status, subscription_ends_at, trial_ends_at 
                  FROM " . $this->table . " 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return 'expired'; // Return expired if no result found
        }
        
        if ($result['subscription_status'] === 'trial' && 
            $result['trial_ends_at'] && 
            strtotime($result['trial_ends_at']) < time()) {
            // Trial has expired
            $this->updateSubscriptionStatus('expired');
            return 'expired';
        }
        
        if ($result['subscription_status'] === 'active' && 
            $result['subscription_ends_at'] && 
            strtotime($result['subscription_ends_at']) < time()) {
            // Subscription has expired
            $this->updateSubscriptionStatus('expired');
            return 'expired';
        }
        
        return $result['subscription_status'];
    }
    
    private function updateSubscriptionStatus($status) {
        $query = "UPDATE " . $this->table . "
                SET subscription_status = :status
                WHERE id = :id";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    public function updateSubscription($planId, $endDate, $status = 'active') {
        $query = "UPDATE " . $this->table . " 
                  SET subscription_status = :status,
                      subscription_plan_id = :plan_id,
                      subscription_ends_at = :end_date
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':plan_id', $planId);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    public function getUserData() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateProfile($data) {
        // Check if email is already taken by another user
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE email = :email AND id != :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email is already taken'];
        }
        
        $query = "UPDATE " . $this->table . " 
                  SET first_name = :first_name,
                      last_name = :last_name,
                      email = :email
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update profile'];
    }
    
    public function changePassword($currentPassword, $newPassword) {
        // Verify current password
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " 
                  SET password = :password
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update password'];
    }
    
    public function cancelSubscription() {
        $query = "UPDATE " . $this->table . " 
                  SET subscription_status = 'cancelled',
                      subscription_ends_at = NOW(),
                      subscription_plan_id = NULL
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to cancel subscription'];
    }
    
    public function deleteAccount() {
        // First check if user exists
        $query = "SELECT email FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Generate deletion token and expiry
        $deletionToken = bin2hex(random_bytes(32));
        $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store deletion token
        $query = "UPDATE " . $this->table . "
                  SET deletion_token = :token,
                      deletion_token_expiry = :expiry
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $deletionToken);
        $stmt->bindParam(':expiry', $expiryTime);
        $stmt->bindParam(':id', $this->id);
        
        if ($stmt->execute()) {
            // Send deletion confirmation email
            $this->sendDeletionEmail($user['email'], $deletionToken);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to initiate account deletion'];
    }
    
    private function sendDeletionEmail($email, $token) {
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
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Confirm Account Deletion - ClinicFlow PRO';
            
            $confirmUrl = BASE_URL . '/modules/profile/confirm-deletion.php?token=' . $token;
            
            $mail->Body = "
                <h2>Account Deletion Confirmation</h2>
                <p>We received a request to delete your ClinicFlow PRO account.</p>
                <p>If you did not request this, please ignore this email and ensure your account is secure.</p>
                <p>To confirm account deletion, click the link below (valid for 1 hour):</p>
                <p><a href='$confirmUrl'>Confirm Account Deletion</a></p>
                <p>Warning: This action cannot be undone. All your data will be permanently deleted.</p>
            ";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Failed to send deletion email: ' . $e->getMessage());
            return false;
        }
    }
} 