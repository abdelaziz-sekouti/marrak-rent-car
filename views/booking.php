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

// Get car ID from URL
$carId = intval($_GET['id'] ?? 0);
$car = null;

if ($carId > 0) {
    $car = $carModel->getCarById($carId);
    if (!$car) {
        $_SESSION['flash_message'] = 'Car not found';
        $_SESSION['flash_type'] = 'error';
        header('Location: cars.php');
        exit;
    }
    
    // Check if car is available
    $car['available'] = $carModel->isAvailable($carId, date('Y-m-d'), date('Y-m-d', strtotime('+30 days')));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: booking.php?id=' . $carId);
        exit;
    }
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'car_id' => $carId,
        'start_date' => $_POST['start_date'] . ' ' . ($_POST['start_time'] ?? '09:00'),
        'end_date' => $_POST['end_date'] . ' ' . ($_POST['end_time'] ?? '17:00'),
        'pickup_location' => trim($_POST['pickup_location'] ?? ''),
        'dropoff_location' => trim($_POST['dropoff_location'] ?? ''),
        'total_cost' => floatval($_POST['total_cost'] ?? 0),
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
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
            $_SESSION['flash_message'] = 'Booking request submitted successfully! We will confirm your booking shortly.';
            $_SESSION['flash_type'] = 'success';
            header('Location: profile.php');
            exit;
        } else {
            $message = $result['message'];
        }
    }
}

// Get featured cars for recommendations
$featuredCars = $carModel->getFeaturedCars(4);

