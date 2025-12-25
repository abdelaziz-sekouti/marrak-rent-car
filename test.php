<?php
require_once 'includes/init.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = new Database();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $db->query("SELECT COUNT(*) as count FROM users");
    $result = $db->single();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Status</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>BASE_URL: " . BASE_URL . "</p>";

echo "<h2>Constants</h2>";
echo "<p>BASE_URL defined: " . (defined('BASE_URL') ? 'Yes' : 'No') . "</p>";
echo "<p>ROOT_PATH: " . ROOT_PATH . "</p>";
?>