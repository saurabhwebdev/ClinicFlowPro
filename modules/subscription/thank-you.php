<?php
require_once '../../config/app_config.php';
require_once '../auth/models/User.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
    exit();
}

// Refresh subscription status
$user = new User();
$user->id = $_SESSION['user_id'];
$_SESSION['subscription_status'] = $user->checkSubscriptionStatus();

$pageTitle = "Subscription Confirmed";
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
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h2 class="mb-4">Thank You!</h2>
                        <div class="alert alert-success">
                            Your subscription has been activated successfully.
                        </div>
                        <p>You now have full access to all features of ClinicFlow PRO.</p>
                        <a href="<?php echo BASE_URL; ?>/modules/dashboard/index.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 