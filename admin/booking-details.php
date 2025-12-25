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

// Get booking ID from URL
$bookingId = intval($_GET['id'] ?? 0);

if ($bookingId === 0) {
    $_SESSION['flash_message'] = 'Invalid booking ID';
    $_SESSION['flash_type'] = 'error';
    header('Location: bookings.php');
    exit;
}

// Get booking details
$booking = $rentalModel->getRentalById($bookingId);
if (!$booking) {
    $_SESSION['flash_message'] = 'Booking not found';
    $_SESSION['flash_type'] = 'error';
    header('Location: bookings.php');
    exit;
}

// Get additional related data
$customer = $userModel->getUserById($booking['user_id']);
$car = $carModel->getCarById($booking['car_id']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: booking-details.php?id=' . $bookingId);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        $result = $rentalModel->updateBookingStatus($bookingId, $newStatus, $notes);
        
        if ($result['success']) {
            $_SESSION['flash_message'] = 'Booking status updated successfully!';
            $_SESSION['flash_type'] = 'success';
            // Refresh booking data
            $booking = $rentalModel->getRentalById($bookingId);
        } else {
            $_SESSION['flash_message'] = $result['message'];
            $_SESSION['flash_type'] = 'error';
        }
    } elseif ($action === 'add_payment') {
        $paymentData = [
            'amount' => floatval($_POST['payment_amount'] ?? 0),
            'method' => trim($_POST['payment_method'] ?? ''),
            'reference' => trim($_POST['payment_reference'] ?? ''),
            'notes' => trim($_POST['payment_notes'] ?? '')
        ];
        
        if (addPayment($bookingId, $paymentData)) {
            $_SESSION['flash_message'] = 'Payment added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to add payment';
            $_SESSION['flash_type'] = 'error';
        }
    }
}

