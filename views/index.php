<?php 
// Load header and navigation
require_once '../includes/header.php';
?>

<main class="flex-grow">
    <!-- Hero Section -->
    <section class=" bg-linear-to-bl from-violet-500 to-fuchsia-500 text-white py-20 relative overflow-hidden hero-background">
        <!-- Animated background elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-32 h-32 bg-white opacity-5 rounded-full animate-float"></div>
            <div class="absolute top-20 right-20 w-24 h-24 bg-white opacity-5 rounded-full animate-float" style="animation-delay: 1s;"></div>
            <div class="absolute bottom-10 left-1/4 w-16 h-16 bg-white opacity-5 rounded-full animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 right-1/3 w-20 h-20 bg-white opacity-5 rounded-full animate-float" style="animation-delay: 0.5s;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div class="text-center lg:text-left">
                    <div class="hero-title">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                            Find Your <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-yellow-400">Perfect Ride</span>
                        </h1>
                    </div>
                    <div class="hero-subtitle">
                        <p class="text-xl md:text-2xl mb-8 text-gray-100 max-w-lg mx-auto lg:mx-0">
                            Premium car rental services with competitive prices and exceptional customer service
                        </p>
                    </div>
                    <div class="hero-buttons">
                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn bg-white text-blue-600 hover:bg-gray-100 px-8 py-4 text-lg font-semibold hover-lift">
                                <i class="fas fa-search mr-2"></i>
                                Browse Cars
                            </a>
                            <a href="<?php echo BASE_URL; ?>/views/register.php" class="btn  bg-indigo-500 border-white text-white hover:bg-white hover:text-blue-600 px-8 py-4 text-lg font-semibold hover-lift">
                                <i class="fas fa-user-plus mr-2"></i>
                                Sign Up
                            </a>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-12">
                        <div class="text-center animate-bounce-in" style="animation-delay: 0.8s;">
                            <div class="text-3xl font-bold text-yellow-300 counter-animate" data-count="500">0+</div>
                            <div class="text-sm text-gray-200">Vehicles</div>
                        </div>
                        <div class="text-center animate-bounce-in" style="animation-delay: 1s;">
                            <div class="text-3xl font-bold text-yellow-300 counter-animate" data-count="10000">0+</div>
                            <div class="text-sm text-gray-200">Happy Customers</div>
                        </div>
                        <div class="text-center animate-bounce-in" style="animation-delay: 1.2s;">
                            <div class="text-3xl font-bold text-yellow-300">
                                <i class="fas fa-star"></i> <span class="counter-animate" data-count="5">0</span>
                            </div>
                            <div class="text-sm text-gray-200">Rating</div>
                        </div>
                    </div>
                </div>
                
                <!-- Hero Car Animation -->
                <div class="relative hidden lg:block">
                    <div class="hero-car relative">
                        <!-- Car silhouette (using icon as placeholder) -->
                        <div class="text-center">
                            <i class="fas fa-car-side text-9xl text-white opacity-80 animate-glow"></i>
                        </div>
                        
                        <!-- Floating elements around car -->
                        <div class="absolute -top-4 -right-4 bg-yellow-300 text-primary-800 rounded-full w-12 h-12 flex items-center justify-center font-bold animate-float">
                            NEW
                        </div>
                        <div class="absolute -bottom-4 -left-4 bg-green-400 text-white rounded-full w-16 h-16 flex items-center justify-center font-bold animate-float" style="animation-delay: 0.5s;">
                            ECO
                        </div>
                        <div class="absolute top-1/2 -right-8 bg-white text-primary-600 rounded-full w-10 h-10 flex items-center justify-center animate-float" style="animation-delay: 1s;">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom wave animation -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg class="w-full h-16 text-gray-50 fill-current" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path d="M0,50 C360,100 1080,0 1440,50 L1440,100 L0,100 Z" class="animate-pulse"></path>
            </svg>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 scroll-animate">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose Marrak Rent Car?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    We provide exceptional car rental services with a focus on customer satisfaction and convenience
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="text-center group scroll-animate hover-lift" style="animation-delay: 0.1s;">
                    <div class="bg-primary-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-200 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-car text-primary-600 text-2xl group-hover:rotate-12 transition-transform"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Wide Selection</h3>
                    <p class="text-gray-600">Choose from our diverse fleet of well-maintained vehicles to suit your needs and budget</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="text-center group scroll-animate hover-lift" style="animation-delay: 0.2s;">
                    <div class="bg-primary-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-200 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-dollar-sign text-primary-600 text-2xl group-hover:rotate-12 transition-transform"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Competitive Prices</h3>
                    <p class="text-gray-600">Enjoy affordable rates with no hidden fees and transparent pricing</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="text-center group scroll-animate hover-lift" style="animation-delay: 0.3s;">
                    <div class="bg-primary-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 group-hover:bg-primary-200 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-headset text-primary-600 text-2xl group-hover:rotate-12 transition-transform"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Our dedicated customer service team is available around the clock to assist you</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Animated Car Showcase Section -->
    <section class="py-16 bg-gray-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 scroll-animate">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Featured Cars</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Experience our premium fleet with cutting-edge vehicles</p>
            </div>
            
            <!-- Car Carousel -->
            <div class="relative">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Car Card 1 - Featured -->
                    <div class="card group scroll-animate relative overflow-hidden" style="animation-delay: 0.1s;">
                        <!-- Featured Badge -->
                        <div class="absolute top-4 right-4 z-10">
                            <span class="bg-yellow-400 text-primary-800 px-3 py-1 rounded-full text-sm font-bold animate-pulse-glow">
                                <i class="fas fa-star mr-1"></i> Featured
                            </span>
                        </div>
                        
                        <!-- Car Image with hover effect -->
                        <div class="relative h-48 bg-gradient-to-br from-primary-100 to-primary-200 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-car-side text-6xl text-primary-300 group-hover:scale-110 transition-transform duration-300"></i>
                            </div>
                            <!-- Shimmer effect -->
                            <div class="absolute inset-0 shimmer"></div>
                        </div>
                        
                        <div class="card-body">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-semibold">Toyota Camry</h3>
                                <div class="flex items-center text-yellow-400">
                                    <i class="fas fa-star text-sm"></i>
                                    <span class="text-gray-600 text-sm ml-1">4.8</span>
                                </div>
                            </div>
                            
                            <!-- Car Features -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">Automatic</span>
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">5 Seats</span>
                                <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs">Available</span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">Comfortable and reliable midsize sedan</p>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-2xl font-bold text-primary-600">$45</span>
                                    <span class="text-gray-500 text-sm">/day</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/views/car-details.php?id=1" class="btn btn-primary hover-lift">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Car Card 2 -->
                    <div class="card group scroll-animate" style="animation-delay: 0.2s;">
                        <div class="relative h-48 bg-gradient-to-br from-secondary-100 to-secondary-200 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-car-side text-6xl text-secondary-300 group-hover:scale-110 transition-transform duration-300"></i>
                            </div>
                            <div class="absolute inset-0 shimmer"></div>
                        </div>
                        
                        <div class="card-body">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-semibold">Honda Civic</h3>
                                <div class="flex items-center text-yellow-400">
                                    <i class="fas fa-star text-sm"></i>
                                    <span class="text-gray-600 text-sm ml-1">4.6</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">Automatic</span>
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">5 Seats</span>
                                <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs">Available</span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">Fuel-efficient compact car</p>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-2xl font-bold text-primary-600">$35</span>
                                    <span class="text-gray-500 text-sm">/day</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/views/car-details.php?id=2" class="btn btn-primary hover-lift">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Car Card 3 - Premium -->
                    <div class="card group scroll-animate relative overflow-hidden" style="animation-delay: 0.3s;">
                        <!-- Premium Badge -->
                        <div class="absolute top-4 right-4 z-10">
                            <span class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-bold animate-glow">
                                <i class="fas fa-crown mr-1"></i> Premium
                            </span>
                        </div>
                        
                        <div class="relative h-48 bg-gradient-to-br from-purple-100 to-pink-100 overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-car-side text-6xl text-purple-300 group-hover:scale-110 transition-transform duration-300"></i>
                            </div>
                            <div class="absolute inset-0 shimmer"></div>
                        </div>
                        
                        <div class="card-body">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-semibold">Tesla Model 3</h3>
                                <div class="flex items-center text-yellow-400">
                                    <i class="fas fa-star text-sm"></i>
                                    <span class="text-gray-600 text-sm ml-1">4.9</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">Automatic</span>
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">5 Seats</span>
                                <span class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">Electric</span>
                                <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs">Available</span>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">Premium electric sedan with autopilot</p>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-2xl font-bold text-primary-600">$95</span>
                                    <span class="text-gray-500 text-sm">/day</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/views/car-details.php?id=4" class="btn btn-primary hover-lift">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-12 scroll-animate" style="animation-delay: 0.4s;">
                <a href="<?php echo BASE_URL; ?>/views/cars.php" class="btn btn-outline btn-lg hover-lift">
                    <i class="fas fa-th-large mr-2"></i>
                    View All Cars <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>