<?php
require_once __DIR__ . '/../../includes/init.php';

class Rental {
    public $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create a new rental
     */
    public function createRental($userId, $carId, $startDate, $endDate, $totalCost, $pickupLocation, $dropoffLocation) {
        try {
            $this->db->beginTransaction();
            
            // Check if car is available
            $carModel = new Car();
            if (!$carModel->isAvailable($carId, $startDate, $endDate)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Car is not available for the selected dates'];
            }
            
            // Create rental
            $sql = "INSERT INTO rentals (user_id, car_id, start_date, end_date, total_cost, pickup_location, dropoff_location, status) 
                    VALUES (:user_id, :car_id, :start_date, :end_date, :total_cost, :pickup_location, :dropoff_location, 'pending')";
            
            $this->db->query($sql);
            
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':car_id', $carId);
            $this->db->bind(':start_date', $startDate);
            $this->db->bind(':end_date', $endDate);
            $this->db->bind(':total_cost', $totalCost);
            $this->db->bind(':pickup_location', $pickupLocation);
            $this->db->bind(':dropoff_location', $dropoffLocation);
            
            if ($this->db->execute()) {
                $rentalId = $this->db->lastInsertId();
                
                // Update car status
                $this->db->query("UPDATE cars SET status = 'rented' WHERE id = :car_id");
                $this->db->bind(':car_id', $carId);
                $this->db->execute();
                
                $this->db->commit();
                return ['success' => true, 'rental_id' => $rentalId, 'message' => 'Rental created successfully'];
            } else {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to create rental'];
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Rental creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Get rental by ID
     */
    public function getRentalById($id) {
        $sql = "SELECT r.*, u.name as user_name, u.email as user_email, 
                c.make, c.model, c.year, c.daily_rate 
                FROM rentals r 
                JOIN users u ON r.user_id = u.id 
                JOIN cars c ON r.car_id = c.id 
                WHERE r.id = :id";
        
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Get user rentals
     */
    public function getUserRentals($userId, $status = null) {
        $sql = "SELECT r.*, c.make, c.model, c.year, c.image_url 
                FROM rentals r 
                JOIN cars c ON r.car_id = c.id 
                WHERE r.user_id = :user_id";
        
        $params = [':user_id' => $userId];
        
        if ($status) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Get all rentals (admin)
     */
    public function getAllRentals($status = null, $limit = null) {
        $sql = "SELECT r.*, u.name as user_name, u.email as user_email, 
                c.make, c.model, c.year, c.license_plate 
                FROM rentals r 
                JOIN users u ON r.user_id = u.id 
                JOIN cars c ON r.car_id = c.id 
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        }
        
        return $this->db->resultSet();
    }
    
    /**
     * Update rental status
     */
    public function updateRentalStatus($rentalId, $status, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Get rental details
            $rental = $this->getRentalById($rentalId);
            if (!$rental) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Rental not found'];
            }
            
            // Update rental
            $sql = "UPDATE rentals SET status = :status";
            $params = [':status' => $status, ':id' => $rentalId];
            
            if ($notes) {
                $sql .= ", notes = :notes";
                $params[':notes'] = $notes;
            }
            
            if ($status === 'completed') {
                $sql .= ", end_date = NOW()";
            }
            
            $sql .= " WHERE id = :id";
            
            $this->db->query($sql);
            
            foreach ($params as $key => $value) {
                $this->db->bind($key, $value);
            }
            
            if ($this->db->execute()) {
                // Update car status if rental is completed or cancelled
                if ($status === 'completed' || $status === 'cancelled') {
                    $this->db->query("UPDATE cars SET status = 'available' WHERE id = :car_id");
                    $this->db->bind(':car_id', $rental['car_id']);
                    $this->db->execute();
                }
                
                $this->db->commit();
                return ['success' => true, 'message' => 'Rental status updated successfully'];
            } else {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Failed to update rental status'];
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Rental status update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    /**
     * Cancel rental
     */
    public function cancelRental($rentalId, $reason = null) {
        return $this->updateRentalStatus($rentalId, 'cancelled', $reason);
    }
    
    /**
     * Calculate rental cost
     */
    public function calculateCost($carId, $startDate, $endDate) {
        // Get car daily rate
        $this->db->query("SELECT daily_rate FROM cars WHERE id = :car_id");
        $this->db->bind(':car_id', $carId);
        $car = $this->db->single();
        
        if (!$car) {
            return 0;
        }
        
        // Calculate days
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = $end->diff($start)->days + 1; // Include both start and end day
        
        return $car['daily_rate'] * $days;
    }
    
    /**
     * Check rental availability
     */
    public function checkAvailability($carId, $startDate, $endDate, $excludeRentalId = null) {
        $carModel = new Car();
        return $carModel->isAvailable($carId, $startDate, $endDate, $excludeRentalId);
    }
    
    /**
     * Get rental statistics
     */
    public function getStatistics($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(*) as total_rentals,
                    SUM(total_cost) as total_revenue,
                    AVG(total_cost) as avg_rental_cost,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_rentals,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_rentals
                FROM rentals WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND start_date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND end_date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->single();
    }
    
    /**
     * Validate rental data
     */
    public function validateRentalData($data) {
        $errors = [];
        
        if (empty($data['car_id'])) $errors['car_id'] = 'Car selection is required';
        if (empty($data['start_date'])) $errors['start_date'] = 'Start date is required';
        if (empty($data['end_date'])) $errors['end_date'] = 'End date is required';
        if (empty($data['pickup_location'])) $errors['pickup_location'] = 'Pickup location is required';
        if (empty($data['dropoff_location'])) $errors['dropoff_location'] = 'Dropoff location is required';
        
        // Date validation
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $start = new DateTime($data['start_date']);
            $end = new DateTime($data['end_date']);
            $today = new DateTime();
            
            if ($start < $today) {
                $errors['start_date'] = 'Start date cannot be in the past';
            }
            
            if ($end < $start) {
                $errors['end_date'] = 'End date must be after start date';
            }
            
            $days = $end->diff($start)->days + 1;
            if ($days > 30) {
                $errors['end_date'] = 'Rental period cannot exceed 30 days';
            }
        }
        
        return $errors;
    }
}