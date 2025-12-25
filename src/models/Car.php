<?php
require_once __DIR__ . '/../../includes/init.php';

class Car {
    public $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get all cars with optional filtering
     */
    public function getAllCars($category = null, $minPrice = null, $maxPrice = null, $status = 'available') {
        $sql = "SELECT * FROM cars WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        if ($minPrice) {
            $sql .= " AND daily_rate >= :min_price";
            $params[':min_price'] = $minPrice;
        }
        
        if ($maxPrice) {
            $sql .= " AND daily_rate <= :max_price";
            $params[':max_price'] = $maxPrice;
        }
        
        if ($status) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY make, model";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get car by ID
     */
    public function getCarById($id) {
        $this->db->query("SELECT * FROM cars WHERE id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Get featured cars
     */
    public function getFeaturedCars($limit = 6) {
        $this->db->query("SELECT * FROM cars WHERE status = 'available' ORDER BY daily_rate DESC LIMIT :limit");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Search cars
     */
    public function searchCars($searchTerm, $filters = []) {
        // Start with base query
        $sql = "SELECT * FROM cars WHERE 1=1";
        $params = [];
        
        // Add search condition if provided
        if (!empty($searchTerm)) {
            $sql .= " AND (make LIKE :search OR model LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $searchTerm . '%';
        }
        
        // Apply filters
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND daily_rate >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND daily_rate <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
        
        if (!empty($filters['fuel_type'])) {
            $sql .= " AND fuel_type = :fuel_type";
            $params[':fuel_type'] = $filters['fuel_type'];
        }
        
        if (!empty($filters['transmission'])) {
            $sql .= " AND transmission = :transmission";
            $params[':transmission'] = $filters['transmission'];
        }
        
        if (empty($filters['status']) || $filters['status'] === 'available') {
            $sql .= " AND status = 'available'";
        }
        
        $sql .= " ORDER BY make, model";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Check if car is available for dates
     */
    public function isAvailable($carId, $startDate, $endDate, $excludeRentalId = null) {
        $sql = "SELECT COUNT(*) as count FROM rentals 
                WHERE car_id = :car_id 
                AND status != 'cancelled'
                AND ((start_date <= :end_date AND end_date >= :start_date))";
        
        $params = [
            ':car_id' => $carId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ];
        
        if ($excludeRentalId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeRentalId;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result['count'] == 0;
    }
    
    /**
     * Get car categories
     */
    public function getCategories() {
        $this->db->query("SELECT DISTINCT category FROM cars ORDER BY category");
        return $this->db->resultSet();
    }
    
    /**
     * Get price range
     */
    public function getPriceRange() {
        $this->db->query("SELECT MIN(daily_rate) as min_price, MAX(daily_rate) as max_price FROM cars WHERE status = 'available'");
        return $this->db->single();
    }
    
    /**
     * Add new car (admin only)
     */
    public function addCar($data) {
        $sql = "INSERT INTO cars (make, model, year, license_plate, category, daily_rate, status, 
                mileage, color, fuel_type, transmission, seats, description) 
                VALUES (:make, :model, :year, :license_plate, :category, :daily_rate, :status,
                :mileage, :color, :fuel_type, :transmission, :seats, :description)";
        
        $this->db->query($sql);
        
        $this->db->bind(':make', $data['make']);
        $this->db->bind(':model', $data['model']);
        $this->db->bind(':year', $data['year']);
        $this->db->bind(':license_plate', $data['license_plate']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':daily_rate', $data['daily_rate']);
        $this->db->bind(':status', $data['status'] ?? 'available');
        $this->db->bind(':mileage', $data['mileage'] ?? 0);
        $this->db->bind(':color', $data['color']);
        $this->db->bind(':fuel_type', $data['fuel_type']);
        $this->db->bind(':transmission', $data['transmission']);
        $this->db->bind(':seats', $data['seats'] ?? 5);
        $this->db->bind(':description', $data['description']);
        
        return $this->db->execute();
    }
    
    /**
     * Update car (admin only)
     */
    public function updateCar($id, $data) {
        $sql = "UPDATE cars SET make = :make, model = :model, year = :year, license_plate = :license_plate, 
                category = :category, daily_rate = :daily_rate, status = :status, mileage = :mileage, 
                color = :color, fuel_type = :fuel_type, transmission = :transmission, seats = :seats, 
                description = :description WHERE id = :id";
        
        $this->db->query($sql);
        
        $this->db->bind(':make', $data['make']);
        $this->db->bind(':model', $data['model']);
        $this->db->bind(':year', $data['year']);
        $this->db->bind(':license_plate', $data['license_plate']);
        $this->db->bind(':category', $data['category']);
        $this->db->bind(':daily_rate', $data['daily_rate']);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':mileage', $data['mileage']);
        $this->db->bind(':color', $data['color']);
        $this->db->bind(':fuel_type', $data['fuel_type']);
        $this->db->bind(':transmission', $data['transmission']);
        $this->db->bind(':seats', $data['seats']);
        $this->db->bind(':description', $data['description']);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Delete car (admin only)
     */
    public function deleteCar($id) {
        $this->db->query("DELETE FROM cars WHERE id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Validate car data
     */
    public function validateCarData($data) {
        $errors = [];
        
        if (empty($data['make'])) $errors['make'] = 'Make is required';
        if (empty($data['model'])) $errors['model'] = 'Model is required';
        if (empty($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1) {
            $errors['year'] = 'Valid year is required';
        }
        if (empty($data['license_plate'])) $errors['license_plate'] = 'License plate is required';
        if (empty($data['category'])) $errors['category'] = 'Category is required';
        if (empty($data['daily_rate']) || $data['daily_rate'] <= 0) {
            $errors['daily_rate'] = 'Valid daily rate is required';
        }
        
        return $errors;
    }
}