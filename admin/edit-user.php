<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/User.php';

$userModel = new User();

// Get user ID from URL
$userId = intval($_GET['id'] ?? 0);
$message = '';
$errors = [];

if ($userId === 0) {
    $_SESSION['flash_message'] = 'Invalid user ID';
    $_SESSION['flash_type'] = 'error';
    header('Location: users.php');
    exit;
}

// Get user details
$user = $userModel->getUserById($userId);
if (!$user) {
    $_SESSION['flash_message'] = 'User not found';
    $_SESSION['flash_type'] = 'error';
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: edit-user.php?id=' . $userId);
        exit;
    }
    
    $action = $_POST['action'] ?? 'update';
    
    if ($action === 'update') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => $_POST['role'] ?? 'customer',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Handle password change if provided
        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        $errors = $userModel->validateUserData($data, $userId);
        
        if (empty($errors)) {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => $data['role'],
                'status' => $data['status']
            ];
            
            // Include password if provided
            if (!empty($password)) {
                $updateData['password'] = $password;
            }
            
            if ($userModel->updateUser($userId, $updateData)) {
                $_SESSION['flash_message'] = 'User updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: users.php');
                exit;
            } else {
                $message = 'Failed to update user. Check error logs for details.';
                error_log("User update failed for user ID: $userId");
            }
        } else {
            // Display validation errors
            $message = 'Please fix the errors below';
            error_log("Validation errors for user ID $userId: " . json_encode($errors));
        }
    } elseif ($action === 'delete') {
        if ($userModel->deleteUser($userId)) {
            $_SESSION['flash_message'] = 'User deleted successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: users.php');
            exit;
        } else {
            $message = 'Failed to delete user';
        }
    } elseif ($action === 'toggle_status') {
        if ($userModel->toggleUserStatus($userId)) {
            $_SESSION['flash_message'] = 'User status updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: users.php');
            exit;
        } else {
            $message = 'Failed to update user status';
        }
    }
}

// Get user statistics
$userStats = $userModel->getUserStatistics();

// Get user rentals (basic query since method doesn't exist)
$userRentals = [
    'total' => 0,
    'total_spent' => 0,
    'recent' => []
];

try {
    $userModel->db->query("SELECT COUNT(*) as count FROM rentals WHERE user_id = :user_id");
    $userModel->db->bind(':user_id', $userId);
    $result = $userModel->db->single();
    $userRentals['total'] = $result['count'] ?? 0;
    
    // Get total spent
    $userModel->db->query("SELECT COALESCE(SUM(total_cost), 0) as total FROM rentals WHERE user_id = :user_id AND status != 'cancelled'");
    $userModel->db->bind(':user_id', $userId);
    $result = $userModel->db->single();
    $userRentals['total_spent'] = $result['total'] ?? 0;
    
    // Get recent rentals
    $userModel->db->query("SELECT r.*, c.make, c.model, c.license_plate 
                          FROM rentals r 
                          JOIN cars c ON r.car_id = c.id 
                          WHERE r.user_id = :user_id 
                          ORDER BY r.created_at DESC 
                          LIMIT 5");
    $userModel->db->bind(':user_id', $userId);
    $userRentals['recent'] = $userModel->db->resultSet();
} catch (Exception $e) {
    error_log("Error getting user rentals: " . $e->getMessage());
}

$page_title = 'Edit User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo htmlspecialchars($user['name']); ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Edit user for Marrak Rent Car">
    
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
        .user-avatar {
            @apply w-16 h-16 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold text-2xl;
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
            
            <?php require_once __DIR__ . '/aside.php'; ?>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
                            <p class="text-sm text-gray-600">Modify user information and permissions</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="users.php" class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Users
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Edit User Content -->
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
                
                <!-- User Profile Header -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-center space-x-6">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                            <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : ($user['role'] === 'staff' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Form -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <form method="POST" action="edit-user.php?id=<?php echo $userId; ?>" class="p-6" id="editUserForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <?php if (isset($errors['name'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['name']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <?php if (isset($errors['email'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['email']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 (555) 123-4567" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <?php if (isset($errors['phone'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">User Role *</label>
                                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Status *</label>
                                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" name="password" placeholder="Leave blank to keep current password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <p class="text-sm text-gray-500 mt-1">Enter new password only if you want to change it</p>
                                    <?php if (isset($errors['password'])): ?>
                                        <p class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['password']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                            <div class="space-x-3">
                                <?php if ($user['status'] === 'active'): ?>
                                    <form method="POST" action="edit-user.php?id=<?php echo $userId; ?>" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to deactivate this user?')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md">
                                            <i class="fas fa-ban mr-2"></i>
                                            Deactivate
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="edit-user.php?id=<?php echo $userId; ?>" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                            <i class="fas fa-check mr-2"></i>
                                            Activate
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" action="edit-user.php?id=<?php echo $userId; ?>" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                                            <i class="fas fa-trash mr-2"></i>
                                            Delete User
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="space-x-3">
                                <a href="users.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                                <button type="submit" form="editUserForm" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- User Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Rentals</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($userRentals['total'] ?? 0); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-car"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Spent</p>
                                <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($userRentals['total_spent'] ?? 0, 2); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Last Activity</p>
                                <p class="text-lg font-bold text-gray-900">
                                    <?php 
                                    $lastActivity = $user['updated_at'] ?? $user['created_at'];
                                    if ($lastActivity) {
                                        $timeDiff = time() - strtotime($lastActivity);
                                        if ($timeDiff < 3600) {
                                            echo floor($timeDiff / 60) . ' min ago';
                                        } elseif ($timeDiff < 86400) {
                                            echo floor($timeDiff / 3600) . ' hours ago';
                                        } else {
                                            echo date('M j', strtotime($lastActivity));
                                        }
                                    } else {
                                        echo 'No activity';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Rentals -->
                <?php if (!empty($userRentals['recent'])): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Rentals</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($userRentals['recent'] as $rental): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($rental['license_plate']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($rental['start_date'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    to <?php echo date('M j, Y', strtotime($rental['end_date'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                $<?php echo number_format($rental['total_cost'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                                        'active' => 'bg-green-100 text-green-800',
                                                        'completed' => 'bg-gray-100 text-gray-800',
                                                        'cancelled' => 'bg-red-100 text-red-800'
                                                    ];
                                                    echo $statusColors[$rental['status']] ?? 'bg-gray-100 text-gray-800';
                                                    ?>">
                                                    <?php echo ucfirst($rental['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    

</body>
</html>