$page_title = 'Booking Details';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Booking details for Marrak Rent Car">
    
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
        .status-badge {
            @apply px-3 py-1 rounded-full text-sm font-semibold;
        }
        .info-card {
            @apply bg-white rounded-lg border border-gray-200 p-6;
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
                            <h1 class="text-2xl font-bold text-gray-900">Booking Details #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                            <p class="text-sm text-gray-600">Complete booking information and management</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Booking
                            </a>
                            <a href="bookings.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Bookings
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Booking Details Content -->
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
                
                <!-- Status Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                    <div class="info-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Booking ID</p>
                                <p class="text-2xl font-bold text-gray-900">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-hashtag"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Status</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo ucfirst($booking['status']); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg <?php echo getStatusColorClass($booking['status']); ?> flex items-center justify-center">
                                <i class="fas <?php echo getStatusIcon($booking['status']); ?>"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Cost</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($booking['total_cost'], 2); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Duration</p>
                                <?php
                                $start = new DateTime($booking['start_date']);
                                $end = new DateTime($booking['end_date']);
                                $days = $end->diff($start)->days + 1;
                                ?>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $days; ?> Days</p>
                            </div>
                            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer and Car Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Customer Information -->
                    <div class="info-card">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold mr-4">
                                    <?php echo strtoupper(substr($customer['name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($customer['name'] ?? 'N/A'); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Customer ID</p>
                                <p class="text-sm font-medium text-gray-900">#<?php echo str_pad($customer['id'] ?? 0, 5, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Role</p>
                                <p class="text-sm font-medium text-gray-900"><?php echo ucfirst($customer['role'] ?? 'customer'); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Member Since</p>
                                <p class="text-sm font-medium text-gray-900"><?php echo date('M j, Y', strtotime($customer['created_at'] ?? '')); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Car Information -->
                    <div class="info-card">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Vehicle Information</h2>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-car text-2xl text-gray-400"></i>
                                </div>
                                <div>
                                    <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $car['year']; ?> • <?php echo htmlspecialchars($car['license_plate']); ?></p>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Category</p>
                                <p class="text-sm font-medium text-gray-900"><?php echo ucfirst($car['category']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Daily Rate</p>
                                <p class="text-sm font-medium text-gray-900">$<?php echo number_format($car['daily_rate'], 2); ?>/day</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Current Status</p>
                                <span class="status-badge <?php echo getCarStatusColorClass($car['status']); ?>">
                                    <?php echo ucfirst($car['status']); ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Mileage</p>
                                <p class="text-sm font-medium text-gray-900"><?php echo number_format($car['mileage'] ?? 0); ?> miles</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Timeline -->
                <div class="info-card mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Booking Timeline</h2>
                    <div class="relative">
                        <!-- Timeline Line -->
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300"></div>
                        
                        <!-- Timeline Items -->
                        <div class="space-y-6">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center z-10">
                                    <i class="fas fa-calendar-plus text-white text-xs"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">Booking Created</p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center z-10">
                                    <i class="fas fa-car text-white text-xs"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">Rental Period</p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($booking['start_date'])); ?> to <?php echo date('M j, Y g:i A', strtotime($booking['end_date'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($booking['pickup_location'])): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center z-10">
                                    <i class="fas fa-map-marker-alt text-white text-xs"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">Pickup Location</p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($booking['dropoff_location'])): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center z-10">
                                    <i class="fas fa-flag-checkered text-white text-xs"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">Drop-off Location</p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['dropoff_location']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Actions Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Update Status -->
                            <div>
                                <h3 class="text-md font-medium text-gray-900 mb-3">Update Status</h3>
                                <form method="POST" action="booking-details.php?id=<?php echo $booking['id']; ?>" class="space-y-3">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="active" <?php echo $booking['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Notes</label>
                                        <textarea name="notes" rows="3" placeholder="Add notes about this status change..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md">
                                        <i class="fas fa-sync mr-2"></i>
                                        Update Status
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Add Payment -->
                            <div>
                                <h3 class="text-md font-medium text-gray-900 mb-3">Record Payment</h3>
                                <form method="POST" action="booking-details.php?id=<?php echo $booking['id']; ?>" class="space-y-3">
                                    <input type="hidden" name="action" value="add_payment">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                                        <input type="number" name="payment_amount" step="0.01" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                            <option value="cash">Cash</option>
                                            <option value="card">Credit Card</option>
                                            <option value="bank">Bank Transfer</option>
                                            <option value="check">Check</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                        <input type="text" name="payment_reference" placeholder="Transaction reference..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Notes</label>
                                        <textarea name="payment_notes" rows="2" placeholder="Additional payment notes..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                        <i class="fas fa-money-bill mr-2"></i>
                                        Record Payment
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['notes'])): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-md font-medium text-gray-900 mb-3">Current Notes</h3>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <p class="text-sm text-gray-700"><?php echo htmlspecialchars($booking['notes']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php
// Helper functions
function getStatusColorClass($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-600',
        'confirmed' => 'bg-blue-100 text-blue-600',
        'active' => 'bg-green-100 text-green-600',
        'completed' => 'bg-gray-100 text-gray-600',
        'cancelled' => 'bg-red-100 text-red-600'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-600';
}

function getStatusIcon($status) {
    $icons = [
        'pending' => 'fa-clock',
        'confirmed' => 'fa-check-circle',
        'active' => 'fa-play-circle',
        'completed' => 'fa-check-double',
        'cancelled' => 'fa-times-circle'
    ];
    return $icons[$status] ?? 'fa-question-circle';
}

function getCarStatusColorClass($status) {
    $colors = [
        'available' => 'bg-green-100 text-green-800',
        'rented' => 'bg-red-100 text-red-800',
        'maintenance' => 'bg-yellow-100 text-yellow-800',
        'unavailable' => 'bg-gray-100 text-gray-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}

function addPayment($bookingId, $paymentData) {
    // Implementation would insert into payments table
    // For now, just return true
    return true;
}
?>