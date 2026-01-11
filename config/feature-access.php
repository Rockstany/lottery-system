<?php
/**
 * Feature Access Control System
 * GetToKnow SAAS Platform v4.0
 *
 * Handles feature-based access control for the SAAS platform
 */

class FeatureAccess {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Check if a feature is enabled for a community
     *
     * @param int $community_id Community ID
     * @param string $feature_key Feature key (e.g., 'lottery_system')
     * @return bool True if enabled, false otherwise
     */
    public function isFeatureEnabled($community_id, $feature_key) {
        if (!$community_id || !$feature_key) {
            return false;
        }

        $query = "SELECT COUNT(*) as count
                  FROM community_features cf
                  JOIN features f ON cf.feature_id = f.feature_id
                  WHERE cf.community_id = :community_id
                  AND f.feature_key = :feature_key
                  AND cf.is_enabled = TRUE
                  AND f.is_active = TRUE";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
        $stmt->bindParam(':feature_key', $feature_key, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get all enabled features for a community
     *
     * @param int $community_id Community ID
     * @return array Array of enabled features
     */
    public function getEnabledFeatures($community_id) {
        if (!$community_id) {
            return [];
        }

        $query = "SELECT f.feature_id, f.feature_name, f.feature_key,
                         f.feature_description, f.feature_icon, f.display_order
                  FROM features f
                  JOIN community_features cf ON f.feature_id = cf.feature_id
                  WHERE cf.community_id = :community_id
                  AND cf.is_enabled = TRUE
                  AND f.is_active = TRUE
                  ORDER BY f.display_order ASC";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all available features (for admin use)
     *
     * @return array Array of all features
     */
    public function getAllFeatures() {
        $query = "SELECT feature_id, feature_name, feature_key,
                         feature_description, feature_icon, display_order, is_active
                  FROM features
                  WHERE is_active = TRUE
                  ORDER BY display_order ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get feature status for a community (enabled/disabled)
     *
     * @param int $community_id Community ID
     * @param int $feature_id Feature ID
     * @return bool True if enabled, false otherwise
     */
    public function getFeatureStatus($community_id, $feature_id) {
        $query = "SELECT is_enabled
                  FROM community_features
                  WHERE community_id = :community_id
                  AND feature_id = :feature_id";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
        $stmt->bindParam(':feature_id', $feature_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (bool)$result['is_enabled'] : false;
    }

    /**
     * Enable a feature for a community
     *
     * @param int $community_id Community ID
     * @param int $feature_id Feature ID
     * @param int $enabled_by User ID who enabled the feature
     * @return bool True on success, false on failure
     */
    public function enableFeature($community_id, $feature_id, $enabled_by) {
        try {
            $query = "INSERT INTO community_features
                      (community_id, feature_id, is_enabled, enabled_by, enabled_date)
                      VALUES (:community_id, :feature_id, TRUE, :enabled_by, NOW())
                      ON DUPLICATE KEY UPDATE
                      is_enabled = TRUE,
                      enabled_by = :enabled_by,
                      enabled_date = NOW(),
                      disabled_by = NULL,
                      disabled_date = NULL";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
            $stmt->bindParam(':feature_id', $feature_id, PDO::PARAM_INT);
            $stmt->bindParam(':enabled_by', $enabled_by, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error enabling feature: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable a feature for a community
     *
     * @param int $community_id Community ID
     * @param int $feature_id Feature ID
     * @param int $disabled_by User ID who disabled the feature
     * @return bool True on success, false on failure
     */
    public function disableFeature($community_id, $feature_id, $disabled_by) {
        try {
            $query = "UPDATE community_features
                      SET is_enabled = FALSE,
                          disabled_by = :disabled_by,
                          disabled_date = NOW()
                      WHERE community_id = :community_id
                      AND feature_id = :feature_id";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
            $stmt->bindParam(':feature_id', $feature_id, PDO::PARAM_INT);
            $stmt->bindParam(':disabled_by', $disabled_by, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error disabling feature: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Require a feature to be enabled (middleware function)
     * Redirects to error page if feature is not enabled
     *
     * @param string $feature_key Feature key
     * @param int $community_id Community ID (optional, gets from session if not provided)
     */
    public static function requireFeature($feature_key, $community_id = null) {
        if ($community_id === null) {
            $community_id = AuthMiddleware::getCommunityId();
        }

        if (!$community_id) {
            header('Location: /public/errors/no-community.php');
            exit;
        }

        $featureAccess = new self();
        if (!$featureAccess->isFeatureEnabled($community_id, $feature_key)) {
            $_SESSION['error_message'] = "This feature is not enabled for your community. Please contact your administrator.";
            header('Location: /public/group-admin/dashboard.php');
            exit;
        }
    }

    /**
     * Get feature statistics for dashboard
     *
     * @param int $community_id Community ID
     * @param string $feature_key Feature key
     * @return array Feature statistics
     */
    public function getFeatureStats($community_id, $feature_key) {
        $stats = [];

        switch ($feature_key) {
            case 'lottery_system':
                // Get lottery statistics
                $query = "SELECT
                          COUNT(*) as total_events,
                          SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_events,
                          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_events
                          FROM lottery_events
                          WHERE community_id = :community_id";

                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':community_id', $community_id, PDO::PARAM_INT);
                $stmt->execute();
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                break;

            // Add more feature stats as needed
            default:
                $stats = [
                    'total' => 0,
                    'active' => 0
                ];
        }

        return $stats;
    }
}

/**
 * Helper function for quick feature check
 *
 * @param string $feature_key Feature key
 * @param int $community_id Community ID (optional)
 * @return bool True if enabled, false otherwise
 */
function isFeatureEnabled($feature_key, $community_id = null) {
    if ($community_id === null) {
        $community_id = AuthMiddleware::getCommunityId();
    }

    $featureAccess = new FeatureAccess();
    return $featureAccess->isFeatureEnabled($community_id, $feature_key);
}

/**
 * Helper function to get enabled features
 *
 * @param int $community_id Community ID (optional)
 * @return array Array of enabled features
 */
function getEnabledFeatures($community_id = null) {
    if ($community_id === null) {
        $community_id = AuthMiddleware::getCommunityId();
    }

    $featureAccess = new FeatureAccess();
    return $featureAccess->getEnabledFeatures($community_id);
}
