<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

$message = '';
$errors = [];
$settings = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: settings.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'general') {
        $data = [
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_email' => trim($_POST['site_email'] ?? ''),
            'site_phone' => trim($_POST['site_phone'] ?? ''),
            'site_address' => trim($_POST['site_address'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'currency' => $_POST['currency'] ?? 'USD',
            'timezone' => $_POST['timezone'] ?? 'UTC'
        ];
        
        $errors = validateGeneralSettings($data);
        
        if (empty($errors)) {
            if (saveGeneralSettings($data)) {
                $_SESSION['flash_message'] = 'General settings updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: settings.php');
                exit;
            } else {
                $message = 'Failed to update settings';
            }
        }
    } elseif ($action === 'email') {
        $data = [
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => intval($_POST['smtp_port'] ?? 587),
            'smtp_username' => trim($_POST['smtp_username'] ?? ''),
            'smtp_password' => trim($_POST['smtp_password'] ?? ''),
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'email_from_name' => trim($_POST['email_from_name'] ?? ''),
            'email_from_address' => trim($_POST['email_from_address'] ?? '')
        ];
        
        $errors = validateEmailSettings($data);
        
        if (empty($errors)) {
            if (saveEmailSettings($data)) {
                $_SESSION['flash_message'] = 'Email settings updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: settings.php');
                exit;
            } else {
                $message = 'Failed to update email settings';
            }
        }
    } elseif ($action === 'payment') {
        $data = [
            'payment_gateway' => $_POST['payment_gateway'] ?? 'stripe',
            'stripe_public_key' => trim($_POST['stripe_public_key'] ?? ''),
            'stripe_secret_key' => trim($_POST['stripe_secret_key'] ?? ''),
            'paypal_client_id' => trim($_POST['paypal_client_id'] ?? ''),
            'paypal_client_secret' => trim($_POST['paypal_client_secret'] ?? ''),
            'paypal_sandbox' => isset($_POST['paypal_sandbox']) ? 1 : 0
        ];
        
        $errors = validatePaymentSettings($data);
        
        if (empty($errors)) {
            if (savePaymentSettings($data)) {
                $_SESSION['flash_message'] = 'Payment settings updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: settings.php');
                exit;
            } else {
                $message = 'Failed to update payment settings';
            }
        }
    } elseif ($action === 'backup') {
        if (createBackup()) {
            $_SESSION['flash_message'] = 'Database backup created successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to create backup';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: settings.php');
        exit;
    }
}

// Load current settings
$settings = loadAllSettings();

