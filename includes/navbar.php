<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app_config.php';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" 
     style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-tooth me-2"></i>
            <span class="fw-bold">ClinicFlow PRO</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Logged in navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/dashboard/index.php">
                            <i class="fas fa-chart-line me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?php echo strpos($_SERVER['PHP_SELF'], '/patients/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/patients/index.php">
                            <i class="fas fa-users me-1"></i> Patients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?php echo strpos($_SERVER['PHP_SELF'], '/appointments/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/appointments/index.php">
                            <i class="fas fa-calendar-alt me-1"></i> Appointments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?php echo strpos($_SERVER['PHP_SELF'], '/prescriptions/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/prescriptions/index.php">
                            <i class="fas fa-prescription me-1"></i> Prescriptions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentModule === 'invoices' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/invoices/">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center <?php echo strpos($_SERVER['PHP_SELF'], '/clinic_settings/') !== false ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/modules/clinic_settings/settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <?php if (isset($_SESSION['subscription_status']) && $_SESSION['subscription_status'] !== 'active'): ?>
                        <a href="<?php echo BASE_URL; ?>/modules/subscription/plans.php" 
                           class="btn btn-warning btn-sm px-3 rounded-pill d-flex align-items-center">
                            <i class="fas fa-crown me-1"></i> Upgrade Plan
                        </a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center" 
                                type="button" 
                                id="userDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <div class="user-avatar">
                                <?php
                                $firstName = $_SESSION['first_name'] ?? '';
                                $lastName = $_SESSION['last_name'] ?? '';
                                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                                ?>
                                <span class="avatar-circle"><?php echo $initials; ?></span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header">Account</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/profile/index.php">
                                    <i class="fas fa-user-cog me-2"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/inventory/">
                                    <i class="fas fa-boxes me-2"></i> Inventory Management
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/modules/reports/index.php">
                                    <i class="fas fa-chart-bar me-2"></i> Reports
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/modules/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <!-- Public navigation -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light rounded-pill px-3 d-flex align-items-center" 
                           href="<?php echo BASE_URL; ?>/modules/auth/views/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-light rounded-pill px-3 d-flex align-items-center" 
                           href="<?php echo BASE_URL; ?>/modules/auth/views/register.php">
                            <i class="fas fa-rocket me-1"></i> Start Free Trial
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Add this in your header section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Add this CSS after your Bootstrap CSS -->
<style>
.navbar {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.navbar-nav .nav-link {
    position: relative;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500;
}

.navbar-nav .nav-link:hover {
    color: #ffffff !important;
    transform: translateY(-1px);
}

.navbar-nav .nav-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 50%;
    background-color: #ffd700;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.navbar-nav .nav-link:hover::after {
    width: 80%;
}

.dropdown-menu {
    border-radius: 0.5rem;
    margin-top: 0.5rem;
    background-color: #ffffff;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: none;
    padding: 0.5rem;
}

.dropdown-item {
    padding: 0.7rem 1rem;
    transition: all 0.2s ease;
    color: #2c3e50 !important;
    border-radius: 0.3rem;
    font-weight: 500;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
    color: #3498db !important;
}

.dropdown-header {
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.5rem 1rem;
}

.btn {
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
    font-size: 1.4rem;
    color: #ffffff !important;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.dropdown-toggle {
    color: rgba(255, 255, 255, 0.9) !important;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.dropdown-toggle:hover {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.1);
}

.btn-outline-light {
    color: #ffffff !important;
    border-width: 2px;
}

.btn-light {
    color: #2c3e50 !important;
    font-weight: 600;
}

.btn-warning {
    color: #000 !important;
    background: #ffd700;
    border: none;
    font-weight: 600;
}

.btn-warning:hover {
    background: #ffed4a;
}

.nav-link.active {
    color: #ffffff !important;
    font-weight: 600;
}

.dropdown-divider {
    border-color: #edf2f7;
    margin: 0.5rem 0;
}

.dropdown-item.text-danger {
    color: #dc3545 !important;
}

.dropdown-item.text-danger:hover {
    background-color: #fff5f5;
    color: #dc3545 !important;
}

/* Add glow effect to the upgrade button */
.btn-warning {
    box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
}

.btn-warning:hover {
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
}

/* Active link indicator */
.nav-link.active::after {
    width: 80%;
    background-color: #ffd700;
}

/* Improve mobile menu */
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .navbar-nav .nav-link {
        padding: 0.75rem 1rem;
    }
    
    .dropdown-menu {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-item {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
    }
}

/* Add subtle animation to icons */
.nav-link i, 
.dropdown-item i {
    transition: transform 0.3s ease;
}

.nav-link:hover i, 
.dropdown-item:hover i {
    transform: translateX(2px);
}

.avatar-circle {
    width: 36px;
    height: 36px;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
}

.avatar-circle:hover {
    background-color: rgba(255, 255, 255, 0.3);
}
</style> 