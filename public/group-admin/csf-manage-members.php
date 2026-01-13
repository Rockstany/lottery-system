<?php
/**
 * CSF Manage Members - Add members single or bulk import
 * Connected to Community Building backbone
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/feature-access.php';

// Authentication
AuthMiddleware::requireRole('group_admin');
$userId = AuthMiddleware::getUserId();
$communityId = AuthMiddleware::getCommunityId();

// Feature access check
$featureAccess = new FeatureAccess();
if (!$featureAccess->isFeatureEnabled($communityId, 'csf_funds')) {
    $_SESSION['error_message'] = "CSF Funds is not enabled for your community";
    header('Location: /public/group-admin/dashboard.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle Single Member Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_single') {
    try {
        $full_name = trim($_POST['full_name']);
        $mobile_number = trim($_POST['mobile_number']);
        $email = trim($_POST['email']) ?: null;
        $sub_community_id = intval($_POST['sub_community_id']);

        // Validate sub-community belongs to this community
        $stmt = $db->prepare("SELECT sub_community_id FROM sub_communities WHERE sub_community_id = ? AND community_id = ?");
        $stmt->execute([$sub_community_id, $communityId]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid area selected");
        }

        // Check if user already exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE mobile_number = ?");
        $stmt->execute([$mobile_number]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $user_id = $existing_user['user_id'];

            // Check if already a member of this sub-community
            $stmt = $db->prepare("SELECT user_id FROM sub_community_members WHERE user_id = ? AND sub_community_id = ?");
            $stmt->execute([$user_id, $sub_community_id]);
            if ($stmt->fetch()) {
                throw new Exception("Member already exists in this area");
            }
        } else {
            // Create new user (role must be group_admin as per schema, CSF members are in sub_community_members table)
            $password_hash = password_hash('Welcome@123', PASSWORD_DEFAULT);
            $plain_password = 'Welcome@123';
            $stmt = $db->prepare("INSERT INTO users (full_name, email, mobile_number, password_hash, plain_password, role, created_at) VALUES (?, ?, ?, ?, ?, 'group_admin', NOW())");
            $stmt->execute([$full_name, $email, $mobile_number, $password_hash, $plain_password]);
            $user_id = $db->lastInsertId();
        }

        // Add to sub-community
        $stmt = $db->prepare("INSERT INTO sub_community_members (sub_community_id, user_id, joined_date, status) VALUES (?, ?, NOW(), 'active')");
        $stmt->execute([$sub_community_id, $user_id]);

        $success_message = "Member added successfully!";

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle Bulk Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_import') {
    try {
        $csv_data = trim($_POST['csv_data']);
        $sub_community_id = intval($_POST['bulk_sub_community_id']);

        // Validate sub-community
        $stmt = $db->prepare("SELECT sub_community_id FROM sub_communities WHERE sub_community_id = ? AND community_id = ?");
        $stmt->execute([$sub_community_id, $communityId]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid area selected");
        }

        $lines = explode("\n", $csv_data);
        $added_count = 0;
        $skipped_count = 0;
        $errors = [];

        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = str_getcsv($line);
            if (count($parts) < 2) {
                $errors[] = "Line " . ($line_num + 1) . ": Invalid format (need Name, Mobile)";
                continue;
            }

            $full_name = trim($parts[0]);
            $mobile_number = trim($parts[1]);
            $email = isset($parts[2]) ? trim($parts[2]) : null;

            if (empty($full_name) || empty($mobile_number)) {
                $errors[] = "Line " . ($line_num + 1) . ": Name and Mobile are required";
                continue;
            }

            try {
                // Check if user exists
                $stmt = $db->prepare("SELECT user_id FROM users WHERE mobile_number = ?");
                $stmt->execute([$mobile_number]);
                $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_user) {
                    $user_id = $existing_user['user_id'];

                    // Check if already in sub-community
                    $stmt = $db->prepare("SELECT user_id FROM sub_community_members WHERE user_id = ? AND sub_community_id = ?");
                    $stmt->execute([$user_id, $sub_community_id]);
                    if ($stmt->fetch()) {
                        $skipped_count++;
                        continue;
                    }
                } else {
                    // Create new user (role must be group_admin as per schema, CSF members are in sub_community_members table)
                    $password_hash = password_hash('Welcome@123', PASSWORD_DEFAULT);
                    $plain_password = 'Welcome@123';
                    $stmt = $db->prepare("INSERT INTO users (full_name, email, mobile_number, password_hash, plain_password, role, created_at) VALUES (?, ?, ?, ?, ?, 'group_admin', NOW())");
                    $stmt->execute([$full_name, $email, $mobile_number, $password_hash, $plain_password]);
                    $user_id = $db->lastInsertId();
                }

                // Add to sub-community
                $stmt = $db->prepare("INSERT INTO sub_community_members (sub_community_id, user_id, joined_date, status) VALUES (?, ?, NOW(), 'active')");
                $stmt->execute([$sub_community_id, $user_id]);
                $added_count++;

            } catch (Exception $e) {
                $errors[] = "Line " . ($line_num + 1) . ": " . $e->getMessage();
            }
        }

        $success_message = "Import complete! Added: $added_count, Skipped: $skipped_count";
        if (!empty($errors)) {
            $error_message = "Some errors occurred:\n" . implode("\n", array_slice($errors, 0, 5));
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all sub-communities (areas)
$stmt = $db->prepare("SELECT sub_community_id, sub_community_name FROM sub_communities WHERE community_id = ? AND status = 'active' ORDER BY sub_community_name");
$stmt->execute([$communityId]);
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Edit Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_member') {
    try {
        $user_id = intval($_POST['user_id']);
        $full_name = trim($_POST['edit_full_name']);
        $mobile_number = trim($_POST['edit_mobile_number']);
        $email = trim($_POST['edit_email']) ?: null;
        $new_sub_community_id = intval($_POST['edit_sub_community_id']);

        // Verify the member belongs to this community
        $stmt = $db->prepare("SELECT scm.user_id
                              FROM sub_community_members scm
                              JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                              WHERE scm.user_id = ? AND sc.community_id = ?");
        $stmt->execute([$user_id, $communityId]);
        if (!$stmt->fetch()) {
            throw new Exception("Member not found or access denied");
        }

        // Check if mobile number is taken by another user
        $stmt = $db->prepare("SELECT user_id FROM users WHERE mobile_number = ? AND user_id != ?");
        $stmt->execute([$mobile_number, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Mobile number already exists for another user");
        }

        // Validate new sub-community belongs to this community
        $stmt = $db->prepare("SELECT sub_community_id FROM sub_communities WHERE sub_community_id = ? AND community_id = ?");
        $stmt->execute([$new_sub_community_id, $communityId]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid area selected");
        }

        // Update user details
        $stmt = $db->prepare("UPDATE users SET full_name = ?, mobile_number = ?, email = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $mobile_number, $email, $user_id]);

        // Update sub-community assignment
        $stmt = $db->prepare("UPDATE sub_community_members SET sub_community_id = ? WHERE user_id = ?");
        $stmt->execute([$new_sub_community_id, $user_id]);

        $success_message = "Member updated successfully!";

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle Remove Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_member') {
    try {
        $user_id = intval($_POST['user_id']);

        // Verify the member belongs to this community
        $stmt = $db->prepare("SELECT scm.user_id
                              FROM sub_community_members scm
                              JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                              WHERE scm.user_id = ? AND sc.community_id = ?");
        $stmt->execute([$user_id, $communityId]);
        if (!$stmt->fetch()) {
            throw new Exception("Member not found or access denied");
        }

        // Check if member has CSF payment records
        $stmt = $db->prepare("SELECT COUNT(*) as payment_count FROM csf_payments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['payment_count'] > 0) {
            // Soft delete - mark as inactive
            $stmt = $db->prepare("UPDATE sub_community_members SET status = 'inactive' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $success_message = "Member marked as inactive (has payment history)";
        } else {
            // Hard delete - remove completely
            $stmt = $db->prepare("DELETE FROM sub_community_members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $success_message = "Member removed successfully!";
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all current members
$stmt = $db->prepare("SELECT u.user_id, u.full_name, u.mobile_number, u.email, sc.sub_community_id, sc.sub_community_name, scm.joined_date
                       FROM sub_community_members scm
                       JOIN users u ON scm.user_id = u.user_id
                       JOIN sub_communities sc ON scm.sub_community_id = sc.sub_community_id
                       WHERE sc.community_id = ? AND scm.status = 'active'
                       ORDER BY sc.sub_community_name, u.full_name");
$stmt->execute([$communityId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - CSF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-size: 18px;
            background-color: #f8f9fa;
        }
        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-section h1 {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .back-link {
            font-size: 20px;
            color: #007bff;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-link:hover {
            color: #0056b3;
        }
        .tabs-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            padding: 20px 20px 0 20px;
        }
        .nav-tabs .nav-link {
            font-size: 20px;
            font-weight: 600;
            color: #6c757d;
            padding: 15px 30px;
            border: none;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background: none;
        }
        .tab-content {
            padding: 40px;
        }
        .form-label {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .form-control, .form-select {
            font-size: 18px;
            padding: 12px 15px;
            border-radius: 8px;
        }
        .btn-custom {
            font-size: 20px;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
        }
        .members-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table {
            font-size: 16px;
            margin-bottom: 0;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 15px;
        }
        .table td {
            padding: 12px 15px;
        }
        .alert {
            font-size: 18px;
            padding: 20px;
            border-radius: 10px;
        }
        .help-text {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn-sm {
            font-size: 14px;
            padding: 6px 12px;
            margin-right: 5px;
        }
        .table td {
            vertical-align: middle;
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-header {
            background: #f8f9fa;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .modal-title {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="csf-funds.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to CSF Funds
        </a>

        <div class="header-section">
            <h1><i class="fas fa-users"></i> Manage Members</h1>
            <p class="mb-0">Add members to your CSF system (single or bulk import)</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo nl2br(htmlspecialchars($success_message)); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo nl2br(htmlspecialchars($error_message)); ?>
            </div>
        <?php endif; ?>

        <div class="tabs-container">
            <ul class="nav nav-tabs" id="manageTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button">
                        <i class="fas fa-user-plus"></i> Add Single Member
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" type="button">
                        <i class="fas fa-upload"></i> Bulk Import
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button">
                        <i class="fas fa-list"></i> View All Members (<?php echo count($members); ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="manageTabContent">
                <!-- Single Member Add -->
                <div class="tab-pane fade show active" id="single" role="tabpanel">
                    <h3 class="mb-4">Add New Member</h3>

                    <form method="POST">
                        <input type="hidden" name="action" value="add_single">

                        <div class="mb-4">
                            <label class="form-label">Select Area / Sub-Community</label>
                            <select class="form-select" name="sub_community_id" required>
                                <option value="">-- Choose Area --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['sub_community_id']; ?>">
                                        <?php echo htmlspecialchars($area['sub_community_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mobile Number *</label>
                            <input type="tel" class="form-control" name="mobile_number" pattern="[0-9]{10}" required>
                            <small class="text-muted">10 digit mobile number</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" name="email">
                        </div>

                        <button type="submit" class="btn btn-primary btn-custom">
                            <i class="fas fa-plus"></i> Add Member
                        </button>
                    </form>
                </div>

                <!-- Bulk Import -->
                <div class="tab-pane fade" id="bulk" role="tabpanel">
                    <h3 class="mb-4">Bulk Import Members</h3>

                    <div class="help-text">
                        <strong>Format:</strong> Paste data in CSV format (one member per line)<br>
                        <strong>Columns:</strong> Full Name, Mobile Number, Email (optional)<br>
                        <strong>Example:</strong><br>
                        <code>
                        John Doe, 9876543210, john@example.com<br>
                        Jane Smith, 9876543211<br>
                        Akshit Kumar, 9876543212, akshit@example.com
                        </code>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="bulk_import">

                        <div class="mb-4">
                            <label class="form-label">Select Area / Sub-Community</label>
                            <select class="form-select" name="bulk_sub_community_id" required>
                                <option value="">-- Choose Area --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['sub_community_id']; ?>">
                                        <?php echo htmlspecialchars($area['sub_community_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Paste CSV Data</label>
                            <textarea class="form-control" name="csv_data" rows="15" required placeholder="Full Name, Mobile, Email
John Doe, 9876543210, john@example.com
Jane Smith, 9876543211"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-custom">
                            <i class="fas fa-cloud-upload-alt"></i> Import Members
                        </button>
                    </form>
                </div>

                <!-- View Members List -->
                <div class="tab-pane fade" id="list" role="tabpanel">
                    <h3 class="mb-4">All Members in CSF System</h3>

                    <?php if (count($members) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Email</th>
                                        <th>Joined Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($member['sub_community_name']); ?></span></td>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['mobile_number']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email'] ?: '-'); ?></td>
                                            <td><?php echo date('d M Y', strtotime($member['joined_date'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="editMember(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['mobile_number'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($member['email'] ?: '', ENT_QUOTES); ?>', <?php echo $member['sub_community_id']; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="removeMember(<?php echo $member['user_id']; ?>, '<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No members found. Add members using the tabs above.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Member</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editMemberForm">
                    <input type="hidden" name="action" value="edit_member">
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label">Select Area / Sub-Community</label>
                            <select class="form-select" name="edit_sub_community_id" id="edit_sub_community_id" required>
                                <option value="">-- Choose Area --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['sub_community_id']; ?>">
                                        <?php echo htmlspecialchars($area['sub_community_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="edit_full_name" id="edit_full_name" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mobile Number *</label>
                            <input type="tel" class="form-control" name="edit_mobile_number" id="edit_mobile_number" pattern="[0-9]{10}" required>
                            <small class="text-muted">10 digit mobile number</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" class="form-control" name="edit_email" id="edit_email">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Remove Member Form (Hidden) -->
    <form method="POST" id="removeMemberForm" style="display: none;">
        <input type="hidden" name="action" value="remove_member">
        <input type="hidden" name="user_id" id="remove_user_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Member Function
        function editMember(userId, fullName, mobileNumber, email, subCommunityId) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_mobile_number').value = mobileNumber;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_sub_community_id').value = subCommunityId;

            const modal = new bootstrap.Modal(document.getElementById('editMemberModal'));
            modal.show();
        }

        // Remove Member Function
        function removeMember(userId, fullName) {
            if (confirm('Are you sure you want to remove "' + fullName + '" from the CSF system?\n\nNote: If the member has payment history, they will be marked as inactive instead of being permanently deleted.')) {
                document.getElementById('remove_user_id').value = userId;
                document.getElementById('removeMemberForm').submit();
            }
        }
    </script>
</body>
</html>
