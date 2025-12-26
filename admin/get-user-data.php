<?php
require_once __DIR__ . '/../includes/init.php';

// Require admin authentication
requireAdmin();

require_once __DIR__ . '/../src/models/User.php';

header('Content-Type: application/json');

$userId = intval($_GET['id'] ?? 0);
$userModel = new User();

$user = $userModel->getUserById($userId);
if ($user) {
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
}
?>