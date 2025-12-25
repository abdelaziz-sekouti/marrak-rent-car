<?php
require_once 'includes/init.php';
require_once 'src/models/Car.php';

echo "<h1>Testing Car Model</h1>";

try {
    $carModel = new Car();
    echo "<p>✅ Car model created successfully!</p>";
    
    // Test getting all cars
    $cars = $carModel->getAllCars();
    echo "<p>✅ Retrieved " . count($cars) . " cars from database</p>";
    
    // Test search
    $searchResults = $carModel->searchCars('', []);
    echo "<p>✅ Search method works: " . count($searchResults) . " results</p>";
    
    // Test categories
    $categories = $carModel->getCategories();
    echo "<p>✅ Categories retrieved: " . count($categories) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<h2>Database Test</h2>";
try {
    $db = new Database();
    $db->query("SELECT COUNT(*) as count FROM cars");
    $result = $db->single();
    echo "<p>✅ Database query successful: " . $result['count'] . " cars in DB</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>