<?php
// Simple migration runner
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    // Read migration file
    $migrationSQL = file_get_contents(__DIR__ . '/database/migration.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    $db = new Database();
    $pdo = $db->getPdo();
    
    echo "Running database migration...\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "✗ Error in statement: " . $e->getMessage() . "\n";
                echo "Statement: " . $statement . "\n";
            }
        }
    }
    
    echo "\nMigration completed!\n";
    echo "Checking tables...\n";
    
    // Check if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}