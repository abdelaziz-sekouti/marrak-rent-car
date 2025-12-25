<?php
require_once __DIR__ . '/../includes/init.php';

// Require login for profile
requireLogin();

require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Rental.php';

$userModel = new User();
$rentalModel = new Rental();

$message = '';
$errors = [];

// Get current user
$currentUser = $userModel->getUserById($_SESSION['user_id']);
if (!$currentUser) {
    $_SESSION['flash_message'] = 'User not found';
    $_SESSION['flash_type'] = 'error';
    header('Location: index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: profile.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        $errors = validateProfileData($data, $currentUser['id']);
        
        if (empty($errors)) {
            if ($userModel->updateProfile($currentUser['id'], $data['name'], $data['email'], $data['phone'])) {
                $_SESSION['flash_message'] = 'Profile updated successfully!';
                $_SESSION['flash_type'] = 'success';
                // Update current user data
                $currentUser = $userModel->getUserById($_SESSION['user_id']);
                header('Location: profile.php');
                exit;
            } else {
                $message = 'Failed to update profile';
            }
        } else {
            $message = 'Please correct the errors below';
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (password_change($currentUser['id'], $currentPassword, $newPassword, $confirmPassword)) {
            $_SESSION['flash_message'] = 'Password changed successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: profile.php');
            exit;
        } else {
            $message = 'Failed to change password';
        }
    }
}

// Get user's recent rentals
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$rentals = $rentalModel->getUserRentals($currentUser['id'], 'completed');
$totalRentals = count($rentals);

$page_title = 'My Profile - Marrak Rent Car';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="User profile for Marrak Rent Car">
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-grow min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> mb-8 animate-fade-in">
                    <?php 
                    echo htmlspecialchars($_SESSION['flash_message']);
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- User Information Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">My Profile</h2>
                    
                    <!-- Profile Update Form -->
                    <div class="mb-6">
                        <form method="POST" action="profile.php" id="profileForm" class="space-y-4">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg">
                                    <i class="fas fa-save mr-2"></i>
                                    Update Profile
                                </button>
                            </div>
                            
                            <?php if (!empty($message)): ?>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                        <p class="text-red-800"><?php echo htmlspecialchars($message); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <!-- Account Stats -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-car text-primary-600 text-2xl"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">0</div>
                                <div class="text-sm text-gray-600">Total Rentals</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo $totalRentals; ?></div>
                                <div class="text-sm text-gray-600">Completed Rentals</div>
                            </div>
                            
                            <div class="text-center">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-calendar text-blue-600 text-2xl"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900"><?php echo date('M j, Y', strtotime($currentUser['created_at'] ?? '')); ?></div>
                                <div class="text-sm text-gray-600">Member Since</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Rentals Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Recent Rentals</h2>
                    
                    <?php if (!empty($rentals)): ?>
                        <div class="space-y-4">
                            <?php foreach ($rentals as $rental): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></h3>
                                            <p class="text-sm text-gray-600">
                                                <?php echo date('M j, Y', strtotime($rental['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($rental['end_date'])); ?>
                                            </p>
                                        </div>
                                        
                                        <div class="text-right">
                                            <span class="text-2xl font-bold text-primary-600">$<?php echo number_format($rental['total_cost'], 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div>
                                            <a href="booking-details.php?id=<?php echo $rental['id']; ?>" 
                                               class="text-primary-600 hover:text-primary-900 font-medium text-sm">
                                                View Details
                                                <i class="fas fa-arrow-right ml-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalRentals > $limit): ?>
                            <div class="flex justify-center mt-6">
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php
                                    $totalPages = ceil($totalRentals / $limit);
                                    for ($i = 1; $i <= $totalPages; $i++):
                                        $url = "profile.php?page={$i}";
                                        $active = $i === $page;
                                        ?>
                                        <a href="<?php echo $url; ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md <?php echo $active ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Rentals Yet</h3>
                            <p class="text-gray-500 mb-4">You haven't rented any cars yet. Browse our fleet and book your first rental!</p>
                            <a href="cars.php" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg inline-flex items-center">
                                <i class="fas fa-car mr-2"></i>
                                Browse Cars
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Password Change Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Change Password</h2>
                    
                    <form method="POST" action="profile.php" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" required 
                                       minlength="8"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" required 
                                       minlength="8"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg">
                                <i class="fas fa-key mr-2"></i>
                                Change Password
                            </button>
                        </div>
                        
                        <?php if (!empty($message)): ?>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-4">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                    <p class="text-red-800"><?php echo htmlspecialchars($message); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        // Handle password confirmation
        document.getElementById('profileForm')?.addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            
            if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

<?php
// Helper functions
function validateProfileData($data, $userId) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($data['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['phone'])) {
        $errors['phone'] = 'Invalid phone number format';
    }
    
    return $errors;
}

function password_change($userId, $currentPassword, $newPassword, $confirmPassword) {
    global $userModel;
    
    // Validate current password
    $user = $userModel->getUserById($userId);
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return false;
    }
    
    // Validate new password
    if (strlen($newPassword) < 8) {
        return false;
    }
    
    if ($newPassword !== $confirmPassword) {
        return false;
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    global $db;
    $db->query("UPDATE users SET password = :password WHERE id = :id");
    $db->bind(':password', $hashedPassword);
    $db->bind(':id', $userId);
    
    return $db->execute();
}
?>