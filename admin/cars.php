<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/Car.php';

$carModel = new Car();
$message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Security validation failed. Please try again.';
        $_SESSION['flash_type'] = 'error';
        header('Location: cars.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $data = [
            'make' => trim($_POST['make'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'year' => intval($_POST['year'] ?? 0),
            'license_plate' => trim($_POST['license_plate'] ?? ''),
            'category' => $_POST['category'] ?? '',
            'daily_rate' => floatval($_POST['daily_rate'] ?? 0),
            'status' => $_POST['status'] ?? 'available',
            'mileage' => intval($_POST['mileage'] ?? 0),
            'color' => trim($_POST['color'] ?? ''),
            'fuel_type' => $_POST['fuel_type'] ?? '',
            'transmission' => $_POST['transmission'] ?? '',
            'seats' => intval($_POST['seats'] ?? 5),
            'description' => trim($_POST['description'] ?? '')
        ];
        
        $errors = $carModel->validateCarData($data);
        
        if (empty($errors)) {
            if ($carModel->addCar($data)) {
                $_SESSION['flash_message'] = 'Car added successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: cars.php');
                exit;
            } else {
                $message = 'Failed to add car. Please try again.';
            }
        }
    } elseif ($action === 'edit') {
        $carId = intval($_POST['car_id'] ?? 0);
        $data = [
            'make' => trim($_POST['make'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'year' => intval($_POST['year'] ?? 0),
            'license_plate' => trim($_POST['license_plate'] ?? ''),
            'category' => $_POST['category'] ?? '',
            'daily_rate' => floatval($_POST['daily_rate'] ?? 0),
            'status' => $_POST['status'] ?? 'available',
            'mileage' => intval($_POST['mileage'] ?? 0),
            'color' => trim($_POST['color'] ?? ''),
            'fuel_type' => $_POST['fuel_type'] ?? '',
            'transmission' => $_POST['transmission'] ?? '',
            'seats' => intval($_POST['seats'] ?? 5),
            'description' => trim($_POST['description'] ?? '')
        ];
        
        $errors = $carModel->validateCarData($data);
        
        if (empty($errors) && $carId > 0) {
            if ($carModel->updateCar($carId, $data)) {
                $_SESSION['flash_message'] = 'Car updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: cars.php');
                exit;
            } else {
                $message = 'Failed to update car. Please try again.';
            }
        }
    } elseif ($action === 'delete') {
        $carId = intval($_POST['car_id'] ?? 0);
        if ($carId > 0 && $carModel->deleteCar($carId)) {
            $_SESSION['flash_message'] = 'Car deleted successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to delete car.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: cars.php');
        exit;
    }
}

// Get cars with filtering
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$cars = $carModel->getAllCars($category, null, null, $status);
$categories = $carModel->getCategories();

