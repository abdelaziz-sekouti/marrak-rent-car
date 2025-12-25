<?php
/**
 * Database Migration Script
 * Adds missing status column to users table
 */

require_once __DIR__ . '/../includes/db.php';

try {
    echo "Starting database migration...\n";
    
    // Initialize database connection
    $db = new Database();
    
    // Check if status column already exists in users table
    $checkSql = "SHOW COLUMNS FROM users LIKE 'status'";
    $result = $db->executeQuery($checkSql);
    
    if ($result->rowCount() > 0) {
        echo "Status column already exists in users table.\n";
    } else {
        // Add status column to users table
        $alterSql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER role";
        
        if ($db->executeQuery($alterSql)) {
            echo "Successfully added 'status' column to users table.\n";
        } else {
            echo "Failed to add 'status' column to users table.\n";
            exit(1);
        }
    }
    
    // Update existing users to have active status
    $updateSql = "UPDATE users SET status = 'active' WHERE status IS NULL";
    $db->executeQuery($updateSql);
    echo "Updated existing users with active status.\n";
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>