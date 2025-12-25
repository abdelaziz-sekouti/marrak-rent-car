<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

// Get dashboard statistics
require_once __DIR__ . '/../src/models/Car.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Rental.php';

$carModel = new Car();
$userModel = new User();
$rentalModel = new Rental();

// Statistics
$stmt = $carModel->db->executeQuery("SELECT COUNT(*) as count FROM cars");
$totalCars = $stmt ? $stmt->fetch()['count'] : 0;

$stmt = $carModel->db->executeQuery("SELECT COUNT(*) as count FROM cars WHERE status = 'available'");
$availableCars = $stmt ? $stmt->fetch()['count'] : 0;

$stmt = $userModel->db->executeQuery("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt ? $stmt->fetch()['count'] : 0;

$stmt = $rentalModel->db->executeQuery("SELECT COUNT(*) as count FROM rentals");
$totalRentals = $stmt ? $stmt->fetch()['count'] : 0;

// Monthly revenue (last 30 days)
$stmt = $rentalModel->db->executeQuery("
    SELECT SUM(total_cost) as revenue 
    FROM rentals 
    WHERE status = 'completed' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$monthlyRevenue = $stmt ? $stmt->fetch()['revenue'] : 0;

// Recent rentals
$stmt = $rentalModel->db->executeQuery("
    SELECT r.*, u.name as user_name, c.make, c.model 
    FROM rentals r 
    JOIN users u ON r.user_id = u.id 
    JOIN cars c ON r.car_id = c.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$recentRentals = $stmt ? $stmt->fetchAll() : [];

// Popular cars
$stmt = $rentalModel->db->executeQuery("
    SELECT c.make, c.model, COUNT(r.id) as rental_count 
    FROM cars c 
    LEFT JOIN rentals r ON c.id = r.car_id 
    GROUP BY c.id 
    ORDER BY rental_count DESC 
    LIMIT 5
");
$popularCars = $stmt ? $stmt->fetchAll() : [];

// Active rentals by status
$stmt = $rentalModel->db->executeQuery("
    SELECT status, COUNT(*) as count 
    FROM rentals 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
");
$rentalStats = $stmt ? $stmt->fetchAll() : [];

$page_title = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Admin dashboard for Marrak Rent Car">
    
    <!-- TailwindCSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        .stat-card {
            @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200;
        }
        .stat-card .icon {
            @apply w-12 h-12 rounded-lg flex items-center justify-center text-xl;
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
                            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                            <p class="text-sm text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('M j, Y, g:i A'); ?>
                            </div>
                            <div class="flex items-center">
                                <img src="https://picsum.photos/seed/admin/40/40.jpg" alt="Admin" class="w-8 h-8 rounded-full">
                                <span class="ml-2 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Cars</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalCars); ?></p>
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    <?php echo $availableCars; ?> available
                                </p>
                            </div>
                            <div class="icon bg-blue-100 text-blue-600">
                                <i class="fas fa-car"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Users</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalUsers); ?></p>
                                <p class="text-sm text-gray-500 mt-1">Registered customers</p>
                            </div>
                            <div class="icon bg-green-100 text-green-600">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Rentals</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalRentals); ?></p>
                                <p class="text-sm text-gray-500 mt-1">All time</p>
                            </div>
                            <div class="icon bg-purple-100 text-purple-600">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                                <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($monthlyRevenue, 0); ?></p>
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +12% from last month
                                </p>
                            </div>
                            <div class="icon bg-yellow-100 text-yellow-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts and Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Rental Status Chart -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rental Status Overview</h2>
                            <canvas id="rentalChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <!-- Popular Cars -->
                    <div>
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Popular Cars</h2>
                            <div class="space-y-3">
                                <?php foreach ($popularCars as $car): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo $car['rental_count']; ?> rentals</p>
                                        </div>
                                        <div class="text-primary-600">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Rentals Table -->
                <div class="mt-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Recent Rentals</h2>
                                <a href="rentals.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    View All <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recentRentals as $rental): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($rental['user_name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($rental['make'] . ' ' . $rental['model']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></div>
                                                <div class="text-sm text-gray-500">to <?php echo date('M j, Y', strtotime($rental['end_date'])); ?></div>
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
                                                <a href="rental-details.php?id=<?php echo $rental['id']; ?>" class="text-primary-600 hover:text-primary-900 mr-3">View</a>
                                                <a href="edit-rental.php?id=<?php echo $rental['id']; ?>" class="text-gray-600 hover:text-gray-900">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Rental Status Chart
        const ctx = document.getElementById('rentalChart').getContext('2d');
        const rentalData = <?php echo json_encode($rentalStats); ?>;
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: rentalData.map(item => item.status),
                datasets: [{
                    data: rentalData.map(item => item.count),
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>