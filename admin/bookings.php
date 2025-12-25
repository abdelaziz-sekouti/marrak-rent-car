<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/Car.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Rental.php';

$carModel = new Car();
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
        header('Location: bookings.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_booking') {
        $data = [
            'user_id' => intval($_POST['user_id'] ?? 0),
            'car_id' => intval($_POST['car_id'] ?? 0),
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'pickup_location' => trim($_POST['pickup_location'] ?? ''),
            'dropoff_location' => trim($_POST['dropoff_location'] ?? ''),
            'total_cost' => floatval($_POST['total_cost'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        $errors = validateBookingData($data);
        
        if (empty($errors)) {
            if (createBooking($data)) {
                $_SESSION['flash_message'] = 'Booking created successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: bookings.php');
                exit;
            } else {
                $message = 'Failed to create booking';
            }
        }
    } elseif ($action === 'update_status') {
        $bookingId = intval($_POST['booking_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($rentalModel->updateBookingStatus($bookingId, $status)) {
            $_SESSION['flash_message'] = 'Booking status updated successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to update booking status';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: bookings.php');
        exit;
    } elseif ($action === 'delete') {
        $bookingId = intval($_POST['booking_id'] ?? 0);
        if ($rentalModel->deleteBooking($bookingId)) {
            $_SESSION['flash_message'] = 'Booking deleted successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to delete booking';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: bookings.php');
        exit;
    }
}

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$car_id = intval($_GET['car_id'] ?? 0);
$user_id = intval($_GET['user_id'] ?? 0);
$start_date_filter = $_GET['start_date_filter'] ?? '';
$end_date_filter = $_GET['end_date_filter'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Get bookings with filters
$bookings = $rentalModel->getBookingsWithFilters($search, $status, $car_id, $user_id, $start_date_filter, $end_date_filter, $limit, $offset);
$totalBookings = $rentalModel->countBookingsWithFilters($search, $status, $car_id, $user_id, $start_date_filter, $end_date_filter);
$totalPages = ceil($totalBookings / $limit);

// Get booking statistics
$stats = $rentalModel->getBookingStatistics();

// Get data for dropdowns
$users = $userModel->db->executeQuery("SELECT id, name, email FROM users ORDER BY name")->fetchAll();
$cars = $carModel->db->executeQuery("SELECT id, make, model, license_plate, status FROM cars ORDER BY make, model")->fetchAll();

$page_title = 'Booking Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Booking management for Marrak Rent Car">
    
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
                            <h1 class="text-2xl font-bold text-gray-900">Booking Management</h1>
                            <p class="text-sm text-gray-600">Manage car rental bookings and reservations</p>
                        </div>
                        <button onclick="openAddBookingModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            New Booking
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Bookings Content -->
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
                                <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_bookings']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-bookmark"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['pending_bookings']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Confirmed</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['confirmed_bookings']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Today's Bookings</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['today_bookings']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Booking ID, customer..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Car</label>
                            <select name="car_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">All Cars</option>
                                <?php foreach ($cars as $car): ?>
                                    <option value="<?php echo $car['id']; ?>" <?php echo $car_id === $car['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' (' . $car['license_plate'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                            <input type="date" name="start_date_filter" value="<?php echo htmlspecialchars($start_date_filter); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-search mr-2"></i>
                                Filter
                            </button>
                            <a href="bookings.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md">
                                <i class="fas fa-redo mr-2"></i>
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Bookings Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($bookings as $booking): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-gray-900">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['car_make'] . ' ' . $booking['car_model']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['car_license_plate']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></div>
                                            <div class="text-sm text-gray-500">to <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">$<?php echo number_format($booking['total_cost'], 2); ?></div>
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
                                            $color = $statusColors[$booking['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewBooking(<?php echo $booking['id']; ?>)" class="text-primary-600 hover:text-primary-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editBooking(<?php echo $booking['id']; ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                            <button onclick="deleteBooking(<?php echo $booking['id']; ?>)" class="text-red-600 hover:text-red-900">
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
                                    <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&car_id=<?php echo $car_id; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                    <a href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&car_id=<?php echo $car_id; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to 
                                            <span class="font-medium"><?php echo min($offset + $limit, $totalBookings); ?></span> of 
                                            <span class="font-medium"><?php echo $totalBookings; ?></span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&car_id=<?php echo $car_id; ?>" 
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
    
    <!-- Add Booking Modal -->
    <div id="addBookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Booking</h3>
                <form method="POST" action="bookings.php">
                    <input type="hidden" name="action" value="add_booking">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                            <select name="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select Customer</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Car *</label>
                            <select name="car_id" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Select Car</option>
                                <?php foreach ($cars as $car): ?>
                                    <option value="<?php echo $car['id']; ?>" data-rate="<?php echo $car['daily_rate']; ?>" 
                                            <?php echo $car['status'] !== 'available' ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' - $' . number_format($car['daily_rate'], 2) . '/day'); ?>
                                        <?php echo $car['status'] !== 'available' ? ' (Unavailable)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                            <input type="datetime-local" name="start_date" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                            <input type="datetime-local" name="end_date" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
                            <input type="text" name="pickup_location" placeholder="Enter pickup location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Drop-off Location</label>
                            <input type="text" name="dropoff_location" placeholder="Enter drop-off location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost</label>
                            <input type="number" name="total_cost" id="total_cost" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500" readonly>
                            <p class="text-sm text-gray-500">Calculated automatically based on duration and car rate</p>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                            <div id="duration" class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">Select dates to calculate</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3" placeholder="Additional booking notes..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAddBookingModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">Create Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Booking Status</h3>
                <form method="POST" action="bookings.php" id="statusForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" id="statusBookingId">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                        <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openAddBookingModal() {
            document.getElementById('addBookingModal').classList.remove('hidden');
        }
        
        function closeAddBookingModal() {
            document.getElementById('addBookingModal').classList.add('hidden');
            document.querySelector('#addBookingModal form').reset();
        }
        
        function viewBooking(bookingId) {
            window.location.href = `booking-details.php?id=${bookingId}`;
        }
        
        function editBooking(bookingId) {
            window.location.href = `edit-booking.php?id=${bookingId}`;
        }
        
        function updateBookingStatus(bookingId) {
            document.getElementById('statusBookingId').value = bookingId;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
        
        function deleteBooking(bookingId) {
            if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bookings.php';
                
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                `;
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function calculateTotal() {
            const carSelect = document.querySelector('select[name="car_id"]');
            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const dailyRate = parseFloat(selectedOption?.dataset?.rate || 0);
            
            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            const endDate = new Date(document.querySelector('input[name="end_date"]').value);
            
            if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime()) && dailyRate > 0) {
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                const total = days * dailyRate;
                
                document.getElementById('total_cost').value = total.toFixed(2);
                document.getElementById('duration').textContent = `${days} day(s) @ $${dailyRate.toFixed(2)}/day`;
            } else {
                document.getElementById('total_cost').value = '';
                document.getElementById('duration').textContent = 'Select dates to calculate';
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addBookingModal');
            const statusModal = document.getElementById('statusModal');
            
            if (event.target === addModal) {
                closeAddBookingModal();
            } else if (event.target === statusModal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>

<?php
// Helper functions for booking management
function validateBookingData($data) {
    $errors = [];
    
    if (empty($data['user_id'])) {
        $errors['user_id'] = 'Customer is required';
    }
    
    if (empty($data['car_id'])) {
        $errors['car_id'] = 'Car is required';
    }
    
    if (empty($data['start_date'])) {
        $errors['start_date'] = 'Start date is required';
    }
    
    if (empty($data['end_date'])) {
        $errors['end_date'] = 'End date is required';
    }
    
    if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
        $errors['end_date'] = 'End date must be after start date';
    }
    
    if ($data['total_cost'] <= 0) {
        $errors['total_cost'] = 'Total cost must be greater than 0';
    }
    
    return $errors;
}

function createBooking($data) {
    // Implementation would insert into rentals table
    // For now, just return true
    return true;
}
?>