<?php
require_once __DIR__ . '/../../../config/database.php';

class Settings {
    private $conn;
    private $table = 'clinic_settings';
    
    public $id;
    public $user_id;
    public $clinic_name;
    public $logo_path;
    public $email;
    public $phone;
    public $website;
    public $address;
    public $country;
    public $currency;
    public $working_days;
    public $working_hours_start;
    public $working_hours_end;
    public $default_appointment_duration;
    public $doctor_name;
    public $license_number;
    public $degree;
    public $specialization;
    public $awards_recognition;
    public $signature_path;
    public $gst_number;
    public $tax_registration_number;
    public $holidays;
    public $time_zone;
    public $appointment_reminder_before;
    public $cancellation_policy;
    public $social_media_links;
    public $payment_methods;
    public $invoice_prefix;
    public $invoice_footer_text;
    public $sms_notifications;
    public $email_notifications;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    public function getSettings($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function saveSettings() {
        // Check if settings exist for this user
        $existingSettings = $this->getSettings($this->user_id);
        
        if ($existingSettings) {
            return $this->updateSettings();
        } else {
            return $this->createSettings();
        }
    }
    
    private function createSettings() {
        $query = "INSERT INTO " . $this->table . "
                (user_id, clinic_name, logo_path, email, phone, website, 
                 address, country, currency, working_days, working_hours_start,
                 working_hours_end, default_appointment_duration, doctor_name,
                 license_number, degree, specialization, awards_recognition,
                 signature_path, gst_number, tax_registration_number, holidays,
                 time_zone, appointment_reminder_before, cancellation_policy,
                 social_media_links, payment_methods, invoice_prefix,
                 invoice_footer_text, sms_notifications, email_notifications)
                VALUES
                (:user_id, :clinic_name, :logo_path, :email, :phone, :website,
                 :address, :country, :currency, :working_days, :working_hours_start,
                 :working_hours_end, :default_appointment_duration, :doctor_name,
                 :license_number, :degree, :specialization, :awards_recognition,
                 :signature_path, :gst_number, :tax_registration_number, :holidays,
                 :time_zone, :appointment_reminder_before, :cancellation_policy,
                 :social_media_links, :payment_methods, :invoice_prefix,
                 :invoice_footer_text, :sms_notifications, :email_notifications)";
                 
        return $this->executeQuery($query);
    }
    
    private function updateSettings() {
        $query = "UPDATE " . $this->table . "
                SET clinic_name = :clinic_name,
                    logo_path = :logo_path,
                    email = :email,
                    phone = :phone,
                    website = :website,
                    address = :address,
                    country = :country,
                    currency = :currency,
                    working_days = :working_days,
                    working_hours_start = :working_hours_start,
                    working_hours_end = :working_hours_end,
                    default_appointment_duration = :default_appointment_duration,
                    doctor_name = :doctor_name,
                    license_number = :license_number,
                    degree = :degree,
                    specialization = :specialization,
                    awards_recognition = :awards_recognition,
                    signature_path = :signature_path,
                    gst_number = :gst_number,
                    tax_registration_number = :tax_registration_number,
                    holidays = :holidays,
                    time_zone = :time_zone,
                    appointment_reminder_before = :appointment_reminder_before,
                    cancellation_policy = :cancellation_policy,
                    social_media_links = :social_media_links,
                    payment_methods = :payment_methods,
                    invoice_prefix = :invoice_prefix,
                    invoice_footer_text = :invoice_footer_text,
                    sms_notifications = :sms_notifications,
                    email_notifications = :email_notifications
                WHERE user_id = :user_id";
                
        return $this->executeQuery($query);
    }
    
    private function executeQuery($query) {
        try {
            $stmt = $this->conn->prepare($query);
            
            // Clean and bind data
            $cleanData = $this->cleanData();
            
            // Debug output for holidays
            error_log('Holidays data being saved: ' . $cleanData['holidays']);
            
            foreach ($cleanData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            if ($stmt->execute()) {
                return ['success' => true];
            }
            
            error_log('SQL Error: ' . print_r($stmt->errorInfo(), true));
            return ['success' => false, 'error' => 'Failed to save settings'];
        } catch (Exception $e) {
            error_log('Exception in executeQuery: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function cleanData() {
        return [
            'user_id' => $this->user_id,
            'clinic_name' => htmlspecialchars(strip_tags($this->clinic_name)),
            'logo_path' => $this->logo_path,
            'email' => htmlspecialchars(strip_tags($this->email)),
            'phone' => htmlspecialchars(strip_tags($this->phone)),
            'website' => htmlspecialchars(strip_tags($this->website)),
            'address' => htmlspecialchars(strip_tags($this->address)),
            'country' => htmlspecialchars(strip_tags($this->country)),
            'currency' => htmlspecialchars(strip_tags($this->currency)),
            'working_days' => $this->working_days,
            'working_hours_start' => $this->working_hours_start,
            'working_hours_end' => $this->working_hours_end,
            'default_appointment_duration' => (int)$this->default_appointment_duration,
            'doctor_name' => htmlspecialchars(strip_tags($this->doctor_name)),
            'license_number' => htmlspecialchars(strip_tags($this->license_number)),
            'degree' => htmlspecialchars(strip_tags($this->degree)),
            'specialization' => htmlspecialchars(strip_tags($this->specialization)),
            'awards_recognition' => htmlspecialchars(strip_tags($this->awards_recognition)),
            'signature_path' => $this->signature_path,
            'gst_number' => htmlspecialchars(strip_tags($this->gst_number)),
            'tax_registration_number' => htmlspecialchars(strip_tags($this->tax_registration_number)),
            'holidays' => $this->holidays,
            'time_zone' => htmlspecialchars(strip_tags($this->time_zone)),
            'appointment_reminder_before' => (int)$this->appointment_reminder_before,
            'cancellation_policy' => htmlspecialchars(strip_tags($this->cancellation_policy)),
            'social_media_links' => $this->social_media_links,
            'payment_methods' => $this->payment_methods,
            'invoice_prefix' => htmlspecialchars(strip_tags($this->invoice_prefix)),
            'invoice_footer_text' => htmlspecialchars(strip_tags($this->invoice_footer_text)),
            'sms_notifications' => (bool)$this->sms_notifications,
            'email_notifications' => (bool)$this->email_notifications
        ];
    }
} 