$page_title = 'Book a Car';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car</title>
    <meta name="description" content="Book your perfect car rental with Marrak Rent Car">
    
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
        <?php if ($car): ?>
            <!-- Booking Form Section -->
            <section class="bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    
                    <!-- Car Information Card -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column - Car Info -->
                            <div>
                                <div class="flex items-center mb-6">
                                    <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h1>
                                    <?php if ($car['status'] === 'available'): ?>
                                        <span class="ml-3 px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">Available</span>
                                    <?php else: ?>
                                        <span class="ml-3 px-3 py-1 bg-red-100 text-red-800 text-sm font-medium rounded-full">Currently Rented</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-calendar-alt mr-3 w-5"></i>
                                        <span>Year: <?php echo $car['year']; ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-cogs mr-3 w-5"></i>
                                        <span>Transmission: <?php echo ucfirst($car['transmission']); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-gas-pump mr-3 w-5"></i>
                                        <span>Fuel Type: <?php echo ucfirst($car['fuel_type']); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-users mr-3 w-5"></i>
                                        <span>Seats: <?php echo $car['seats']; ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                                        <span>Mileage: <?php echo number_format($car['mileage']); ?> miles</span>
                                    </div>
                                    
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-palette mr-3 w-5"></i>
                                        <span>Color: <?php echo htmlspecialchars($car['color']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($car['description'])): ?>
                                <div class="mt-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                                    <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Right Column - Pricing & Image -->
                            <div>
                                <!-- Pricing -->
                                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing</h3>
                                    <div class="text-center">
                                        <div class="text-4xl font-bold text-primary-600 mb-2">$<?php echo number_format($car['daily_rate'], 2); ?></div>
                                        <div class="text-gray-600">per day</div>
                                    </div>
                                    
                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Security Deposit:</span>
                                            <span class="font-medium text-gray-900">$<?php echo number_format($car['daily_rate'] * 3, 2); ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Insurance:</span>
                                            <span class="font-medium text-green-600">Included</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Mileage Limit:</span>
                                            <span class="font-medium text-gray-900">200 miles/day</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Availability Status -->
                                <?php if (isset($car['available'])): ?>
                                    <div class="mt-6 text-center">
                                        <?php if ($car['available']): ?>
                                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
                                                <p class="text-green-800 font-medium">Car is available for booking!</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                                <i class="fas fa-times-circle text-red-600 text-2xl mb-2"></i>
                                                <p class="text-red-800 font-medium">Car is currently unavailable</p>
                                                <p class="text-red-600 text-sm mt-1">Please check back later or choose another car</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Booking Form -->
                    <?php if (isset($car['available']) && $car['available']): ?>
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Complete Your Booking</h2>
                        
                        <form method="POST" action="booking.php?id=<?php echo $carId; ?>" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Pickup Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-check mr-2"></i>
                                        Pickup Date *
                                    </label>
                                    <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <!-- Pickup Time -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-2"></i>
                                        Pickup Time *
                                    </label>
                                    <select name="start_time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="08:00">8:00 AM</option>
                                        <option value="09:00" selected>9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="12:00">12:00 PM</option>
                                        <option value="13:00">1:00 PM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                        <option value="17:00" selected>5:00 PM</option>
                                        <option value="18:00">6:00 PM</option>
                                    </select>
                                </div>
                                
                                <!-- Return Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        Return Date *
                                    </label>
                                    <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <!-- Return Time -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-clock mr-2"></i>
                                        Return Time *
                                    </label>
                                    <select name="end_time" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="16:00">4:00 PM</option>
                                        <option value="17:00" selected>5:00 PM</option>
                                        <option value="18:00">6:00 PM</option>
                                        <option value="19:00">7:00 PM</option>
                                        <option value="20:00">8:00 PM</option>
                                    </select>
                                </div>
                                
                                <!-- Pickup Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        Pickup Location *
                                    </label>
                                    <input type="text" name="pickup_location" required 
                                           placeholder="Enter pickup address or location" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                
                                <!-- Drop-off Location -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-flag-checkered mr-2"></i>
                                        Drop-off Location *
                                    </label>
                                    <input type="text" name="dropoff_location" required 
                                           placeholder="Enter drop-off address or location" 
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                            
                            <!-- Additional Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calculator mr-2"></i>
                                        Total Cost
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium text-lg">$</span>
                                        </div>
                                        <input type="number" name="total_cost" id="total_cost" step="0.01" readonly 
                                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-lg font-medium text-gray-900">
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">Calculated automatically based on rental duration</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-sticky-note mr-2"></i>
                                        Special Requests
                                    </label>
                                    <textarea name="notes" rows="4" placeholder="Any special requirements or requests..." 
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                                </div>
                            </div>
                            
                            <!-- Error Display -->
                            <?php if (!empty($message)): ?>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($message); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Form Validation Errors -->
                            <?php if (!empty($errors)): ?>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-exclamation-circle text-yellow-600 mr-3"></i>
                                        <p class="text-yellow-800 font-medium">Please correct the following errors:</p>
                                    </div>
                                    <ul class="space-y-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li class="flex items-center text-yellow-700">
                                                <i class="fas fa-times-circle text-yellow-600 mr-2 text-sm"></i>
                                                <?php echo htmlspecialchars($error); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold px-8 py-4 rounded-lg text-lg transition-all duration-200 transform hover:scale-105">
                                    <i class="fas fa-check-circle mr-3"></i>
                                    Complete Booking
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <!-- Unavailable Car Message -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-car text-red-600 text-4xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">This Car is Currently Unavailable</h2>
                            <p class="text-gray-600 mb-6">We apologize, but this car is currently rented or under maintenance. Please choose another vehicle or check back later.</p>
                            <div class="space-y-3">
                                <a href="cars.php" class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Browse Available Cars
                                </a>
                                <a href="index.php" class="inline-flex items-center bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium px-6 py-3 rounded-lg transition-colors ml-3">
                                    <i class="fas fa-home mr-2"></i>
                                    Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Recommendations Section -->
            <?php if (!empty($featuredCars)): ?>
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">You Might Also Like</h2>
                        <p class="text-gray-600 max-w-2xl mx-auto">Check out these similar cars available for rent</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <?php foreach ($featuredCars as $featuredCar): ?>
                            <div class="group hover-lift">
                                <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                                    <!-- Car Image/Icon -->
                                    <div class="relative h-48 bg-gradient-to-br from-blue-100 to-indigo-100">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <i class="fas fa-car-side text-6xl text-blue-300 group-hover:scale-110 transition-transform duration-300"></i>
                                        </div>
                                        
                                        <!-- Category Badge -->
                                        <div class="absolute top-4 right-4">
                                            <span class="bg-primary-600 text-white px-3 py-1 rounded-full text-xs font-medium">
                                                <?php echo ucfirst($featuredCar['category']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Car Details -->
                                    <div class="p-6">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($featuredCar['make'] . ' ' . $featuredCar['model']); ?>
                                            </h3>
                                            <div class="flex items-center text-yellow-400">
                                                <i class="fas fa-star text-sm"></i>
                                                <span class="text-gray-600 text-sm ml-1">4.5</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Car Features -->
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"><?php echo ucfirst($featuredCar['transmission']); ?></span>
                                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"><?php echo $featuredCar['seats']; ?> Seats</span>
                                            <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs">Available</span>
                                        </div>
                                        
                                        <p class="text-gray-600 text-sm mb-4">
                                            Great value and reliable performance
                                        </p>
                                        
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <span class="text-2xl font-bold text-primary-600">$<?php echo number_format($featuredCar['daily_rate'], 2); ?></span>
                                                <span class="text-gray-500 text-sm">/day</span>
                                            </div>
                                            
                                            <a href="booking.php?id=<?php echo $featuredCar['id']; ?>" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors">
                                                Book Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        <?php else: ?>
            <!-- No Car Selected -->
            <section class="py-16 bg-gray-100">
                <div class="max-w-md mx-auto text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-question-circle text-gray-400 text-5xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">No Car Selected</h2>
                    <p class="text-gray-600 mb-8">Please choose a car from our fleet to make a booking.</p>
                    <a href="cars.php" class="inline-flex items-center bg-primary-600 hover:bg-primary-700 text-white font-bold px-8 py-4 rounded-lg transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-car mr-3"></i>
                        Browse Our Fleet
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    
    <!-- JavaScript for Booking Form -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startInput = document.querySelector('input[name="start_date"]');
            const endInput = document.querySelector('input[name="end_date"]');
            const costInput = document.getElementById('total_cost');
            const dailyRate = <?php echo $car['daily_rate'] ?? 0; ?>;
            
            function calculateCost() {
                if (startInput.value && endInput.value && dailyRate > 0) {
                    const startDate = new Date(startInput.value);
                    const endDate = new Date(endInput.value);
                    
                    if (endDate <= startDate) {
                        costInput.value = '';
                        return;
                    }
                    
                    const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                    const total = days * dailyRate;
                    
                    costInput.value = total.toFixed(2);
                } else {
                    costInput.value = '';
                }
            }
            
            function updateEndDateMin() {
                if (startInput.value) {
                    const minDate = new Date(startInput.value);
                    minDate.setDate(minDate.getDate() + 1);
                    endInput.min = minDate.toISOString().split('T')[0];
                } else {
                    endInput.min = new Date().toISOString().split('T')[0];
                }
            }
            
            if (startInput && endInput && costInput) {
                startInput.addEventListener('change', function() {
                    updateEndDateMin();
                    calculateCost();
                });
                
                endInput.addEventListener('change', calculateCost);
            }
        });
    </script>
</body>
</html>