<?php
require_once __DIR__ . '/../../includes/init.php';

class User {
    public $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Register a new user
     */
    public function register($name, $email, $password, $phone = null, $role = 'customer') {
        // Check if email already exists
        if ($this->getUserByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $this->db->query("INSERT INTO users (name, email, password, phone, role) VALUES (:name, :email, :password, :phone, :role)");
            
            $this->db->bind(':name', $name);
            $this->db->bind(':email', $email);
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':phone', $phone);
            $this->db->bind(':role', $role);
            
            if ($this->db->execute()) {
                $userId = $this->db->lastInsertId();
                return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from session data
            unset($user['password']);
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $this->db->query("SELECT id, name, email, phone, role, created_at FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $this->db->query("SELECT id, name, email, phone, role FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        
        return $this->db->single();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($id, $name, $email, $phone) {
        try {
            $this->db->query("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
            
            $this->db->bind(':name', $name);
            $this->db->bind(':email', $email);
            $this->db->bind(':phone', $phone);
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update password
     */
    public function updatePassword($id, $currentPassword, $newPassword) {
        // Verify current password
        $this->db->query("SELECT password FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        $user = $this->db->single();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $this->db->query("UPDATE users SET password = :password WHERE id = :id");
            $this->db->bind(':password', $hashedPassword);
            $this->db->bind(':id', $id);
            
            if ($this->db->execute()) {
                return ['success' => true, 'message' => 'Password updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Validate user input
     */
    public function validateRegistration($data) {
        $errors = [];
        
        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // Phone validation (optional)
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        }
        
        return $errors;
    }
    
    /**
     * Validate login input
     */
    public function validateLogin($data) {
        $errors = [];
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate user data for admin operations
     */
    public function validateUserData($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        // Check if email already exists (exclude current user during edit)
        $existingUser = $this->getUserByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $excludeId) {
            $errors['email'] = 'Email already exists';
        }
        
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Invalid phone number format';
        }
        
        if (!in_array($data['role'], ['customer', 'staff', 'admin'])) {
            $errors['role'] = 'Invalid role';
        }
        
        return $errors;
    }
    
    /**
     * Update user (admin only)
     */
    public function updateUser($id, $data) {
        try {
            $this->db->query("UPDATE users SET name = :name, email = :email, phone = :phone, role = :role WHERE id = :id");
            
            $this->db->bind(':name', $data['name']);
            $this->db->bind(':email', $data['email']);
            $this->db->bind(':phone', $data['phone']);
            $this->db->bind(':role', $data['role']);
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete user (admin only)
     */
    public function deleteUser($id) {
        try {
            $this->db->query("DELETE FROM users WHERE id = :id");
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle user status (active/inactive)
     */
    public function toggleUserStatus($id) {
        try {
            $this->db->query("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = :id");
            $this->db->bind(':id', $id);
            
            return $this->db->execute();
        } catch (PDOException $e) {
            error_log("Toggle user status error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users with filters for admin panel
     */
    public function getUsersWithFilters($search = '', $role = '', $status = '', $limit = 20, $offset = 0) {
        $sql = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($role)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }
    
    /**
     * Count users with filters
     */
    public function countUsersWithFilters($search = '', $role = '', $status = '') {
        $sql = "SELECT COUNT(*) as count FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($role)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $this->db->query($sql);
        
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        $result = $this->db->single();
        return $result['count'] ?? 0;
    }
    
    /**
     * Get user statistics for admin dashboard
     */
    public function getUserStatistics() {
        $stats = [];
        
        // Total users
        $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $this->db->single();
        $stats['total'] = $result['count'] ?? 0;
        
        // Customers
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
        $result = $this->db->single();
        $stats['customers'] = $result['count'] ?? 0;
        
        // Staff (including admin)
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('staff', 'admin')");
        $result = $this->db->single();
        $stats['staff'] = $result['count'] ?? 0;
        
        // Active today (users who logged in today or were created today)
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE() OR DATE(updated_at) = CURDATE()");
        $result = $this->db->single();
        $stats['active_today'] = $result['count'] ?? 0;
        
        return $stats;
    }
}