<?php
require_once '../../config/app_config.php';
require_once '../../config/paypal_config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/views/login.php');
    exit();
}

$pageTitle = "Subscription Plans";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ClinicFlow PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&vault=true"></script>
</head>
<body>
    <?php include_once BASE_PATH . '/includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Choose Your Plan</h2>
                <?php if (isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'trial'): ?>
                    <div class="alert alert-info">
                        Your trial period will expire soon. Choose a plan to continue using our services.
                    </div>
                <?php elseif (isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] === 'expired'): ?>
                    <div class="alert alert-warning">
                        Your subscription has expired. Please choose a plan to continue using our services.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row justify-content-center mt-4">
            <!-- Monthly Plan -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="my-0">Monthly Plan</h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h1 class="card-title text-center">$49<small class="text-muted">/mo</small></h1>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li>✓ All Features</li>
                            <li>✓ Unlimited Patients</li>
                            <li>✓ Email Support</li>
                            <li>✓ Regular Updates</li>
                        </ul>
                        <div class="mt-auto">
                            <div id="paypal-monthly-button"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Yearly Plan -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="my-0">Yearly Plan</h4>
                        <span class="badge bg-warning">Save 20%</span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h1 class="card-title text-center">$39<small class="text-muted">/mo</small></h1>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li>✓ All Features</li>
                            <li>✓ Unlimited Patients</li>
                            <li>✓ Priority Support</li>
                            <li>✓ Regular Updates</li>
                            <li>✓ Billed annually ($468)</li>
                        </ul>
                        <div class="mt-auto">
                            <div id="paypal-yearly-button"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        paypal.Buttons({
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': '<?php echo PAYPAL_MONTHLY_PLAN_ID; ?>'
                });
            },
            onApprove: function(data, actions) {
                return fetch('<?php echo BASE_URL; ?>/modules/subscription/process-subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        subscriptionID: data.subscriptionID,
                        planType: 'monthly'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        window.location.href = '<?php echo BASE_URL; ?>/modules/subscription/thank-you.php';
                    } else {
                        alert('Error processing subscription: ' + data.error);
                    }
                });
            }
        }).render('#paypal-monthly-button');

        paypal.Buttons({
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': '<?php echo PAYPAL_YEARLY_PLAN_ID; ?>'
                });
            },
            onApprove: function(data, actions) {
                return fetch('<?php echo BASE_URL; ?>/modules/subscription/process-subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        subscriptionID: data.subscriptionID,
                        planType: 'yearly'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        window.location.href = '<?php echo BASE_URL; ?>/modules/subscription/thank-you.php';
                    } else {
                        alert('Error processing subscription: ' + data.error);
                    }
                });
            }
        }).render('#paypal-yearly-button');
    </script>
</body>
</html> 