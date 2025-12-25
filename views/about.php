<?php 
require_once '../includes/init.php';
require_once '../includes/header.php'; 

$page_title = 'About Us - Marrak Rent Car';
$page_description = 'Learn about Marrak Rent Car - your trusted partner for premium car rental services in Marrakech, Morocco';
?>

<main class="flex-grow py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                About <span class="text-blue-600">Marrak Rent Car</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Your trusted partner for premium car rental services in the heart of Marrakech
            </p>
        </div>

        <!-- About Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">
            <!-- Our Story -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="flex items-center mb-6">
                    <i class="fas fa-history text-3xl text-blue-600 mr-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Our Story</h2>
                </div>
                <p class="text-gray-600 leading-relaxed mb-4">
                    Founded in the heart of Marrakech, Marrak Rent Car has been serving both locals and tourists with reliable, affordable, and high-quality vehicle rental services for over a decade. What started as a small family business has grown into one of the most trusted names in Moroccan car rentals.
                </p>
                <p class="text-gray-600 leading-relaxed">
                    We understand the unique needs of travelers exploring Morocco's vibrant cities, winding mountain roads, and vast desert landscapes. That's why every vehicle in our fleet is carefully selected, regularly maintained, and ready for any adventure you have in mind.
                </p>
            </div>

            <!-- Our Mission -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="flex items-center mb-6">
                    <i class="fas fa-bullseye text-3xl text-blue-600 mr-4"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Our Mission</h2>
                </div>
                <p class="text-gray-600 leading-relaxed mb-4">
                    To provide exceptional car rental experiences that combine affordability, reliability, and authentic Moroccan hospitality. We believe every journey should start with confidence and comfort.
                </p>
                <ul class="text-gray-600 space-y-3">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <span>Well-maintained, modern fleet of vehicles</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <span>Transparent pricing with no hidden fees</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <span>24/7 customer support for peace of mind</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                        <span>Local knowledge and travel tips</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Why Choose Us -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-8 mb-16">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Why Choose Marrak Rent Car?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Quality Vehicles -->
                <div class="text-center">
                    <div class="bg-blue-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-car text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Quality Vehicles</h3>
                    <p class="text-gray-600">Modern, well-maintained cars perfect for Moroccan roads and terrain</p>
                </div>

                <!-- Best Prices -->
                <div class="text-center">
                    <div class="bg-green-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tag text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Best Prices</h3>
                    <p class="text-gray-600">Competitive rates with no hidden fees or surprise charges</p>
                </div>

                <!-- Local Support -->
                <div class="text-center">
                    <div class="bg-purple-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Always here to help with directions, recommendations, and assistance</p>
                </div>

                <!-- Easy Booking -->
                <div class="text-center">
                    <div class="bg-orange-600 text-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Easy Booking</h3>
                    <p class="text-gray-600">Simple online reservation process with instant confirmation</p>
                </div>
            </div>
        </div>

        <!-- Our Fleet Preview -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Our Fleet</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Economy Cars -->
                <div class="card hover-lift">
                    <div class="bg-gradient-to-br from-blue-100 to-blue-50 p-6 rounded-t-lg">
                        <i class="fas fa-car text-3xl text-blue-600 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Economy Cars</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Perfect for city driving and budget-conscious travelers. Fuel-efficient and easy to park.</p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Renault Clio, Dacia Sandero</li>
                            <li>• 4-5 passengers, AC, manual/auto</li>
                            <li>• From 200 MAD/day</li>
                        </ul>
                        <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn btn-primary w-full mt-4">
                            View Economy Cars
                        </a>
                    </div>
                </div>

                <!-- Family Cars -->
                <div class="card hover-lift">
                    <div class="bg-gradient-to-br from-green-100 to-green-50 p-6 rounded-t-lg">
                        <i class="fas fa-car-side text-3xl text-green-600 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Family Cars</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Spacious and comfortable for families and groups traveling together.</p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Dacia Logan, Dacia Duster</li>
                            <li>• 5-7 passengers, AC, auto</li>
                            <li>• From 350 MAD/day</li>
                        </ul>
                        <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn btn-primary w-full mt-4">
                            View Family Cars
                        </a>
                    </div>
                </div>

                <!-- SUVs & 4x4 -->
                <div class="card hover-lift">
                    <div class="bg-gradient-to-br from-orange-100 to-orange-50 p-6 rounded-t-lg">
                        <i class="fas fa-truck-pickup text-3xl text-orange-600 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">SUVs & 4x4</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Perfect for desert tours and mountain adventures with ample space.</p>
                        <ul class="text-sm text-gray-500 space-y-1">
                            <li>• Toyota Land Cruiser, Nissan Patrol</li>
                            <li>• 5-8 passengers, 4WD, AC</li>
                            <li>• From 600 MAD/day</li>
                        </ul>
                        <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn btn-primary w-full mt-4">
                            View SUVs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-16">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Meet Our Team</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Team Member 1 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-user-tie text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Ahmed Hassan</h3>
                    <p class="text-blue-600 font-medium mb-2">Founder & CEO</p>
                    <p class="text-gray-600">With over 15 years in Morocco's tourism industry, Ahmed ensures every customer feels like family.</p>
                </div>

                <!-- Team Member 2 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-user-tie text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Fatima Zahra</h3>
                    <p class="text-blue-600 font-medium mb-2">Operations Manager</p>
                    <p class="text-gray-600">Ensuring our fleet is always ready and our customers have smooth experiences.</p>
                </div>

                <!-- Team Member 3 -->
                <div class="text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-user-tie text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Youssef Omar</h3>
                    <p class="text-blue-600 font-medium mb-2">Customer Relations</p>
                    <p class="text-gray-600">Always ready to help with recommendations, directions, and travel tips.</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-lg p-12 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to Explore Morocco?</h2>
            <p class="text-xl text-blue-100 mb-8">
                Book your perfect vehicle today and start your Moroccan adventure with Marrak Rent Car
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn bg-white text-blue-600 hover:bg-gray-100 px-8 py-3 text-lg">
                    <i class="fas fa-car mr-2"></i>
                    Browse Our Fleet
                </a>
                <a href="<?php echo BASE_URL; ?>/views/contact.php" class="btn border-2 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-3 text-lg">
                    <i class="fas fa-phone mr-2"></i>
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>