<?php
/**
 * Configuration constants only - no session code
 */
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/marrak-rent-car');
    define('ROOT_PATH', __DIR__ . '/..');
    
    // Google Maps API Key (replace with your actual API key)
    // Get your key from: https://console.cloud.google.com/
    // Free tier: 28,000 map loads/month, $200 credit
    // Test key - replace with your own
    define('GOOGLE_MAPS_API_KEY', 'AIzaSyBF1nE8q7hKz7jX9e3mRf8Lq2vC6W3nG9Y');
}
?>