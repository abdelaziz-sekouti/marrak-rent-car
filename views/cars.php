<?php
require_once '../includes/init.php';

// Load models
require_once '../src/models/Car.php';

$carModel = new Car();

// Get filtering parameters
$category = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$search = $_GET['search'] ?? '';

// Get cars with filters
$cars = $carModel->searchCars($search, [
    'category' => $category,
    'min_price' => $minPrice,
    'max_price' => $maxPrice
]);

// Get categories for filter
$categories = $carModel->getCategories();
$priceRange = $carModel->getPriceRange();

$page_title = 'Browse Cars - Marrak Rent Car';
$page_description = 'Browse our wide selection of rental cars with competitive prices.';
?>

<?php require_once '../includes/header.php'; ?>

<main class="flex-grow">
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-primary-600 to-primary-700 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4 animate-fade-in">Our Fleet</h1>
                <p class="text-xl mb-8 max-w-2xl mx-auto animate-slide-up">
                    Choose from our wide selection of well-maintained vehicles to suit your needs and budget
                </p>
            </div>
        </div>
    </section>
    
    <!-- Search and Filter Section -->
    <section class="py-8 bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <div class="relative">
                        <input type="text" id="carSearch" class="form-input pr-10" 
                               placeholder="Search by make, model, or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Category Filter -->
                <div>
                    <select id="categoryFilter" class="form-input">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category']; ?>" 
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Price Range Filter -->
                <div>
                    <select id="priceFilter" class="form-input">
                        <option value="">All Prices</option>
                        <option value="0-50" <?php echo $maxPrice == '50' ? 'selected' : ''; ?>>Under $50/day</option>
                        <option value="50-100" <?php echo ($minPrice == '50' && $maxPrice == '100') ? 'selected' : ''; ?>>$50 - $100/day</option>
                        <option value="100-200" <?php echo ($minPrice == '100' && $maxPrice == '200') ? 'selected' : ''; ?>>$100 - $200/day</option>
                        <option value="200+" <?php echo $minPrice == '200' ? 'selected' : ''; ?>>Over $200/day</option>
                    </select>
                </div>
            </div>
            
            <!-- Active Filters Display -->
            <?php if ($category || $minPrice || $maxPrice || $search): ?>
                <div class="mt-4 flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Active filters:</span>
                    <?php if ($category): ?>
                        <span class="bg-primary-100 text-primary-800 px-2 py-1 rounded-full text-xs">
                            <?php echo ucfirst($category); ?>
                            <button onclick="removeFilter('category')" class="ml-1">×</button>
                        </span>
                    <?php endif; ?>
                    <?php if ($minPrice || $maxPrice): ?>
                        <span class="bg-primary-100 text-primary-800 px-2 py-1 rounded-full text-xs">
                            $<?php echo $minPrice ?: '0'; ?> - $<?php echo $maxPrice ?: '∞'; ?>
                            <button onclick="removeFilter('price')" class="ml-1">×</button>
                        </span>
                    <?php endif; ?>
                    <?php if ($search): ?>
                        <span class="bg-primary-100 text-primary-800 px-2 py-1 rounded-full text-xs">
                            "<?php echo htmlspecialchars($search); ?>"
                            <button onclick="removeFilter('search')" class="ml-1">×</button>
                        </span>
                    <?php endif; ?>
                    <button onclick="clearAllFilters()" class="text-primary-600 hover:text-primary-800 text-sm">
                        Clear all
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Cars Grid -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <p class="text-gray-600">
                    Showing <span class="font-semibold"><?php echo count($cars); ?></span> cars
                </p>
                <div class="flex items-center space-x-4">
                    <label class="text-sm text-gray-600">Sort by:</label>
                    <select id="sortFilter" class="form-input text-sm py-1">
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="make">Make: A to Z</option>
                        <option value="year">Year: Newest First</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="carsGrid">
                <?php foreach ($cars as $car): ?>
                    <div class="card hover:shadow-xl transition-all duration-300 scroll-animate">
                        <!-- Car Image -->
                        <div class="relative h-48 bg-gradient-to-br from-primary-100 to-secondary-100 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-car-side text-6xl text-primary-300"></i>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="absolute top-4 right-4">
                                <?php
                                $statusColors = [
                                    'available' => 'bg-green-500',
                                    'rented' => 'bg-red-500',
                                    'maintenance' => 'bg-yellow-500',
                                    'unavailable' => 'bg-gray-500'
                                ];
                                $bgColor = $statusColors[$car['status']] ?? 'bg-gray-500';
                                ?>
                                <span class="px-3 py-1 <?php echo $bgColor; ?> text-white text-xs font-semibold rounded-full">
                                    <?php echo ucfirst($car['status']); ?>
                                </span>
                            </div>
                            
                            <!-- Category Badge -->
                            <div class="absolute top-4 left-4">
                                <span class="bg-white bg-opacity-90 text-gray-800 px-3 py-1 text-xs font-semibold rounded-full">
                                    <?php echo ucfirst($car['category']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Car Details -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-gray-900 mb-1">
                                    <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>
                                </h3>
                                <div class="flex items-center text-sm text-gray-600 mb-2">
                                    <span class="mr-4"><?php echo $car['year']; ?></span>
                                    <span class="mr-4"><?php echo $car['mileage']; ?> mi</span>
                                    <span><?php echo $car['color']; ?></span>
                                </div>
                                
                                <!-- Features -->
                                <div class="flex flex-wrap gap-1 mb-3">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                        <?php echo ucfirst($car['transmission']); ?>
                                    </span>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                        <?php echo ucfirst($car['fuel_type']); ?>
                                    </span>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                        <?php echo $car['seats']; ?> Seats
                                    </span>
                                </div>
                                
                                <!-- Description -->
                                <p class="text-gray-600 text-sm mb-4">
                                    <?php echo htmlspecialchars(substr($car['description'], 0, 100) . (strlen($car['description']) > 100 ? '...' : '')); ?>
                                </p>
                            </div>
                            
                            <!-- Price and Actions -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-2xl font-bold text-primary-600">$<?php echo number_format($car['daily_rate'], 0); ?></span>
                                    <span class="text-gray-500 text-sm">/day</span>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="viewCarDetails(<?php echo $car['id']; ?>)" 
                                            class="btn btn-outline text-sm">
                                        <i class="fas fa-eye mr-1"></i>
                                        View
                                    </button>
                                    <button onclick="bookCar(<?php echo $car['id']; ?>)" 
                                            class="btn btn-primary text-sm <?php echo $car['status'] !== 'available' ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                            <?php echo $car['status'] !== 'available' ? 'disabled' : ''; ?>>
                                        <i class="fas fa-calendar-check mr-1"></i>
                                        Book
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- No Results -->
            <?php if (empty($cars)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No cars found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your filters or search criteria</p>
                    <button onclick="clearAllFilters()" class="btn btn-primary">
                        <i class="fas fa-times mr-2"></i>
                        Clear Filters
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Car Details Modal -->
    <div id="carModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold text-gray-900" id="modalCarTitle">Car Details</h3>
                <button onclick="closeCarModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="modalCarContent">
                <!-- Content will be loaded dynamically -->
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="closeCarModal()" class="btn btn-secondary">Close</button>
                <button onclick="bookCarFromModal()" class="btn btn-primary">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Book This Car
                </button>
            </div>
        </div>
    </div>
</main>

<script>
// Store car data
const carsData = <?php echo json_encode(array_column($cars, null, 'id')); ?>;
let currentCarId = null;

// Search functionality
document.getElementById('carSearch')?.addEventListener('input', debounce(function() {
    applyFilters();
}, 300));

// Filter listeners
document.getElementById('categoryFilter')?.addEventListener('change', applyFilters);
document.getElementById('priceFilter')?.addEventListener('change', applyFilters);
document.getElementById('sortFilter')?.addEventListener('change', sortCars);

// Apply filters
function applyFilters() {
    const search = document.getElementById('carSearch').value;
    const category = document.getElementById('categoryFilter').value;
    const priceRange = document.getElementById('priceFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (category) params.set('category', category);
    if (priceRange) params.set('price_range', priceRange);
    
    window.location.href = 'cars.php?' + params.toString();
}

// Sort cars
function sortCars() {
    const sortBy = document.getElementById('sortFilter').value;
    const grid = document.getElementById('carsGrid');
    const cards = Array.from(grid.children);
    
    cards.sort((a, b) => {
        const aId = parseInt(a.dataset.carId);
        const bId = parseInt(b.dataset.carId);
        const aCar = carsData[aId];
        const bCar = carsData[bId];
        
        switch (sortBy) {
            case 'price-low':
                return aCar.daily_rate - bCar.daily_rate;
            case 'price-high':
                return bCar.daily_rate - aCar.daily_rate;
            case 'make':
                return (aCar.make + ' ' + aCar.model).localeCompare(bCar.make + ' ' + bCar.model);
            case 'year':
                return bCar.year - aCar.year;
            default:
                return 0;
        }
    });
    
    // Re-append sorted cards
    cards.forEach(card => grid.appendChild(card));
}

// Remove specific filter
function removeFilter(filterType) {
    switch (filterType) {
        case 'search':
            document.getElementById('carSearch').value = '';
            break;
        case 'category':
            document.getElementById('categoryFilter').value = '';
            break;
        case 'price':
            document.getElementById('priceFilter').value = '';
            break;
    }
    applyFilters();
}

// Clear all filters
function clearAllFilters() {
    document.getElementById('carSearch').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('priceFilter').value = '';
    window.location.href = 'cars.php';
}

// View car details
function viewCarDetails(carId) {
    currentCarId = carId;
    const car = carsData[carId];
    if (!car) return;
    
    document.getElementById('modalCarTitle').textContent = `${car.make} ${car.model}`;
    
    const content = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="h-64 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-car-side text-8xl text-primary-300"></i>
                </div>
            </div>
            <div>
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-900">Vehicle Details</h4>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div><span class="text-gray-600">Make:</span> ${car.make}</div>
                            <div><span class="text-gray-600">Model:</span> ${car.model}</div>
                            <div><span class="text-gray-600">Year:</span> ${car.year}</div>
                            <div><span class="text-gray-600">Color:</span> ${car.color}</div>
                            <div><span class="text-gray-600">Mileage:</span> ${car.mileage} miles</div>
                            <div><span class="text-gray-600">License:</span> ${car.license_plate}</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Specifications</h4>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div><span class="text-gray-600">Category:</span> ${car.category}</div>
                            <div><span class="text-gray-600">Transmission:</span> ${car.transmission}</div>
                            <div><span class="text-gray-600">Fuel Type:</span> ${car.fuel_type}</div>
                            <div><span class="text-gray-600">Seats:</span> ${car.seats}</div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Pricing</h4>
                        <div class="mt-2">
                            <span class="text-3xl font-bold text-primary-600">$${Number(car.daily_rate).toLocaleString()}</span>
                            <span class="text-gray-500"> / day</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <h4 class="font-semibold text-gray-900 mb-2">Description</h4>
            <p class="text-gray-600">${car.description}</p>
        </div>
    `;
    
    document.getElementById('modalCarContent').innerHTML = content;
    document.getElementById('carModal').classList.remove('hidden');
}

// Close modal
function closeCarModal() {
    document.getElementById('carModal').classList.add('hidden');
    currentCarId = null;
}

// Book car
function bookCar(carId) {
    <?php if (isset($_SESSION['user_id'])): ?>
        window.location.href = `booking.php?car_id=${carId}`;
    <?php else: ?>
        window.location.href = `login.php?redirect=booking.php&car_id=${carId}`;
    <?php endif; ?>
}

// Book from modal
function bookCarFromModal() {
    if (currentCarId) {
        closeCarModal();
        bookCar(currentCarId);
    }
}

// Add car IDs to grid items for sorting
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('carsGrid');
    if (grid) {
        const cards = grid.querySelectorAll('.card');
        cards.forEach((card, index) => {
            // Extract car ID from the buttons or add data attribute
            const viewBtn = card.querySelector('button[onclick*="viewCarDetails"]');
            if (viewBtn) {
                const match = viewBtn.getAttribute('onclick').match(/viewCarDetails\((\d+)\)/);
                if (match) {
                    card.dataset.carId = match[1];
                }
            }
        });
    }
});

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Close modal when clicking outside
document.getElementById('carModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCarModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>