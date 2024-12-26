<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../patients/models/Patient.php';
require_once __DIR__ . '/../../appointments/models/Appointment.php';
require_once __DIR__ . '/../../prescriptions/models/Prescription.php';
require_once __DIR__ . '/../../invoices/models/Invoice.php';

class Report {
    private $conn;
    private $patient;
    private $appointment;
    private $prescription;
    private $invoice;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        
        $this->patient = new Patient();
        $this->appointment = new Appointment();
        $this->prescription = new Prescription();
        $this->invoice = new Invoice();
    }
    
    public function getPatientMetrics($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_patients,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_patients,
                    COUNT(CASE WHEN gender = 'male' THEN 1 END) as male_patients,
                    COUNT(CASE WHEN gender = 'female' THEN 1 END) as female_patients,
                    AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as avg_age
                 FROM patients 
                 WHERE user_id = :user_id";
        
        if ($start_date && $end_date) {
            $query .= " AND created_at BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAppointmentMetrics($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_appointments,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_appointments,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_appointments,
                    COUNT(CASE WHEN date >= CURDATE() THEN 1 END) as upcoming_appointments
                 FROM appointments 
                 WHERE user_id = :user_id";
        
        if ($start_date && $end_date) {
            $query .= " AND date BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getFinancialMetrics($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_invoice_amount,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices,
                    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
                 FROM invoices 
                 WHERE user_id = :user_id";
        
        if ($start_date && $end_date) {
            $query .= " AND created_at BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getPrescriptionMetrics($user_id, $start_date = null, $end_date = null) {
        $query = "SELECT 
                    COUNT(*) as total_prescriptions,
                    COUNT(DISTINCT patient_id) as unique_patients,
                    COUNT(*) / COUNT(DISTINCT patient_id) as prescriptions_per_patient
                 FROM prescriptions 
                 WHERE user_id = :user_id";
        
        if ($start_date && $end_date) {
            $query .= " AND created_at BETWEEN :start_date AND :end_date";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($start_date && $end_date) {
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 