<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Rental.php';

$userModel = new User();
$rentalModel = new Rental();

$message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: rentals.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $rentalId = intval($_POST['rental_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        
        if ($rentalId > 0 && in_array($status, ['pending', 'confirmed', 'active', 'completed', 'cancelled'])) {
            $result = $rentalModel->updateRentalStatus($rentalId, $status, $notes);
            if ($result['success']) {
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_type'] = 'error';
            }
        }
        header('Location: rentals.php');
        exit;
    }
}

// Get rentals with filtering
$status = $_GET['status'] ?? '';
$dateFilter = $_GET['date_filter'] ?? '';
$search = $_GET['search'] ?? '';

// Build query parameters
$where = ["1=1"];
$params = [];

if ($status) {
    $where[] = "r.status = :status";
    $params[':status'] = $status;
}

if ($dateFilter) {
    switch ($dateFilter) {
        case 'today':
            $where[] = "DATE(r.created_at) = CURDATE()";
            break;
        case 'week':
            $where[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($search) {
    $where[] = "(u.name LIKE :search OR u.email LIKE :search OR c.make LIKE :search OR c.model LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql = "SELECT r.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                c.make, c.model, c.year, c.license_plate, c.image_url
        FROM rentals r 
        JOIN users u ON r.user_id = u.id 
        JOIN cars c ON r.car_id = c.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.created_at DESC";

$rentalModel->db->query($sql);
foreach ($params as $key => $value) {
    $rentalModel->db->bind($key, $value);
}
$rentals = $rentalModel->db->resultSet();

// Get statistics
$stats = $rentalModel->getStatistics();

$page_title = 'Manage Rentals';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold text-gray-900">Manage Rentals</h1>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-filter mr-1"></i>
                                <?php echo count($rentals); ?> rentals found
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-blue-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-calendar text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Rentals</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_rentals'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Completed</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['completed_rentals'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-yellow-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php 
                                    $pendingCount = 0;
                                    foreach ($rentals as $rental) {
                                        if ($rental['status'] === 'pending') $pendingCount++;
                                    }
                                    echo $pendingCount;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-red-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Cancelled</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['cancelled_rentals'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="form-input" onchange="filterRentals()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Filter</label>
                            <select id="dateFilter" class="form-input" onchange="filterRentals()">
                                <option value="">All Time</option>
                                <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $dateFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $dateFilter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" class="form-input" placeholder="Customer, car..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="filterRentals()">
                        </div>
                        <div class="flex items-end">
                            <button onclick="clearFilters()" class="btn btn-secondary w-full">
                                <i class="fas fa-times mr-2"></i>
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Flash Messages -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> mb-6">
                        <?php 
                        echo htmlspecialchars($_SESSION['flash_message']);
                        unset($_SESSION['flash_message']);
                        unset($_SESSION['flash_type']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Rentals Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rental ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Car</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($rentals as $rental): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-mono text-gray-900">#<?php echo str_pad($rental['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['user_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($rental['user_email']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($rental['user_phone']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $rental['year']; ?> • <?php echo htmlspecialchars($rental['license_plate']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></div>
                                            <div class="text-sm text-gray-500">to <?php echo date('M j, Y', strtotime($rental['end_date'])); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($rental['start_date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">$<?php echo number_format($rental['total_cost'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'confirmed' => 'bg-blue-100 text-blue-800',
                                                'active' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-gray-100 text-gray-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $color = $statusColors[$rental['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                                <?php echo ucfirst($rental['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewRentalDetails(<?php echo $rental['id']; ?>)" class="text-primary-600 hover:text-primary-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="updateRentalStatus(<?php echo $rental['id']; ?>, '<?php echo $rental['status']; ?>')" class="text-gray-600 hover:text-gray-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="printRental(<?php echo $rental['id']; ?>)" class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (empty($rentals)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600">No rentals found matching your criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Update Rental Status</h3>
                <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="statusForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="rental_id" id="statusRentalId">
                
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" id="statusSelect" class="form-input" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" id="statusNotes" rows="3" class="form-input" placeholder="Add any notes about this status change..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const rentals = <?php echo json_encode(array_column($rentals, null, 'id')); ?>;
        
        function filterRentals() {
            const status = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const search = document.getElementById('searchInput').value;
            
            const params = new URLSearchParams();
            if (status) params.set('status', status);
            if (dateFilter) params.set('date_filter', dateFilter);
            if (search) params.set('search', search);
            
            window.location.href = 'rentals.php?' + params.toString();
        }
        
        function clearFilters() {
            window.location.href = 'rentals.php';
        }
        
        function viewRentalDetails(rentalId) {
            const rental = rentals[rentalId];
            if (!rental) return;
            
            // Create a simple details view (could be enhanced with a modal)
            const details = `
                Rental ID: #${String(rental.id).padStart(6, '0')}
                Customer: ${rental.user_name}
                Car: ${rental.make} ${rental.model}
                Dates: ${new Date(rental.start_date).toLocaleDateString()} - ${new Date(rental.end_date).toLocaleDateString()}
                Amount: $${parseFloat(rental.total_cost).toFixed(2)}
                Status: ${rental.status}
            `;
            
            alert(details);
        }
        
        function updateRentalStatus(rentalId, currentStatus) {
            document.getElementById('statusRentalId').value = rentalId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusForm').reset();
        }
        
        function printRental(rentalId) {
            window.open(`print-rental.php?id=${rentalId}`, '_blank', 'width=800,height=600');
        }
        
        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });
    </script>
</body>
</html>