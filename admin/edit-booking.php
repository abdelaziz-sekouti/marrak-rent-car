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
$message = '';
$errors = [];

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: edit-booking.php?id=' . $bookingId);
        exit;
    }
    
    $action = $_POST['action'] ?? 'update';
    
    if ($action === 'update') {
        $data = [
            'user_id' => intval($_POST['user_id'] ?? 0),
            'car_id' => intval($_POST['car_id'] ?? 0),
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'pickup_location' => trim($_POST['pickup_location'] ?? ''),
            'dropoff_location' => trim($_POST['dropoff_location'] ?? ''),
            'total_cost' => floatval($_POST['total_cost'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'status' => $_POST['status'] ?? 'pending'
        ];
        
        $errors = $rentalModel->validateRentalData($data);
        
        if (empty($errors)) {
            // Update booking
            $updateData = [
                'user_id' => $data['user_id'],
                'car_id' => $data['car_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'pickup_location' => $data['pickup_location'],
                'dropoff_location' => $data['dropoff_location'],
                'total_cost' => $data['total_cost'],
                'notes' => $data['notes']
            ];
            
            if (updateBooking($bookingId, $updateData, $data['status'])) {
                $_SESSION['flash_message'] = 'Booking updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: bookings.php');
                exit;
            } else {
                $message = 'Failed to update booking';
            }
        }
    } elseif ($action === 'cancel') {
        if ($rentalModel->cancelRental($bookingId)) {
            $_SESSION['flash_message'] = 'Booking cancelled successfully!';
            $_SESSION['flash_type'] = 'success';
            header('Location: bookings.php');
            exit;
        } else {
            $message = 'Failed to cancel booking';
        }
    }
}

// Get data for dropdowns
$users = $userModel->db->executeQuery("SELECT id, name, email FROM users ORDER BY name")->fetchAll();
$cars = $carModel->db->executeQuery("SELECT id, make, model, license_plate, status, daily_rate FROM cars ORDER BY make, model")->fetchAll();

$page_title = 'Edit Booking';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Edit booking for Marrak Rent Car">
    
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
                            <h1 class="text-2xl font-bold text-gray-900">Edit Booking #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                            <p class="text-sm text-gray-600">Modify booking details and information</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="text-primary-600 hover:text-primary-900">
                                <i class="fas fa-eye mr-2"></i>
                                View Details
                            </a>
                            <a href="bookings.php" class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Bookings
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Edit Booking Form -->
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
                
                <!-- Current Status Alert -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-yellow-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Current Status: <?php echo ucfirst($booking['status']); ?></h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <?php
                                $statusInfo = [
                                    'pending' => 'Awaiting confirmation from customer or admin',
                                    'confirmed' => 'Booking confirmed, awaiting pickup',
                                    'active' => 'Vehicle currently rented',
                                    'completed' => 'Rental completed successfully',
                                    'cancelled' => 'Booking has been cancelled'
                                ];
                                echo $statusInfo[$booking['status']] ?? 'Unknown status';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Form -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <form method="POST" action="edit-booking.php?id=<?php echo $bookingId; ?>" class="p-6">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer *</label>
                                    <select name="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Select Customer</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $booking['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Car *</label>
                                    <select name="car_id" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="">Select Car</option>
                                        <?php foreach ($cars as $car): ?>
                                            <option value="<?php echo $car['id']; ?>" data-rate="<?php echo $car['daily_rate']; ?>" 
                                                    <?php echo $car['id'] == $booking['car_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' - $' . number_format($car['daily_rate'], 2) . '/day'); ?>
                                                <?php echo $car['status'] !== 'available' ? ' (Currently: ' . ucfirst($car['status']) . ')' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="active" <?php echo $booking['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                                    <input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['start_date'])); ?>" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
                                    <input type="datetime-local" name="end_date" value="<?php echo date('Y-m-d\TH:i', strtotime($booking['end_date'])); ?>" required onchange="calculateTotal()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost *</label>
                                    <input type="number" name="total_cost" id="total_cost" step="0.01" value="<?php echo $booking['total_cost']; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                    <p class="text-sm text-gray-500 mt-1">Calculated automatically based on duration and car rate</p>
                                </div>
                                
                                <div class="mb-4">
                                    <div id="duration" class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                                        <?php 
                                        $start = new DateTime($booking['start_date']);
                                        $end = new DateTime($booking['end_date']);
                                        $days = $end->diff($start)->days + 1;
                                        echo "$days day(s)";
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bottom Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Location</label>
                                <input type="text" name="pickup_location" value="<?php echo htmlspecialchars($booking['pickup_location']); ?>" placeholder="Enter pickup location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Drop-off Location</label>
                                <input type="text" name="dropoff_location" value="<?php echo htmlspecialchars($booking['dropoff_location']); ?>" placeholder="Enter drop-off location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea name="notes" rows="4" placeholder="Additional booking notes..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center">
                            <div>
                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                    <form method="POST" action="edit-booking.php?id=<?php echo $bookingId; ?>" class="inline">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" onclick="return confirm('Are you sure you want to cancel this booking?')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md">
                                            <i class="fas fa-times mr-2"></i>
                                            Cancel Booking
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="space-x-3">
                                <a href="bookings.php" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-2 rounded-md">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel
                                </a>
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-md">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Booking History -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Booking Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-1">Booking ID</h3>
                                <p class="text-gray-900">#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-1">Created</h3>
                                <p class="text-gray-900"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-1">Last Updated</h3>
                                <p class="text-gray-900"><?php echo date('M j, Y g:i A', strtotime($booking['updated_at'] ?? $booking['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
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
                document.getElementById('total_cost').value = document.querySelector('input[name="total_cost"]').value;
                document.getElementById('duration').textContent = 'Select dates to calculate';
            }
        }
        
        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
    </script>
</body>
</html>

<?php
// Helper function to update booking
function updateBooking($bookingId, $data, $status) {
    $db = new Database();
    
    try {
        $db->beginTransaction();
        
        // Update rental
        $sql = "UPDATE rentals SET 
                    user_id = :user_id,
                    car_id = :car_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    pickup_location = :pickup_location,
                    dropoff_location = :dropoff_location,
                    total_cost = :total_cost,
                    notes = :notes,
                    status = :status,
                    updated_at = NOW()
                 WHERE id = :id";
        
        $db->query($sql);
        $db->bind(':user_id', $data['user_id']);
        $db->bind(':car_id', $data['car_id']);
        $db->bind(':start_date', $data['start_date']);
        $db->bind(':end_date', $data['end_date']);
        $db->bind(':pickup_location', $data['pickup_location']);
        $db->bind(':dropoff_location', $data['dropoff_location']);
        $db->bind(':total_cost', $data['total_cost']);
        $db->bind(':notes', $data['notes']);
        $db->bind(':status', $status);
        $db->bind(':id', $bookingId);
        
        if ($db->execute()) {
            $db->commit();
            return true;
        } else {
            $db->rollBack();
            return false;
        }
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update booking error: " . $e->getMessage());
        return false;
    }
}
?>