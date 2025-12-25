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

// Get report parameters
$report_type = $_GET['report_type'] ?? 'revenue';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Generate report data based on type
$report_data = [];
$chart_data = [];

switch ($report_type) {
    case 'revenue':
        $report_data = generateRevenueReport($start_date, $end_date);
        $chart_data = generateRevenueChartData($start_date, $end_date);
        break;
    case 'rentals':
        $report_data = generateRentalsReport($start_date, $end_date);
        $chart_data = generateRentalsChartData($start_date, $end_date);
        break;
    case 'cars':
        $report_data = generateCarsReport();
        $chart_data = generateCarsChartData();
        break;
    case 'customers':
        $report_data = generateCustomersReport($start_date, $end_date);
        $chart_data = generateCustomersChartData($start_date, $end_date);
        break;
}

$page_title = 'Reports & Analytics';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Marrak Rent Car Admin</title>
    <meta name="description" content="Reports and analytics for Marrak Rent Car">
    
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
        .report-tabs button {
            @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200;
        }
        .report-tabs button.active {
            @apply bg-primary-600 text-white;
        }
        .report-tabs button:not(.active) {
            @apply bg-gray-200 text-gray-700 hover:bg-gray-300;
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
                            <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
                            <p class="text-sm text-gray-600">Business insights and performance metrics</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <form method="GET" class="flex items-center space-x-3">
                                <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                                <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <span class="text-gray-500">to</span>
                                <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500">
                                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md">
                                    <i class="fas fa-filter mr-2"></i>
                                    Filter
                                </button>
                            </form>
                            <button onclick="exportReport()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-download mr-2"></i>
                                Export
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Reports Content -->
            <div class="p-6">
                <!-- Report Type Tabs -->
                <div class="mb-6">
                    <div class="report-tabs space-x-2">
                        <button onclick="changeReportType('revenue')" id="tab-revenue" class="<?php echo $report_type === 'revenue' ? 'active' : ''; ?> hover:text-blue-400 hover:underline transition-all">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Revenue
                        </button>
                        <button onclick="changeReportType('rentals')" id="tab-rentals" class="<?php echo $report_type === 'rentals' ? 'active' : ''; ?> hover:text-blue-400 hover:underline transition-all">
                            <i class="fas fa-car mr-2"></i>
                            Rentals
                        </button>
                        <button onclick="changeReportType('cars')" id="tab-cars" class="<?php echo $report_type === 'cars' ? 'active' : ''; ?> hover:text-blue-400 hover:underline transition-all">
                            <i class="fas fa-car-side mr-2"></i>
                            Cars
                        </button>
                        <button onclick="changeReportType('customers')" id="tab-customers" class="<?php echo $report_type === 'customers' ? 'active' : ''; ?> hover:text-blue-400 hover:underline transition-all">
                            <i class="fas fa-users mr-2"></i>
                            Customers
                        </button>
                    </div>
                </div>
                
                <!-- Revenue Report -->
                <?php if ($report_type === 'revenue'): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                    <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($report_data['total_revenue'], 2); ?></p>
                                    <p class="text-sm text-green-600 mt-1">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        <?php echo $report_data['revenue_change']; ?>% from last period
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Average Rental</p>
                                    <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($report_data['average_rental'], 2); ?></p>
                                    <p class="text-sm text-gray-500 mt-1">Per booking</p>
                                </div>
                                <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($report_data['total_bookings']); ?></p>
                                    <p class="text-sm text-gray-500 mt-1">In selected period</p>
                                </div>
                                <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Occupancy Rate</p>
                                    <p class="text-3xl font-bold text-gray-900"><?php echo $report_data['occupancy_rate']; ?>%</p>
                                    <p class="text-sm text-gray-500 mt-1">Fleet utilization</p>
                                </div>
                                <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Chart Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Main Chart -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <?php 
                            switch($report_type) {
                                case 'revenue': echo 'Revenue Trend'; break;
                                case 'rentals': echo 'Rental Activity'; break;
                                case 'cars': echo 'Car Utilization'; break;
                                case 'customers': echo 'Customer Growth'; break;
                            }
                            ?>
                        </h2>
                        <canvas id="mainChart" width="400" height="200"></canvas>
                    </div>
                    
                    <!-- Secondary Chart -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <?php 
                            switch($report_type) {
                                case 'revenue': echo 'Revenue by Category'; break;
                                case 'rentals': echo 'Rental Status Distribution'; break;
                                case 'cars': echo 'Car Categories'; break;
                                case 'customers': echo 'Customer Types'; break;
                            }
                            ?>
                        </h2>
                        <canvas id="secondaryChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <!-- Detailed Tables -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Detailed Report</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if ($report_type === 'revenue'): ?>
                            <!-- Revenue Details Table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data['daily_breakdown'] as $day): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M j, Y', strtotime($day['date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $day['bookings']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                $<?php echo number_format($day['revenue'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                $<?php echo number_format($day['average'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($report_type === 'cars'): ?>
                            <!-- Cars Performance Table -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Car</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Rentals</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data['car_performance'] as $car): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo $car['license_plate']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $car['total_rentals']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                $<?php echo number_format($car['total_revenue'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $car['utilization']; ?>%
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $car['status'] === 'available' ? 'bg-green-100 text-green-800' : ($car['status'] === 'rented' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                    <?php echo ucfirst($car['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Chart data
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        // Initialize main chart
        const mainCtx = document.getElementById('mainChart').getContext('2d');
        const mainChart = new Chart(mainCtx, {
            type: chartData.mainChart.type,
            data: {
                labels: chartData.mainChart.labels,
                datasets: chartData.mainChart.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: chartData.mainChart.scales || {}
            }
        });
        
        // Initialize secondary chart
        const secondaryCtx = document.getElementById('secondaryChart').getContext('2d');
        const secondaryChart = new Chart(secondaryCtx, {
            type: chartData.secondaryChart.type,
            data: {
                labels: chartData.secondaryChart.labels,
                datasets: chartData.secondaryChart.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        
        function changeReportType(type) {
            const url = new URL(window.location);
            url.searchParams.set('report_type', type);
            window.location.href = url.toString();
        }
        
        function exportReport() {
            const reportType = '<?php echo $report_type; ?>';
            const startDate = '<?php echo $start_date; ?>';
            const endDate = '<?php echo $end_date; ?>';
            
            // Create form for export
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export-reports.php';
            
            form.innerHTML = `
                <input type="hidden" name="report_type" value="${reportType}">
                <input type="hidden" name="start_date" value="${startDate}">
                <input type="hidden" name="end_date" value="${endDate}">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            `;
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

<?php
// Report generation functions
function generateRevenueReport($startDate, $endDate) {
    // Mock data - in real implementation, this would query the database
    return [
        'total_revenue' => 125450.50,
        'revenue_change' => 12.5,
        'average_rental' => 245.80,
        'total_bookings' => 511,
        'occupancy_rate' => 78,
        'daily_breakdown' => [
            ['date' => '2024-01-01', 'bookings' => 12, 'revenue' => 2890.50, 'average' => 240.88],
            ['date' => '2024-01-02', 'bookings' => 15, 'revenue' => 3680.00, 'average' => 245.33],
            ['date' => '2024-01-03', 'bookings' => 8, 'revenue' => 1952.00, 'average' => 244.00],
            // More days...
        ]
    ];
}

function generateRentalsReport($startDate, $endDate) {
    // Mock rental report data
    return [
        'total_rentals' => 511,
        'active_rentals' => 45,
        'completed_rentals' => 466,
        'cancelled_rentals' => 12,
        'average_duration' => 3.2
    ];
}

function generateCarsReport() {
    // Mock car report data
    return [
        'total_cars' => 25,
        'available_cars' => 18,
        'rented_cars' => 6,
        'maintenance_cars' => 1,
        'car_performance' => [
            ['make' => 'Toyota', 'model' => 'Camry', 'license_plate' => 'ABC-123', 'total_rentals' => 45, 'total_revenue' => 11060.50, 'utilization' => 82, 'status' => 'available'],
            ['make' => 'Honda', 'model' => 'Civic', 'license_plate' => 'XYZ-789', 'total_rentals' => 38, 'total_revenue' => 9342.00, 'utilization' => 75, 'status' => 'rented'],
            // More cars...
        ]
    ];
}

function generateCustomersReport($startDate, $endDate) {
    // Mock customer report data
    return [
        'total_customers' => 1240,
        'new_customers' => 68,
        'returning_customers' => 1172,
        'customer_retention' => 94.6,
        'top_customers' => [
            ['name' => 'John Doe', 'total_rentals' => 12, 'total_spent' => 2949.60],
            ['name' => 'Jane Smith', 'total_rentals' => 8, 'total_spent' => 1966.40],
            // More customers...
        ]
    ];
}

function generateRevenueChartData($startDate, $endDate) {
    // Mock chart data for revenue
    return [
        'mainChart' => [
            'type' => 'line',
            'labels' => ['Jan 1', 'Jan 2', 'Jan 3', 'Jan 4', 'Jan 5', 'Jan 6', 'Jan 7'],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [2890.50, 3680.00, 1952.00, 4120.50, 3540.00, 4890.50, 2980.00],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value; }'
                    ]
                ]
            ]
        ],
        'secondaryChart' => [
            'type' => 'doughnut',
            'labels' => ['Economy', 'Compact', 'Midsize', 'Luxury', 'SUV'],
            'datasets' => [
                [
                    'data' => [30, 25, 20, 15, 10],
                    'backgroundColor' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']
                ]
            ]
        ]
    ];
}

function generateRentalsChartData($startDate, $endDate) {
    // Mock chart data for rentals
    return [
        'mainChart' => [
            'type' => 'bar',
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'datasets' => [
                [
                    'label' => 'Rentals',
                    'data' => [45, 52, 48, 58, 65, 72, 68],
                    'backgroundColor' => '#3B82F6'
                ]
            ]
        ],
        'secondaryChart' => [
            'type' => 'pie',
            'labels' => ['Completed', 'Active', 'Pending', 'Cancelled'],
            'datasets' => [
                [
                    'data' => [466, 45, 12, 8],
                    'backgroundColor' => ['#10B981', '#3B82F6', '#F59E0B', '#EF4444']
                ]
            ]
        ]
    ];
}

function generateCarsChartData() {
    // Mock chart data for cars
    return [
        'mainChart' => [
            'type' => 'bar',
            'labels' => ['Economy', 'Compact', 'Midsize', 'Luxury', 'SUV', 'Van'],
            'datasets' => [
                [
                    'label' => 'Utilization %',
                    'data' => [85, 78, 82, 65, 90, 45],
                    'backgroundColor' => '#10B981'
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100
                ]
            ]
        ],
        'secondaryChart' => [
            'type' => 'doughnut',
            'labels' => ['Available', 'Rented', 'Maintenance', 'Unavailable'],
            'datasets' => [
                [
                    'data' => [18, 6, 1, 0],
                    'backgroundColor' => ['#10B981', '#3B82F6', '#F59E0B', '#EF4444']
                ]
            ]
        ]
    ];
}

function generateCustomersChartData($startDate, $endDate) {
    // Mock chart data for customers
    return [
        'mainChart' => [
            'type' => 'line',
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'New Customers',
                    'data' => [45, 52, 48, 58, 65, 68],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true
                ]
            ]
        ],
        'secondaryChart' => [
            'type' => 'pie',
            'labels' => ['New', 'Returning', 'VIP', 'Inactive'],
            'datasets' => [
                [
                    'data' => [68, 1172, 45, 23],
                    'backgroundColor' => ['#10B981', '#3B82F6', '#8B5CF6', '#EF4444']
                ]
            ]
        ]
    ];
}
?>