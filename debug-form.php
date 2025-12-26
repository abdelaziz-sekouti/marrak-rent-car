<?php
// Debug script to test form submission
require_once __DIR__ . '/../includes/init.php';

// Log all request data
echo "=== DEBUG: Form Submission Test ===\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data received: " . (empty($_POST) ? 'NO' : 'YES') . "\n";

if (!empty($_POST)) {
    echo "POST contents:\n";
    print_r($_POST);
} else {
    echo "No POST data received.\n";
}

echo "GET data:\n";
print_r($_GET);

echo "Session data (flash messages):\n";
if (isset($_SESSION['flash_message'])) {
    echo "Flash message: " . $_SESSION['flash_message'] . "\n";
    echo "Flash type: " . $_SESSION['flash_type'] . "\n";
} else {
    echo "No flash messages\n";
}

echo "=== END DEBUG ===\n";
?>