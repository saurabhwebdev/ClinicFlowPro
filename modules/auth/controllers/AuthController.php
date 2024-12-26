<?php
require_once __DIR__ . '/../../../config/app_config.php';
require_once __DIR__ . '/../../../config/smtp_config.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once __DIR__ . '/../models/User.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    public function register($data) {
        // Validate input
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || 
            empty($data['last_name']) || empty($data['terms'])) {
            return ['success' => false, 'error' => 'Please fill in all required fields'];
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        // Validate password strength
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $data['password'])) {
            return ['success' => false, 'error' => 'Password does not meet requirements'];
        }

        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        $this->user->email = $data['email'];
        $this->user->password = $data['password'];
        $this->user->first_name = $data['first_name'];
        $this->user->last_name = $data['last_name'];
        
        if($this->user->create()) {
            $this->sendVerificationEmail();
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
    
    private function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->user->getConnection()->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    private function sendVerificationEmail() {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($this->user->email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email';
            $mail->Body = $this->getVerificationEmailTemplate();
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getVerificationEmailTemplate() {
        $verificationLink = BASE_URL . "/modules/auth/verify-email.php?token=" . $this->user->verification_token;
        
        return "
        <html>
        <body>
            <h2>Welcome to " . SMTP_FROM_NAME . "!</h2>
            <p>Thank you for registering. Your 14-day trial period has begun.</p>
            <p>Please click the link below to verify your email address:</p>
            <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
            <p>Your trial will expire in 14 days. After that, you'll need to choose a subscription plan to continue using our services.</p>
            <br>
            <p>Best regards,<br>" . SMTP_FROM_NAME . " Team</p>
        </body>
        </html>";
    }
    
    public function login($data) {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }
        
        $query = "SELECT id, email, password, first_name, last_name, is_verified, subscription_status 
                  FROM users 
                  WHERE email = :email";
                  
        $stmt = $this->user->getConnection()->prepare($query);
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        if (!$user['is_verified']) {
            return ['success' => false, 'error' => 'Please verify your email before logging in'];
        }
        
        // Start session and store user data
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['subscription_status'] = $user['subscription_status'];
        
        return ['success' => true];
    }

    public function sendPasswordResetLink($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        $query = "SELECT id FROM users WHERE email = :email AND is_verified = 1";
        $stmt = $this->user->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Email not found or account not verified'];
        }

        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "UPDATE users SET reset_token = :token, reset_token_expiry = :expiry 
                  WHERE email = :email";
        $stmt = $this->user->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':email', $email);

        if ($stmt->execute() && $this->sendPasswordResetEmail($email, $token)) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to send reset link'];
    }

    private function sendPasswordResetEmail($email, $token) {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);
            
            $resetLink = BASE_URL . "/modules/auth/views/reset-password.php?token=" . $token;
            
            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body = "
            <html>
            <body>
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password. This link will expire in 1 hour.</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>If you didn't request this, please ignore this email.</p>
                <br>
                <p>Best regards,<br>" . SMTP_FROM_NAME . " Team</p>
            </body>
            </html>";
            
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    public function resetPassword($token, $password) {
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
            return ['success' => false, 'error' => 'Password does not meet requirements'];
        }

        $query = "SELECT id FROM users 
                  WHERE reset_token = :token 
                  AND reset_token_expiry > NOW()";
        $stmt = $this->user->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Invalid or expired reset token'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users 
                  SET password = :password, reset_token = NULL, reset_token_expiry = NULL 
                  WHERE reset_token = :token";
        $stmt = $this->user->conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':token', $token);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to reset password'];
    }

    public function verifyEmail($token) {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Invalid verification token'];
        }
        
        // Call the User model's verifyEmail method
        if ($this->user->verifyEmail($token)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Email verification failed'];
    }
} 