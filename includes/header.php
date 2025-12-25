<?php 
// Check if constants already defined before including
if (!defined('BASE_URL')) {
    require_once 'init.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Marrak Rent Car'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Premium car rental services'; ?>">
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        /* Additional custom styles if needed */
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="navbar sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="<?php echo BASE_URL; ?>" class="navbar-brand">
                        <i class="fas fa-car mr-2"></i>
                        Marrak Rent Car
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:block">
                    <div class="navbar-nav">
                        <a href="<?php echo BASE_URL; ?>/views/index.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            Home
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/cars.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : ''; ?>">
                            Cars
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/about.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                            About
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/contact.php" class="navbar-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">
                            Contact
                        </a>
                    </div>
                </div>
                
                <!-- User Actions -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative">
                            <button class="flex items-center text-gray-700 hover:text-primary-600">
                                <i class="fas fa-user-circle mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </button>
                        </div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/" class="btn btn-outline text-sm">
                                <i class="fas fa-cog mr-1"></i>
                                Admin
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/views/logout.php" class="btn btn-secondary text-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/views/login.php" class="btn btn-outline text-sm">
                            <i class="fas fa-sign-in-alt mr-1"></i>
                            Login
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/register.php" class="btn btn-primary text-sm">
                            <i class="fas fa-user-plus mr-1"></i>
                            Register
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-700 hover:text-primary-600 focus:outline-none" id="mobile-menu-button">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t">
                <a href="<?php echo BASE_URL; ?>" class="navbar-link block">Home</a>
                <a href="<?php echo BASE_URL; ?>/views/cars.php" class="navbar-link block">Cars</a>
                <a href="<?php echo BASE_URL; ?>/views/about.php" class="navbar-link block">About</a>
                <a href="<?php echo BASE_URL; ?>/views/contact.php" class="navbar-link block">Contact</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="border-t pt-2">
                        <div class="px-3 py-2 text-sm text-gray-700">
                            <i class="fas fa-user-circle mr-2"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/" class="navbar-link block">
                                <i class="fas fa-cog mr-1"></i> Admin
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/views/logout.php" class="navbar-link block">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="border-t pt-2 space-y-1">
                        <a href="<?php echo BASE_URL; ?>/views/login.php" class="navbar-link block">
                            <i class="fas fa-sign-in-alt mr-1"></i> Login
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/register.php" class="navbar-link block">
                            <i class="fas fa-user-plus mr-1"></i> Register
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> animate-fade-in">
                <?php 
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->