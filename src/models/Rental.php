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
    
    /**
     * Update booking status (alias for updateRentalStatus)
     */
    public function updateBookingStatus($bookingId, $status, $notes = null) {
        return $this->updateRentalStatus($bookingId, $status, $notes);
    }
    
    /**
     * Delete booking
     */
    public function deleteBooking($bookingId) {
        try {
            $this->db->beginTransaction();
            
            // Get rental details
            $rental = $this->getRentalById($bookingId);
            if (!$rental) {
                $this->db->rollBack();
                return false;
            }
            
            // Only delete bookings that are not active
            if (in_array($rental['status'], ['active'])) {
                $this->db->rollBack();
                return false;
            }
            
            // Delete rental
            $this->db->query("DELETE FROM rentals WHERE id = :id");
            $this->db->bind(':id', $bookingId);
            
            if ($this->db->execute()) {
                // Update car status if rental was confirmed but not active
                if (in_array($rental['status'], ['pending', 'confirmed'])) {
                    $this->db->query("UPDATE cars SET status = 'available' WHERE id = :car_id");
                    $this->db->bind(':car_id', $rental['car_id']);
                    $this->db->execute();
                }
                
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Delete booking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get bookings with filters (admin)
     */
    public function getBookingsWithFilters($search = '', $status = '', $carId = 0, $userId = 0, $startDate = '', $endDate = '', $limit = 20, $offset = 0) {
        $sql = "SELECT r.*, u.name as user_name, u.email as user_email,
                        c.make as car_make, c.model as car_model, c.license_plate as car_license_plate
                 FROM rentals r
                 JOIN users u ON r.user_id = u.id
                 JOIN cars c ON r.car_id = c.id
                 WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (r.id LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR c.make LIKE :search OR c.model LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status)) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }
        
        if ($carId > 0) {
            $sql .= " AND r.car_id = :car_id";
            $params[':car_id'] = $carId;
        }
        
        if ($userId > 0) {
            $sql .= " AND r.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if (!empty($startDate)) {
            $sql .= " AND r.start_date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $sql .= " AND r.end_date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Count bookings with filters
     */
    public function countBookingsWithFilters($search = '', $status = '', $carId = 0, $userId = 0, $startDate = '', $endDate = '') {
        $sql = "SELECT COUNT(*) as count
                 FROM rentals r
                 JOIN users u ON r.user_id = u.id
                 JOIN cars c ON r.car_id = c.id
                 WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (r.id LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR c.make LIKE :search OR c.model LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status)) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }
        
        if ($carId > 0) {
            $sql .= " AND r.car_id = :car_id";
            $params[':car_id'] = $carId;
        }
        
        if ($userId > 0) {
            $sql .= " AND r.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if (!empty($startDate)) {
            $sql .= " AND r.start_date >= :start_date";
            $params[':start_date'] = $startDate;
        }
        
        if (!empty($endDate)) {
            $sql .= " AND r.end_date <= :end_date";
            $params[':end_date'] = $endDate;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get booking statistics for admin dashboard
     */
    public function getBookingStatistics() {
        $stats = [];
        
        // Total bookings
        $this->db->query("SELECT COUNT(*) as count FROM rentals");
        $result = $this->db->single();
        $stats['total_bookings'] = $result['count'] ?? 0;
        
        // Pending bookings
        $this->db->query("SELECT COUNT(*) as count FROM rentals WHERE status = 'pending'");
        $result = $this->db->single();
        $stats['pending_bookings'] = $result['count'] ?? 0;
        
        // Confirmed bookings
        $this->db->query("SELECT COUNT(*) as count FROM rentals WHERE status = 'confirmed'");
        $result = $this->db->single();
        $stats['confirmed_bookings'] = $result['count'] ?? 0;
        
        // Today's bookings
        $this->db->query("SELECT COUNT(*) as count FROM rentals WHERE DATE(start_date) = CURDATE()");
        $result = $this->db->single();
        $stats['today_bookings'] = $result['count'] ?? 0;
        
        return $stats;
    }
}