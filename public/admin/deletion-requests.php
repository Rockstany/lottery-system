<?php
/**
 * Deletion Requests Management (Super Admin Only)
 * Review and approve/reject deletion requests from group admins
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Get filter status
$filterStatus = isset($_GET['status']) ? Validator::sanitizeString($_GET['status']) : 'pending';
$allowedStatuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filterStatus, $allowedStatuses)) {
    $filterStatus = 'pending';
}

// Build query based on filter
$whereClause = $filterStatus === 'all' ? '1=1' : 'dr.status = :status';

// Get all deletion requests
$query = "SELECT dr.*, u.name as requester_name, u.mobile_number as requester_mobile,
                 a.name as reviewer_name
          FROM deletion_requests dr
          LEFT JOIN users u ON dr.requested_by = u.user_id
          LEFT JOIN users a ON dr.reviewed_by = a.user_id
          WHERE {$whereClause}
          ORDER BY
              CASE WHEN dr.status = 'pending' THEN 0 ELSE 1 END,
              dr.created_at DESC";

$stmt = $db->prepare($query);
if ($filterStatus !== 'all') {
    $stmt->bindParam(':status', $filterStatus);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for badges
$countQuery = "SELECT
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                  COUNT(*) as total_count
               FROM deletion_requests";
$countStmt = $db->query($countQuery);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deletion Requests - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }

        .filter-tabs {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            border-bottom: 2px solid var(--gray-200);
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: var(--spacing-sm) var(--spacing-lg);
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            text-decoration: none;
            transition: all var(--transition-base);
            position: relative;
        }

        .filter-tab:hover {
            color: var(--primary-color);
            background: var(--gray-50);
        }

        .filter-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .filter-tab .badge {
            margin-left: var(--spacing-xs);
            font-size: var(--font-size-xs);
        }

        .request-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--gray-300);
        }

        .request-card.pending { border-left-color: var(--warning-color); }
        .request-card.approved { border-left-color: var(--success-color); }
        .request-card.rejected { border-left-color: var(--danger-color); }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--gray-50);
            border-radius: var(--radius-md);
            margin: var(--spacing-md) 0;
        }

        .detail-item {
            font-size: var(--font-size-sm);
        }

        .detail-label {
            color: var(--gray-600);
            font-weight: 600;
            display: block;
            margin-bottom: var(--spacing-xs);
        }

        .reason-box {
            background: var(--warning-light);
            border-left: 3px solid var(--warning-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin: var(--spacing-md) 0;
        }

        .review-box {
            background: var(--info-light);
            border-left: 3px solid var(--info-color);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin: var(--spacing-md) 0;
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--gray-500);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: var(--spacing-md);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../group-admin/includes/navigation.php'; ?>

    <div class="header">
        <div class="container">
            <h1>🗑️ Deletion Requests</h1>
            <p style="margin: 0; opacity: 0.9;">Review and manage deletion requests from group admins</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Success/Error Messages -->
        <?php if ($success === 'approved'): ?>
            <div class="alert alert-success">Deletion request approved and item deleted successfully.</div>
        <?php elseif ($success === 'rejected'): ?>
            <div class="alert alert-success">Deletion request rejected successfully.</div>
        <?php endif; ?>

        <?php if ($error === 'invalid'): ?>
            <div class="alert alert-danger">Invalid request.</div>
        <?php elseif ($error === 'notfound'): ?>
            <div class="alert alert-danger">Deletion request not found.</div>
        <?php elseif ($error === 'delete_failed'): ?>
            <div class="alert alert-danger">Failed to delete the item. Please try again.</div>
        <?php elseif ($error === 'update_failed'): ?>
            <div class="alert alert-danger">Failed to update request status. Please try again.</div>
        <?php endif; ?>

        <!-- Back Button -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/admin/dashboard.php" class="btn btn-secondary">← Back to Admin Dashboard</a>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=pending" class="filter-tab <?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">
                Pending
                <span class="badge badge-warning"><?php echo $counts['pending_count']; ?></span>
            </a>
            <a href="?status=approved" class="filter-tab <?php echo $filterStatus === 'approved' ? 'active' : ''; ?>">
                Approved
                <span class="badge badge-success"><?php echo $counts['approved_count']; ?></span>
            </a>
            <a href="?status=rejected" class="filter-tab <?php echo $filterStatus === 'rejected' ? 'active' : ''; ?>">
                Rejected
                <span class="badge badge-danger"><?php echo $counts['rejected_count']; ?></span>
            </a>
            <a href="?status=all" class="filter-tab <?php echo $filterStatus === 'all' ? 'active' : ''; ?>">
                All Requests
                <span class="badge badge-secondary"><?php echo $counts['total_count']; ?></span>
            </a>
        </div>

        <!-- Deletion Requests -->
        <?php if (count($requests) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No <?php echo $filterStatus === 'all' ? '' : ucfirst($filterStatus); ?> Deletion Requests</h3>
                <p>There are currently no deletion requests<?php echo $filterStatus === 'all' ? '' : ' with status: ' . ucfirst($filterStatus); ?>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card <?php echo $request['status']; ?>">
                    <div class="request-header">
                        <div>
                            <h3 style="margin: 0; margin-bottom: var(--spacing-xs);">
                                <?php echo $request['request_type'] === 'lottery_event' ? '🎫' : '💰'; ?>
                                <?php echo htmlspecialchars($request['item_name']); ?>
                            </h3>
                            <div style="display: flex; gap: var(--spacing-sm); align-items: center; flex-wrap: wrap;">
                                <span class="badge badge-<?php echo $request['status'] === 'pending' ? 'warning' : ($request['status'] === 'approved' ? 'success' : 'danger'); ?>">
                                    <?php echo strtoupper($request['status']); ?>
                                </span>
                                <span class="badge badge-secondary">
                                    <?php echo $request['request_type'] === 'lottery_event' ? 'Lottery Event' : 'Payment Transaction'; ?>
                                </span>
                            </div>
                        </div>
                        <div style="text-align: right; font-size: var(--font-size-sm); color: var(--gray-600);">
                            <div>Requested: <?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></div>
                            <?php if ($request['reviewed_at']): ?>
                                <div>Reviewed: <?php echo date('M d, Y H:i', strtotime($request['reviewed_at'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="request-details">
                        <div class="detail-item">
                            <span class="detail-label">Requested By</span>
                            <?php echo htmlspecialchars($request['requester_name'] ?? 'Unknown'); ?>
                            <?php if ($request['requester_mobile']): ?>
                                <br><small style="color: var(--gray-600);"><?php echo htmlspecialchars($request['requester_mobile']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Request ID</span>
                            #<?php echo $request['request_id']; ?>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Item ID</span>
                            #<?php echo $request['item_id']; ?>
                        </div>
                        <?php if ($request['reviewer_name']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Reviewed By</span>
                                <?php echo htmlspecialchars($request['reviewer_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="reason-box">
                        <strong style="display: block; margin-bottom: var(--spacing-xs);">📝 Reason for Deletion:</strong>
                        <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                    </div>

                    <?php if ($request['review_notes']): ?>
                        <div class="review-box">
                            <strong style="display: block; margin-bottom: var(--spacing-xs);">💬 Admin Review Notes:</strong>
                            <?php echo nl2br(htmlspecialchars($request['review_notes'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($request['status'] === 'pending'): ?>
                        <div class="action-buttons">
                            <button
                                onclick="showApproveModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['item_name'], ENT_QUOTES); ?>', '<?php echo $request['request_type']; ?>', <?php echo $request['item_id']; ?>)"
                                class="btn btn-success">
                                ✅ Approve & Delete
                            </button>
                            <button
                                onclick="showRejectModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['item_name'], ENT_QUOTES); ?>')"
                                class="btn btn-danger">
                                ❌ Reject Request
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Approve Confirmation Modal -->
    <div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); max-width: 500px; margin: var(--spacing-md);">
            <h3 style="margin-top: 0; color: var(--success-color);">✅ Approve Deletion Request</h3>
            <p>Are you sure you want to approve this deletion request?</p>
            <p><strong id="approveItemName"></strong></p>
            <p style="color: var(--danger-color); font-weight: 600;">⚠️ This will PERMANENTLY delete the item. This action cannot be undone!</p>
            <form id="approveForm" method="POST" action="/public/admin/deletion-request-approve.php">
                <input type="hidden" name="request_id" id="approveRequestId">
                <input type="hidden" name="request_type" id="approveRequestType">
                <input type="hidden" name="item_id" id="approveItemId">
                <div class="form-group">
                    <label for="approveNotes" class="form-label">Admin Notes (Optional)</label>
                    <textarea
                        id="approveNotes"
                        name="notes"
                        class="form-control"
                        rows="3"
                        placeholder="Any notes about this approval..."></textarea>
                </div>
                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="button" onclick="closeApproveModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="flex: 1;">Approve & Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); max-width: 500px; margin: var(--spacing-md);">
            <h3 style="margin-top: 0; color: var(--danger-color);">❌ Reject Deletion Request</h3>
            <p>Are you sure you want to reject this deletion request?</p>
            <p><strong id="rejectItemName"></strong></p>
            <form id="rejectForm" method="POST" action="/public/admin/deletion-request-reject.php">
                <input type="hidden" name="request_id" id="rejectRequestId">
                <div class="form-group">
                    <label for="rejectNotes" class="form-label">Rejection Reason <span style="color: var(--danger-color);">*</span></label>
                    <textarea
                        id="rejectNotes"
                        name="notes"
                        class="form-control"
                        rows="3"
                        required
                        placeholder="Please provide a reason for rejecting this request..."></textarea>
                </div>
                <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-lg);">
                    <button type="button" onclick="closeRejectModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="flex: 1;">Reject Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showApproveModal(requestId, itemName, requestType, itemId) {
            document.getElementById('approveRequestId').value = requestId;
            document.getElementById('approveRequestType').value = requestType;
            document.getElementById('approveItemId').value = itemId;
            document.getElementById('approveItemName').textContent = itemName;
            document.getElementById('approveModal').style.display = 'flex';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
            document.getElementById('approveNotes').value = '';
        }

        function showRejectModal(requestId, itemName) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectItemName').textContent = itemName;
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejectNotes').value = '';
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApproveModal();
                closeRejectModal();
            }
        });
    </script>
</body>
</html>
