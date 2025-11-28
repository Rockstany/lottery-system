<?php
/**
 * User Model
 * Handle user authentication and management
 */

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Authenticate user with mobile and password
     * @param string $mobile
     * @param string $password
     * @return array|false
     */
    public function authenticate($mobile, $password) {
        try {
            $query = "SELECT user_id, mobile_number, password_hash, full_name, email, role, status
                      FROM {$this->table}
                      WHERE mobile_number = :mobile AND status = 'active'
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                return false;
            }

            $user = $stmt->fetch();

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }

            // Update last login
            $this->updateLastLogin($user['user_id']);

            // Log activity
            $this->logActivity($user['user_id'], 'login', 'User logged in successfully');

            return $user;

        } catch (PDOException $e) {
            error_log("Authentication Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new user
     * @param array $data
     * @return int|false User ID on success
     */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table}
                      (mobile_number, password_hash, full_name, email, role, status)
                      VALUES (:mobile, :password, :name, :email, :role, :status)";

            $stmt = $this->db->prepare($query);

            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

            $stmt->bindParam(':mobile', $data['mobile_number']);
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindValue(':status', $data['status'] ?? 'active');

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("User Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     * @param int $userId
     * @return array|false
     */
    public function getById($userId) {
        try {
            $query = "SELECT user_id, mobile_number, full_name, email, role, status, created_at, last_login
                      FROM {$this->table}
                      WHERE user_id = :user_id
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Get User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if mobile number exists
     * @param string $mobile
     * @return bool
     */
    public function mobileExists($mobile) {
        try {
            $query = "SELECT user_id FROM {$this->table} WHERE mobile_number = :mobile LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':mobile', $mobile);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Mobile Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function update($userId, $data) {
        try {
            $query = "UPDATE {$this->table}
                      SET full_name = :name,
                          email = :email,
                          status = :status,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['full_name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("User Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change user password
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword) {
        try {
            $query = "UPDATE {$this->table}
                      SET password_hash = :password,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id";

            $stmt = $this->db->prepare($query);
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Password Change Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with pagination
     * @param int $page
     * @param int $perPage
     * @param string $role (optional filter)
     * @return array
     */
    public function getAll($page = 1, $perPage = ITEMS_PER_PAGE, $role = null) {
        try {
            $offset = ($page - 1) * $perPage;

            $query = "SELECT user_id, mobile_number, full_name, email, role, status, created_at, last_login
                      FROM {$this->table}";

            if ($role) {
                $query .= " WHERE role = :role";
            }

            $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($query);

            if ($role) {
                $stmt->bindParam(':role', $role);
            }

            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Get All Users Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId) {
        try {
            $query = "UPDATE {$this->table} SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
        }
    }

    /**
     * Log user activity
     * @param int $userId
     * @param string $action
     * @param string $description
     */
    private function logActivity($userId, $action, $description) {
        try {
            $query = "INSERT INTO activity_logs (user_id, action_type, action_description, ip_address, user_agent)
                      VALUES (:user_id, :action, :description, :ip, :user_agent)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }

    /**
     * Get community ID for group admin
     * @param int $userId
     * @return int|null
     */
    public function getCommunityId($userId) {
        try {
            $query = "SELECT community_id FROM group_admin_assignments WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result ? $result['community_id'] : null;

        } catch (PDOException $e) {
            error_log("Get Community ID Error: " . $e->getMessage());
            return null;
        }
    }
}
?>
