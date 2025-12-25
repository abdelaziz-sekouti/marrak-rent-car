-- Car Rental Database Migration Script
-- Creates initial database structure for car rental system

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS car_rental_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_rental_db;

-- Disable foreign key checks for migration
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (for clean migration)
DROP TABLE IF EXISTS rentals;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin', 'staff') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create cars table
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20) NOT NULL UNIQUE,
    category ENUM('economy', 'compact', 'midsize', 'fullsize', 'luxury', 'suv', 'van', 'sports') NOT NULL,
    daily_rate DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance', 'unavailable') DEFAULT 'available',
    mileage INT DEFAULT 0,
    color VARCHAR(30),
    fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid') DEFAULT 'gasoline',
    transmission ENUM('manual', 'automatic') DEFAULT 'automatic',
    seats INT DEFAULT 5,
    image_url VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_make_model (make, model),
    INDEX idx_license_plate (license_plate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert sample data
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin User', 'admin@rental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890', 'admin'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567891', 'customer'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567892', 'customer');

INSERT INTO cars (make, model, year, license_plate, category, daily_rate, status, mileage, color, fuel_type, transmission, seats, description) VALUES
('Toyota', 'Camry', 2023, 'ABC123', 'midsize', 45.00, 'available', 15000, 'Silver', 'gasoline', 'automatic', 5, 'Comfortable and reliable midsize sedan'),
('Honda', 'Civic', 2023, 'DEF456', 'compact', 35.00, 'available', 12000, 'Blue', 'gasoline', 'automatic', 5, 'Fuel-efficient compact car'),
('Ford', 'Mustang', 2022, 'GHI789', 'sports', 85.00, 'available', 8000, 'Red', 'gasoline', 'manual', 4, 'Powerful sports car with thrilling performance'),
('Tesla', 'Model 3', 2023, 'JKL012', 'luxury', 95.00, 'available', 5000, 'White', 'electric', 'automatic', 5, 'Premium electric sedan with autopilot'),
('Chevrolet', 'Tahoe', 2023, 'MNO345', 'suv', 75.00, 'available', 10000, 'Black', 'gasoline', 'automatic', 8, 'Spacious SUV perfect for families');