<?php
require_once '../../config/app_config.php';
require_once '../auth/models/User.php';

$pageTitle = "Confirm Account Deletion";
$message = '';
$error = '';

if (!isset($_GET['token'])) {
    header('Location: ' . BASE_URL);
    exit();
}

$token = $_GET['token'];

// Delete the account
$query = "DELETE FROM users 
          WHERE deletion_token = :token 
          AND deletion_token_expiry > NOW()";
          
$database = new Database();
$conn = $database->connect();
$stmt = $conn->prepare($query);
$stmt->bindParam(':token', $token);

if ($stmt->execute() && $stmt->rowCount() > 0) {
    session_start();
    session_destroy();
    $message = "Your account has been successfully deleted.";
} else {
    $error = "Invalid or expired deletion token.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                            <p>We're sorry to see you go. You can always create a new account if you change your mind.</p>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">Return to Homepage</a>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/modules/profile/index.php" class="btn btn-primary">Return to Profile</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 