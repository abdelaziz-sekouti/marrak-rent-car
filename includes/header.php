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
    <!-- tailwind cdn-->
     <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>


    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="bg-blue-400 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="<?php echo BASE_URL; ?>" class="text-white text-xl font-bold flex items-center hover:text-blue-200 transition-colors">
                        <i class="fas fa-car mr-2"></i>
                        Marrak Rent Car
                    </a>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="<?php echo BASE_URL; ?>/views/index.php" class="text-white hover:bg-blue-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : ''; ?>">
                            Home
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/cars.php" class="text-white hover:bg-blue-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'bg-blue-900' : ''; ?>">
                            Cars
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/about.php" class="text-white hover:bg-blue-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'bg-blue-900' : ''; ?>">
                            About
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/contact.php" class="text-white hover:bg-blue-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'bg-blue-900' : ''; ?>">
                            Contact
                        </a>
                    </div>
                </div>
                
                <!-- Desktop User Actions -->
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6 space-x-3">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="flex items-center text-white">
                                <i class="fas fa-user-circle mr-2 text-blue-200"></i>
                                <span class="text-sm"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </div>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/" class="text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                    <i class="fas fa-cog mr-1"></i>
                                    Admin
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/views/logout.php" class="text-white bg-red-600 hover:bg-red-700 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                <i class="fas fa-sign-out-alt mr-1"></i>
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/views/login.php" class="text-white border border-white hover:bg-white hover:text-blue-800 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                <i class="fas fa-sign-in-alt mr-1"></i>
                                Login
                            </a>
                            <a href="<?php echo BASE_URL; ?>/views/register.php" class="text-white bg-green-600 hover:bg-green-700 px-3 py-2 rounded-md text-xs font-medium transition-colors">
                                <i class="fas fa-user-plus mr-1"></i>
                                Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="mobile-menu-button text-white hover:bg-blue-700 p-2 rounded-md transition-colors" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation Menu -->
        <div class="mobile-menu hidden md:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-blue-900 border-t border-blue-700">
                <!-- Mobile Navigation Links -->
                <a href="<?php echo BASE_URL; ?>/views/index.php" class="text-white hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-home mr-2"></i>
                    Home
                </a>
                <a href="<?php echo BASE_URL; ?>/views/cars.php" class="text-white hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-car mr-2"></i>
                    Cars
                </a>
                <a href="<?php echo BASE_URL; ?>/views/about.php" class="text-white hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-info-circle mr-2"></i>
                    About
                </a>
                <a href="<?php echo BASE_URL; ?>/views/contact.php" class="text-white hover:bg-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'bg-blue-700' : ''; ?>">
                    <i class="fas fa-envelope mr-2"></i>
                    Contact
                </a>
                
                <!-- Mobile User Actions -->
                <div class="border-t border-blue-700 pt-2 mt-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="text-white px-3 py-2 text-sm bg-blue-800 rounded-md mb-2">
                            <i class="fas fa-user-circle mr-2"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/" class="text-white bg-blue-600 hover:bg-blue-700 block px-3 py-2 rounded-md text-base font-medium transition-colors mb-1">
                                <i class="fas fa-cog mr-2"></i>
                                Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/views/logout.php" class="text-white bg-red-600 hover:bg-red-700 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/views/login.php" class="text-white border border-white hover:bg-white hover:text-blue-800 block px-3 py-2 rounded-md text-base font-medium transition-colors mb-1">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login
                        </a>
                        <a href="<?php echo BASE_URL; ?>/views/register.php" class="text-white bg-green-600 hover:bg-green-700 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>
                            Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md p-4 animate-fade-in <?php echo $_SESSION['flash_type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : ($_SESSION['flash_type'] === 'error' ? 'bg-red-100 border border-red-400 text-red-700' : 'bg-blue-100 border border-blue-400 text-blue-700'); ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($_SESSION['flash_type'] === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php elseif ($_SESSION['flash_type'] === 'error'): ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-info-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            <?php 
                            echo htmlspecialchars($_SESSION['flash_message']);
                            unset($_SESSION['flash_message']);
                            unset($_SESSION['flash_type']);
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->