</main>
    
    <!-- Footer -->
    <footer class="bg-secondary-800 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-car text-2xl text-primary-400 mr-3"></i>
                        <h3 class="text-xl font-bold">Marrak Rent Car</h3>
                    </div>
                    <p class="text-gray-300 mb-4">
                        Your trusted partner for premium car rental services. We offer a wide range of vehicles to suit your needs and budget.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-primary-400 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?php echo BASE_URL; ?>" class="text-gray-300 hover:text-primary-400 transition-colors">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/views/cars.php" class="text-gray-300 hover:text-primary-400 transition-colors">Cars</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/views/about.php" class="text-gray-300 hover:text-primary-400 transition-colors">About</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/views/contact.php" class="text-gray-300 hover:text-primary-400 transition-colors">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/views/terms.php" class="text-gray-300 hover:text-primary-400 transition-colors">Terms & Conditions</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/views/privacy.php" class="text-gray-300 hover:text-primary-400 transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Info</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-map-marker-alt mr-2 text-primary-400"></i>
                            123 Rental Street, City, State 12345
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-phone mr-2 text-primary-400"></i>
                            +1 (555) 123-4567
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-envelope mr-2 text-primary-400"></i>
                            info@marrakrentcar.com
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-clock mr-2 text-primary-400"></i>
                            Mon-Fri: 8AM-8PM, Sat-Sun: 9AM-6PM
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="border-t border-secondary-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> Marrak Rent Car. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    
    <!-- Mobile menu toggle -->
    <script>
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
        
        // Ensure BASE_URL is available for JavaScript
        const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</body>
</html>