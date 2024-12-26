<?php
require_once '../../config/app_config.php';
require_once '../auth/middleware/SubscriptionMiddleware.php';
require_once '../auth/models/User.php';

// Check authentication
SubscriptionMiddleware::checkAccess();

$pageTitle = "Profile Settings";
$message = '';
$error = '';

// Get user data
$user = new User();
$user->id = $_SESSION['user_id'];
$userData = $user->getUserData();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $user->updateProfile([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email']
        ]);
        
        if ($result['success']) {
            $message = 'Profile updated successfully!';
            $userData = $user->getUserData(); // Refresh data
            $_SESSION['email'] = $userData['email']; // Update session
        } else {
            $error = $result['error'];
        }
    } elseif (isset($_POST['change_password'])) {
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
            $error = 'All password fields are required';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'New passwords do not match';
        } else {
            $result = $user->changePassword(
                $_POST['current_password'],
                $_POST['new_password']
            );
            
            if ($result['success']) {
                $message = 'Password changed successfully!';
            } else {
                $error = $result['error'];
            }
        }
    } elseif (isset($_POST['cancel_subscription'])) {
        if (isset($_POST['confirm_cancellation']) && $_POST['confirm_cancellation'] === 'yes') {
            $result = $user->cancelSubscription();
            if ($result['success']) {
                $message = 'Subscription cancelled successfully.';
                $userData = $user->getUserData(); // Refresh data
                $_SESSION['subscription_status'] = 'cancelled';
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'Please confirm cancellation by checking the checkbox.';
        }
    } elseif (isset($_POST['delete_account'])) {
        if (isset($_POST['confirm_deletion']) && $_POST['confirm_deletion'] === 'yes') {
            $result = $user->deleteAccount();
            if ($result['success']) {
                $message = 'Please check your email to confirm account deletion.';
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'Please confirm deletion by checking the checkbox.';
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
        <div class="row">
            <div class="col-md-12 mb-4">
                <h2><i class="fas fa-user-cog me-2"></i><?php echo $pageTitle; ?></h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                       title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Account Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $userData['subscription_status'] === 'active' ? 'success' : 
                                            ($userData['subscription_status'] === 'trial' ? 'warning' : 
                                                ($userData['subscription_status'] === 'cancelled' ? 'secondary' : 'danger')); ?>">
                                        <?php echo ucfirst($userData['subscription_status']); ?>
                                    </span>
                                </p>
                                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($userData['created_at'])); ?></p>
                                <?php if ($userData['subscription_plan_id']): ?>
                                    <p><strong>Subscription ID:</strong> 
                                        <span class="text-muted"><?php echo htmlspecialchars($userData['subscription_plan_id']); ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php if ($userData['subscription_status'] === 'trial'): ?>
                                    <p><strong>Trial Ends:</strong> <?php echo date('F j, Y', strtotime($userData['trial_ends_at'])); ?></p>
                                <?php elseif ($userData['subscription_status'] === 'active'): ?>
                                    <p><strong>Subscription Ends:</strong> <?php echo date('F j, Y', strtotime($userData['subscription_ends_at'])); ?></p>
                                    <p><strong>Plan Type:</strong> 
                                        <?php 
                                        // Determine plan type based on subscription_plan_id
                                        $planType = strpos($userData['subscription_plan_id'], 'MONTHLY') !== false ? 'Monthly' : 'Yearly';
                                        echo $planType;
                                        ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($userData['subscription_status'] === 'active'): ?>
                <div class="col-md-12 mt-4">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Subscription</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Warning:</strong> Cancelling your subscription will:
                                <ul class="mb-0 mt-2">
                                    <li>Immediately end your current subscription</li>
                                    <li>Remove access to premium features</li>
                                    <li>Cannot be undone - you'll need to subscribe again</li>
                                </ul>
                            </div>
                            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to cancel your subscription? This action cannot be undone.');">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="confirm_cancellation" name="confirm_cancellation" value="yes" required>
                                    <label class="form-check-label" for="confirm_cancellation">
                                        I understand that by cancelling my subscription, I will lose access to premium features immediately.
                                    </label>
                                </div>
                                <button type="submit" name="cancel_subscription" class="btn btn-danger">
                                    <i class="fas fa-times-circle me-1"></i> Cancel Subscription
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="col-md-12 mt-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-trash-alt me-2"></i>Delete Account</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Deleting your account will:
                            <ul class="mb-0 mt-2">
                                <li>Permanently remove all your data</li>
                                <li>Cancel any active subscriptions</li>
                                <li>Cannot be undone</li>
                            </ul>
                        </div>
                        <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action CANNOT be undone!');">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="confirm_deletion" name="confirm_deletion" value="yes" required>
                                <label class="form-check-label" for="confirm_deletion">
                                    I understand that by deleting my account, I will lose all data permanently.
                                </label>
                            </div>
                            <button type="submit" name="delete_account" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1"></i> Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 