$page_title = 'Manage Cars';
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
                        <h1 class="text-2xl font-bold text-gray-900">Manage Cars</h1>
                        <button onclick="openAddCarModal()" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Car
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <div class="p-6">
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="categoryFilter" class="form-input" onchange="filterCars()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="statusFilter" class="form-input" onchange="filterCars()">
                                <option value="">All Status</option>
                                <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="rented" <?php echo $status === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="unavailable" <?php echo $status === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text" id="searchInput" class="form-input" placeholder="Search cars..." onkeyup="filterCars()">
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
                
                <!-- Cars Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Car Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Daily Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="carsTableBody">
                                <?php foreach ($cars as $car): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-16 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-car text-gray-400"></i>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $car['year']; ?> • <?php echo htmlspecialchars($car['license_plate']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo $car['mileage']; ?> miles</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo ucfirst($car['category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">$<?php echo number_format($car['daily_rate'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'available' => 'bg-green-100 text-green-800',
                                                'rented' => 'bg-blue-100 text-blue-800',
                                                'maintenance' => 'bg-yellow-100 text-yellow-800',
                                                'unavailable' => 'bg-red-100 text-red-800'
                                            ];
                                            $color = $statusColors[$car['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                                <?php echo ucfirst($car['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editCar(<?php echo $car['id']; ?>)" class="text-primary-600 hover:text-primary-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteCar(<?php echo $car['id']; ?>, '<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>')" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Car Modal -->
    <div id="carModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Add New Car</h3>
                <button onclick="closeCarModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="carForm" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="car_id" id="carId">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Make *</label>
                        <input type="text" name="make" id="make" class="form-input" required>
                        <?php if (isset($errors['make'])): ?>
                            <p class="text-red-500 text-sm"><?php echo $errors['make']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="form-label">Model *</label>
                        <input type="text" name="model" id="model" class="form-input" required>
                        <?php if (isset($errors['model'])): ?>
                            <p class="text-red-500 text-sm"><?php echo $errors['model']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Year *</label>
                        <input type="number" name="year" id="year" min="1900" max="<?php echo date('Y') + 1; ?>" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">License Plate *</label>
                        <input type="text" name="license_plate" id="license_plate" class="form-input" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Category *</label>
                        <select name="category" id="category" class="form-input" required>
                            <option value="">Select Category</option>
                            <option value="economy">Economy</option>
                            <option value="compact">Compact</option>
                            <option value="midsize">Midsize</option>
                            <option value="fullsize">Fullsize</option>
                            <option value="luxury">Luxury</option>
                            <option value="suv">SUV</option>
                            <option value="van">Van</option>
                            <option value="sports">Sports</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Daily Rate *</label>
                        <input type="number" name="daily_rate" id="daily_rate" min="0" step="0.01" class="form-input" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-input">
                            <option value="available">Available</option>
                            <option value="rented">Rented</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Mileage</label>
                        <input type="number" name="mileage" id="mileage" min="0" class="form-input" value="0">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Color</label>
                        <input type="text" name="color" id="color" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Seats</label>
                        <input type="number" name="seats" id="seats" min="2" max="8" class="form-input" value="5">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Fuel Type</label>
                        <select name="fuel_type" id="fuel_type" class="form-input">
                            <option value="gasoline">Gasoline</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Transmission</label>
                        <select name="transmission" id="transmission" class="form-input">
                            <option value="manual">Manual</option>
                            <option value="automatic">Automatic</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" id="description" rows="3" class="form-input"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeCarModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Car</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Store car data for editing
        const carData = <?php echo json_encode(array_column($cars, null, 'id')); ?>;
        
        function filterCars() {
            const category = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            
            const params = new URLSearchParams();
            if (category) params.set('category', category);
            if (status) params.set('status', status);
            if (search) params.set('search', search);
            
            window.location.href = 'cars.php?' + params.toString();
        }
        
        function openAddCarModal() {
            document.getElementById('modalTitle').textContent = 'Add New Car';
            document.getElementById('formAction').value = 'add';
            document.getElementById('carForm').reset();
            document.getElementById('carModal').classList.remove('hidden');
        }
        
        function editCar(carId) {
            const car = carData[carId];
            if (!car) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Car';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('carId').value = carId;
            
            // Fill form with car data
            document.getElementById('make').value = car.make;
            document.getElementById('model').value = car.model;
            document.getElementById('year').value = car.year;
            document.getElementById('license_plate').value = car.license_plate;
            document.getElementById('category').value = car.category;
            document.getElementById('daily_rate').value = car.daily_rate;
            document.getElementById('status').value = car.status;
            document.getElementById('mileage').value = car.mileage;
            document.getElementById('color').value = car.color || '';
            document.getElementById('seats').value = car.seats;
            document.getElementById('fuel_type').value = car.fuel_type;
            document.getElementById('transmission').value = car.transmission;
            document.getElementById('description').value = car.description || '';
            
            document.getElementById('carModal').classList.remove('hidden');
        }
        
        function closeCarModal() {
            document.getElementById('carModal').classList.add('hidden');
            document.getElementById('carForm').reset();
        }
        
        function deleteCar(carId, carName) {
            if (confirm(`Are you sure you want to delete "${carName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="car_id" value="${carId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('carModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCarModal();
            }
        });
    </script>
</body>
</html>