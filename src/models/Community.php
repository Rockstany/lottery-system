<?php
/**
 * Community Model
 * Handle community management operations
 */

class Community {
    private $db;
    private $table = 'communities';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create new community
     * @param array $data
     * @return int|false Community ID on success
     */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table}
                      (community_name, address, city, state, pincode, created_by, status)
                      VALUES (:name, :address, :city, :state, :pincode, :created_by, :status)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['community_name']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':city', $data['city']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':pincode', $data['pincode']);
            $stmt->bindParam(':created_by', $data['created_by']);
            $stmt->bindValue(':status', $data['status'] ?? 'active');

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Community Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get community by ID
     * @param int $communityId
     * @return array|false
     */
    public function getById($communityId) {
        try {
            $query = "SELECT c.*, u.full_name as created_by_name,
                      (SELECT COUNT(*) FROM group_admin_assignments WHERE community_id = c.community_id) as admin_count
                      FROM {$this->table} c
                      LEFT JOIN users u ON c.created_by = u.user_id
                      WHERE c.community_id = :community_id
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Get Community Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all communities with pagination
     * @param int $page
     * @param int $perPage
     * @param string $status (optional filter)
     * @return array
     */
    public function getAll($page = 1, $perPage = ITEMS_PER_PAGE, $status = null) {
        try {
            $offset = ($page - 1) * $perPage;

            $query = "SELECT c.*, u.full_name as created_by_name,
                      (SELECT COUNT(*) FROM group_admin_assignments WHERE community_id = c.community_id) as admin_count
                      FROM {$this->table} c
                      LEFT JOIN users u ON c.created_by = u.user_id";

            if ($status) {
                $query .= " WHERE c.status = :status";
            }

            $query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($query);

            if ($status) {
                $stmt->bindParam(':status', $status);
            }

            $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Get All Communities Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total count of communities
     * @param string $status (optional filter)
     * @return int
     */
    public function getCount($status = null) {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table}";

            if ($status) {
                $query .= " WHERE status = :status";
            }

            $stmt = $this->db->prepare($query);

            if ($status) {
                $stmt->bindParam(':status', $status);
            }

            $stmt->execute();
            $result = $stmt->fetch();

            return $result['total'];

        } catch (PDOException $e) {
            error_log("Get Community Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update community
     * @param int $communityId
     * @param array $data
     * @return bool
     */
    public function update($communityId, $data) {
        try {
            $query = "UPDATE {$this->table}
                      SET community_name = :name,
                          address = :address,
                          city = :city,
                          state = :state,
                          pincode = :pincode,
                          status = :status,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE community_id = :community_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['community_name']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':city', $data['city']);
            $stmt->bindParam(':state', $data['state']);
            $stmt->bindParam(':pincode', $data['pincode']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':community_id', $communityId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Community Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle community status
     * @param int $communityId
     * @return bool
     */
    public function toggleStatus($communityId) {
        try {
            $query = "UPDATE {$this->table}
                      SET status = IF(status = 'active', 'inactive', 'active'),
                          updated_at = CURRENT_TIMESTAMP
                      WHERE community_id = :community_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Toggle Community Status Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if community has assigned group admin
     * @param int $communityId
     * @return bool
     */
    public function hasGroupAdmin($communityId) {
        try {
            $query = "SELECT COUNT(*) as count FROM group_admin_assignments WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("Check Group Admin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get group admin for community
     * @param int $communityId
     * @return array|false
     */
    public function getGroupAdmin($communityId) {
        try {
            $query = "SELECT u.user_id, u.full_name, u.mobile_number, u.email, gaa.assigned_at
                      FROM group_admin_assignments gaa
                      INNER JOIN users u ON gaa.user_id = u.user_id
                      WHERE gaa.community_id = :community_id
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->execute();

            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log("Get Group Admin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign group admin to community
     * @param int $communityId
     * @param int $userId
     * @param int $assignedBy
     * @return bool
     */
    public function assignGroupAdmin($communityId, $userId, $assignedBy) {
        try {
            // Check if user is already assigned to another community
            $checkQuery = "SELECT COUNT(*) as count FROM group_admin_assignments WHERE user_id = :user_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            $result = $checkStmt->fetch();

            if ($result['count'] > 0) {
                return false; // User already assigned to a community
            }

            // Check if community already has an admin
            if ($this->hasGroupAdmin($communityId)) {
                return false; // Community already has an admin
            }

            $query = "INSERT INTO group_admin_assignments (user_id, community_id, assigned_by)
                      VALUES (:user_id, :community_id, :assigned_by)";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->bindParam(':assigned_by', $assignedBy);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Assign Group Admin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unassign group admin from community
     * @param int $communityId
     * @return bool
     */
    public function unassignGroupAdmin($communityId) {
        try {
            $query = "DELETE FROM group_admin_assignments WHERE community_id = :community_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Unassign Group Admin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get communities without group admin
     * @return array
     */
    public function getCommunitiesWithoutAdmin() {
        try {
            $query = "SELECT c.*
                      FROM {$this->table} c
                      LEFT JOIN group_admin_assignments gaa ON c.community_id = gaa.community_id
                      WHERE gaa.assignment_id IS NULL AND c.status = 'active'
                      ORDER BY c.community_name";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Get Communities Without Admin Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update total members count
     * @param int $communityId
     * @return bool
     */
    public function updateMemberCount($communityId) {
        try {
            // This would be called when members are added/removed
            // For now, it's a placeholder for future member management
            $query = "UPDATE {$this->table}
                      SET total_members = (SELECT COUNT(DISTINCT member_name)
                                          FROM book_distribution bd
                                          INNER JOIN lottery_books lb ON bd.book_id = lb.book_id
                                          INNER JOIN lottery_events le ON lb.event_id = le.event_id
                                          WHERE le.community_id = :community_id)
                      WHERE community_id = :community_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);

            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Update Member Count Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
