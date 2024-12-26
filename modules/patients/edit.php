<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once 'models/Patient.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Edit Patient";
$message = '';
$error = '';

// Initialize Patient model
$patient = new Patient();
$patient->id = $_GET['id'] ?? 0;
$patient->user_id = $_SESSION['user_id'];

// Get patient data
$patientData = $patient->getOne();
if (!$patientData) {
    header('Location: index.php?error=' . urlencode('Patient not found'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set patient properties
    $patient->first_name = $_POST['first_name'] ?? '';
    $patient->last_name = $_POST['last_name'] ?? '';
    $patient->email = $_POST['email'] ?? '';
    $patient->phone = $_POST['phone'] ?? '';
    $patient->date_of_birth = $_POST['date_of_birth'] ?? '';
    $patient->gender = $_POST['gender'] ?? '';
    $patient->address = $_POST['address'] ?? '';
    $patient->medical_history = $_POST['medical_history'] ?? '';
    $patient->notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($patient->first_name) || empty($patient->last_name) || empty($patient->phone)) {
        $error = 'First name, last name, and phone are required fields.';
    } else {
        if ($patient->update()) {
            header('Location: index.php?message=' . urlencode('Patient updated successfully'));
            exit;
        } else {
            $error = 'Failed to update patient. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-edit me-2"></i><?php echo $pageTitle; ?></h2>
                <div>
                    <a href="view.php?id=<?php echo $patientData['id']; ?>" class="btn btn-outline-primary me-2">
                        <i class="fas fa-eye me-1"></i> View Details
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Personal Information</h5>
                            
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required
                                       value="<?php echo htmlspecialchars($patientData['first_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required
                                       value="<?php echo htmlspecialchars($patientData['last_name']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($patientData['email']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required
                                       value="<?php echo htmlspecialchars($patientData['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                       value="<?php echo htmlspecialchars($patientData['date_of_birth']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo $patientData['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $patientData['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $patientData['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Medical Information -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Additional Information</h5>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($patientData['address']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="medical_history" class="form-label">Medical History</label>
                                <textarea class="form-control" id="medical_history" name="medical_history" rows="3"><?php echo htmlspecialchars($patientData['medical_history']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($patientData['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Patient
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 