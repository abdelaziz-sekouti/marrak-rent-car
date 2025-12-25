<?php
require_once __DIR__ . '/../includes/init.php';

// Require login for booking
requireLogin();

require_once __DIR__ . '/../src/models/Car.php';
require_once __DIR__ . '/../src/models/Rental.php';

$carModel = new Car();
$rentalModel = new Rental();

$message = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: cars.php');
        exit;
    }
    
    $carId = intval($_POST['car_id'] ?? 0);
    $data = [
        'user_id' => $_SESSION['user_id'],
        'car_id' => $carId,
        'start_date' => $_POST['start_date'] . ' ' . ($_POST['start_time'] ?? '09:00'),
        'end_date' => $_POST['end_date'] . ' ' . ($_POST['end_time'] ?? '17:00'),
        'pickup_location' => trim($_POST['pickup_location'] ?? ''),
        'dropoff_location' => trim($_POST['dropoff_location'] ?? ''),
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    // Validate car exists and is available
    $car = $carModel->getCarById($carId);
    if (!$car) {
        $_SESSION['flash_message'] = 'Invalid car selection';
        $_SESSION['flash_type'] = 'error';
        header('Location: cars.php');
        exit;
    }
    
    if (!$carModel->isAvailable($carId, $data['start_date'], $data['end_date'])) {
        $_SESSION['flash_message'] = 'Selected car is not available for the requested dates';
        $_SESSION['flash_type'] = 'error';
        header('Location: cars.php');
        exit;
    }
    
    // Calculate total cost
    $data['total_cost'] = $rentalModel->calculateCost($carId, $data['start_date'], $data['end_date']);
    $data['total_cost'] = $data['total_cost'] > 0 ? $data['total_cost'] : $car['daily_rate'] * 1; // Fallback to 1 day
    
    $errors = $rentalModel->validateRentalData($data);
    
    if (empty($errors)) {
        $result = $rentalModel->createRental(
            $data['user_id'],
            $data['car_id'],
            $data['start_date'],
            $data['end_date'],
            $data['total_cost'],
            $data['pickup_location'],
            $data['dropoff_location']
        );
        
        if ($result['success']) {
            $_SESSION['flash_message'] = 'Booking request submitted successfully! We will confirm your booking shortly. Booking ID: #' . str_pad($result['rental_id'], 6, '0', STR_PAD_LEFT);
            $_SESSION['flash_type'] = 'success';
            
            // Send confirmation email (would implement here)
            
            header('Location: profile.php');
            exit;
        } else {
            $message = $result['message'];
        }
    } else {
        // Store errors for display
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = $data;
        header('Location: cars.php');
        exit;
    }
} else {
    // Not a POST request, redirect to cars page
    header('Location: cars.php');
    exit;
}

// Display error page if there were errors
if (!empty($_SESSION['booking_errors'])) {
    $errors = $_SESSION['booking_errors'];
    $data = $_SESSION['booking_data'];
    unset($_SESSION['booking_errors']);
    unset($_SESSION['booking_data']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Error - Marrak Rent Car</title>
    <meta name="description" content="Booking error page for Marrak Rent Car">
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <?php require_once __DIR__ . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-grow min-h-screen flex items-center justify-center py-12">
        <div class="max-w-md mx-auto">
            <!-- Error Alert -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400 text-2xl"></i>
                    </div>
                    <div class="ml-3">
                        <h2 class="text-lg font-medium text-red-800">Booking Error</h2>
                        <p class="mt-2 text-red-700">There was an error processing your booking request. Please correct the following issues:</p>
                    </div>
                </div>
            </div>
            
            <!-- Validation Errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Please correct the following errors:</h3>
                    <ul class="space-y-2">
                        <?php foreach ($errors as $field => $error): ?>
                            <li class="flex items-start">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3"></i>
                                <div>
                                    <span class="font-medium text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $field)); ?>:</span>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($error); ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Retry Button -->
                    <div class="mt-6 text-center">
                        <a href="javascript:history.back()" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Go Back and Try Again
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Generic Error -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                    <i class="fas fa-exclamation-circle text-gray-400 text-5xl mb-4"></i>
                    <h2 class="text-xl font-medium text-gray-900 mb-2">Oops! Something went wrong</h2>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($message); ?></p>
                    <div class="text-center">
                        <a href="cars.php" class="btn btn-primary">
                            <i class="fas fa-home mr-2"></i>
                            Back to Cars
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Footer -->
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        // Auto-scroll to top when error page loads
        window.scrollTo({ top: 0, behavior: 'smooth' });
    </script>
</body>
</html>