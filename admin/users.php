<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/User.php';

$userModel = new User();
$message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: users.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => $_POST['role'] ?? 'customer'
        ];
        
        $errors = $userModel->validateUserData($data);
        
        if (empty($errors)) {
            $result = $userModel->register($data['name'], $data['email'], $data['password'], $data['phone'], $data['role']);
            if ($result['success']) {
                $_SESSION['flash_message'] = 'User added successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: users.php');
                exit;
            } else {
                $message = $result['message'];
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => $_POST['role'] ?? 'customer'
        ];
        
        $errors = $userModel->validateUserData($data, $id);
        
        if (empty($errors)) {
            if ($userModel->updateUser($id, $data)) {
                $_SESSION['flash_message'] = 'User updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: users.php');
                exit;
            } else {
                $message = 'Failed to update user';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($userModel->deleteUser($id)) {
            $_SESSION['flash_message'] = 'User deleted successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: users.php');
            exit;
        } else {
            $message = 'Failed to delete user';
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($userModel->toggleUserStatus($id)) {
            $_SESSION['flash_message'] = 'User status updated successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: users.php');
            exit;
        } else {
            $message = 'Failed to update user status';
        }
    }
}

// Get search parameters
$search = trim($_GET['search'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get users
$users = $userModel->getUsersWithFilters($search, $role, $status, $limit, $offset);
$totalUsers = $userModel->countUsersWithFilters($search, $role, $status);
$totalPages = ceil($totalUsers / $limit);

// Get user statistics
$stats = $userModel->getUserStatistics();

$page_title = 'User Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="User management for Marrak Rent Car">
    
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
            @apply w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold;
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
                            <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                            <p class="text-sm text-gray-600">Manage user accounts and permissions</p>
                        </div>
                        <button onclick="openAddUserModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Add User
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- User Management Content -->
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
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Customers</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['customers']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Staff</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['staff']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Today</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['active_today']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Name, email..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Roles</option>
                                <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md mr-2">
                                <i class="fas fa-search mr-2"></i>
                                Search
                            </button>
                            <a href="users.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                                <i class="fas fa-redo mr-2"></i>
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : ($user['role'] === 'staff' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editUser(<?php echo $user['id']; ?>)" class="text-primary-600 hover:text-primary-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="toggleUserStatus(<?php echo $user['id']; ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                                            <span class="font-medium"><?php echo min($offset + $limit, $totalUsers); ?></span> of 
                                            <span class="font-medium"><?php echo $totalUsers; ?></span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New User</h3>
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            <option value="customer">Customer</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
        }
        
        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
        }
        
        function editUser(userId) {
            // Load user data and open edit modal
            window.location.href = `edit-user.php?id=${userId}`;
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'users.php';
                
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${userId}">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleUserStatus(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'users.php';
            
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="${userId}">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            `;
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target === modal) {
                closeAddUserModal();
            }
        }
    </script>
</body>
</html>