$page_title = 'System Settings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="System settings for Marrak Rent Car">
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .sidebar-link {
            @apply flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200;
        }
        .sidebar-link:hover {
            @apply bg-gray-100 text-gray-900;
        }
        .sidebar-link.active {
            @apply bg-primary-100 text-primary-700 border-r-2 border-primary-700;
        }
        .settings-tabs button {
            @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200;
        }
        .settings-tabs button.active {
            @apply bg-primary-600 text-white;
        }
        .settings-tabs button:not(.active) {
            @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="flex items-center h-16 px-6 border-b border-gray-200">
                <i class="fas fa-car text-primary-600 text-2xl mr-3"></i>
                <span class="text-xl font-bold text-gray-900">Admin Panel</span>
            </div>
            
            <?php require_once __DIR__ . '/aside.php';?>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
                            <p class="text-sm text-gray-600">Configure system parameters and preferences</p>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Settings Content -->
            <div class="p-6">
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> mb-6 animate-fade-in">
                        <?php 
                        echo htmlspecialchars($_SESSION['flash_message']);
                        unset($_SESSION['flash_message']);
                        unset($_SESSION['flash_type']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Settings Tabs -->
                <div class="mb-6">
                    <div class="settings-tabs space-x-2">
                        <button onclick="showTab('general')" id="tab-general" class="active">
                            <i class="fas fa-cog mr-2"></i>
                            General
                        </button>
                        <button onclick="showTab('email')" id="tab-email">
                            <i class="fas fa-envelope mr-2"></i>
                            Email
                        </button>
                        <button onclick="showTab('payment')" id="tab-payment">
                            <i class="fas fa-credit-card mr-2"></i>
                            Payment
                        </button>
                        <button onclick="showTab('backup')" id="tab-backup">
                            <i class="fas fa-database mr-2"></i>
                            Backup
                        </button>
                    </div>
                </div>
                
                <!-- General Settings -->
                <div id="panel-general" class="settings-panel">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">General Settings</h2>
                        </div>
                        <form method="POST" action="settings.php" class="p-6">
                            <input type="hidden" name="action" value="general">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['general']['site_name'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Email</label>
                                    <input type="email" name="site_email" value="<?php echo htmlspecialchars($settings['general']['site_email'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Phone</label>
                                    <input type="tel" name="site_phone" value="<?php echo htmlspecialchars($settings['general']['site_phone'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                    <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="USD" <?php echo ($settings['general']['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                        <option value="EUR" <?php echo ($settings['general']['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                        <option value="GBP" <?php echo ($settings['general']['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Site Address</label>
                                <textarea name="site_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($settings['general']['site_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                                <textarea name="site_description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($settings['general']['site_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-save mr-2"></i>
                                    Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Email Settings -->
                <div id="panel-email" class="settings-panel hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Email Configuration</h2>
                        </div>
                        <form method="POST" action="settings.php" class="p-6">
                            <input type="hidden" name="action" value="email">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['email']['smtp_host'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                                    <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['email']['smtp_port'] ?? 587); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Username</label>
                                    <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($settings['email']['smtp_username'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                                    <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($settings['email']['smtp_password'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                                    <select name="smtp_encryption" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="none" <?php echo ($settings['email']['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="tls" <?php echo ($settings['email']['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($settings['email']['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">From Email Address</label>
                                    <input type="email" name="email_from_address" value="<?php echo htmlspecialchars($settings['email']['email_from_address'] ?? ''); ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                                <input type="text" name="email_from_name" value="<?php echo htmlspecialchars($settings['email']['email_from_name'] ?? ''); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md mr-3">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Email Settings
                                </button>
                                <button type="button" onclick="testEmailSettings()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Test Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Payment Settings -->
                <div id="panel-payment" class="settings-panel hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Payment Gateway Settings</h2>
                        </div>
                        <form method="POST" action="settings.php" class="p-6">
                            <input type="hidden" name="action" value="payment">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Gateway</label>
                                <select name="payment_gateway" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <option value="stripe" <?php echo ($settings['payment']['payment_gateway'] ?? 'stripe') === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                    <option value="paypal" <?php echo ($settings['payment']['payment_gateway'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                    <option value="both" <?php echo ($settings['payment']['payment_gateway'] ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">Stripe Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Publishable Key</label>
                                        <input type="text" name="stripe_public_key" value="<?php echo htmlspecialchars($settings['payment']['stripe_public_key'] ?? ''); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                                        <input type="password" name="stripe_secret_key" value="<?php echo htmlspecialchars($settings['payment']['stripe_secret_key'] ?? ''); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-6 mt-6">
                                <h3 class="text-md font-medium text-gray-900 mb-4">PayPal Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                                        <input type="text" name="paypal_client_id" value="<?php echo htmlspecialchars($settings['payment']['paypal_client_id'] ?? ''); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                                        <input type="password" name="paypal_client_secret" value="<?php echo htmlspecialchars($settings['payment']['paypal_client_secret'] ?? ''); ?>" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="paypal_sandbox" value="1" <?php echo ($settings['payment']['paypal_sandbox'] ?? 0) ? 'checked' : ''; ?> 
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700">Use PayPal Sandbox (Test Mode)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Payment Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Backup Settings -->
                <div id="panel-backup" class="settings-panel hidden">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Database Backup</h2>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <p class="text-gray-600 mb-4">Create a backup of your database to protect against data loss. Backups are stored in the database/backups/ directory.</p>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-info-circle text-blue-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800">Backup Information</h3>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <p>• Backups include all tables and data</p>
                                                <p>• Recommended to backup weekly</p>
                                                <p>• Keep multiple backup versions</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="settings.php" class="inline">
                                <input type="hidden" name="action" value="backup">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-download mr-2"></i>
                                    Create Database Backup
                                </button>
                            </form>
                            
                            <!-- Recent Backups -->
                            <div class="mt-8">
                                <h3 class="text-md font-medium text-gray-900 mb-4">Recent Backups</h3>
                                <div id="backup-list" class="space-y-2">
                                    <!-- Backup files will be loaded here -->
                                    <p class="text-gray-500">No backups found. Create your first backup above.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.settings-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.settings-tabs button').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected panel
            document.getElementById('panel-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('active');
        }
        
        function testEmailSettings() {
            // Implementation for testing email settings
            alert('Email test functionality would be implemented here');
        }
        
        function loadBackups() {
            // Load backup files (this would be implemented with an API endpoint)
            console.log('Loading backup files...');
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadBackups();
        });
    </script>
</body>
</html>

<?php
// Helper functions for settings management
function validateGeneralSettings($data) {
    $errors = [];
    
    if (empty($data['site_name'])) {
        $errors['site_name'] = 'Site name is required';
    }
    
    if (empty($data['site_email'])) {
        $errors['site_email'] = 'Site email is required';
    } elseif (!filter_var($data['site_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['site_email'] = 'Invalid email format';
    }
    
    return $errors;
}

function validateEmailSettings($data) {
    $errors = [];
    
    if (empty($data['smtp_host'])) {
        $errors['smtp_host'] = 'SMTP host is required';
    }
    
    if (empty($data['smtp_username'])) {
        $errors['smtp_username'] = 'SMTP username is required';
    }
    
    if (empty($data['email_from_address'])) {
        $errors['email_from_address'] = 'From email address is required';
    } elseif (!filter_var($data['email_from_address'], FILTER_VALIDATE_EMAIL)) {
        $errors['email_from_address'] = 'Invalid email format';
    }
    
    return $errors;
}

function validatePaymentSettings($data) {
    $errors = [];
    
    if ($data['payment_gateway'] === 'stripe' || $data['payment_gateway'] === 'both') {
        if (empty($data['stripe_public_key'])) {
            $errors['stripe_public_key'] = 'Stripe public key is required';
        }
        if (empty($data['stripe_secret_key'])) {
            $errors['stripe_secret_key'] = 'Stripe secret key is required';
        }
    }
    
    if ($data['payment_gateway'] === 'paypal' || $data['payment_gateway'] === 'both') {
        if (empty($data['paypal_client_id'])) {
            $errors['paypal_client_id'] = 'PayPal client ID is required';
        }
        if (empty($data['paypal_client_secret'])) {
            $errors['paypal_client_secret'] = 'PayPal client secret is required';
        }
    }
    
    return $errors;
}

function saveGeneralSettings($data) {
    // Implementation would save to database or config file
    // For now, just return true
    return true;
}

function saveEmailSettings($data) {
    // Implementation would save to database or config file
    return true;
}

function savePaymentSettings($data) {
    // Implementation would save to database or config file
    return true;
}

function loadAllSettings() {
    // Implementation would load from database or config file
    return [
        'general' => [
            'site_name' => 'Marrak Rent Car',
            'site_email' => 'info@marrakrentcar.com',
            'site_phone' => '+1 234 567 8900',
            'site_address' => '123 Main St, City, State 12345',
            'site_description' => 'Professional car rental services',
            'currency' => 'USD',
            'timezone' => 'UTC'
        ],
        'email' => [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'email_from_name' => 'Marrak Rent Car',
            'email_from_address' => 'info@marrakrentcar.com'
        ],
        'payment' => [
            'payment_gateway' => 'stripe',
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'paypal_client_id' => '',
            'paypal_client_secret' => '',
            'paypal_sandbox' => 1
        ]
    ];
}

function createBackup() {
    // Implementation would create database backup
    return true;
}
?>