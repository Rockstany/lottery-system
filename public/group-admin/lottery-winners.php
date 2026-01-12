<?php
/**
 * Winners Management
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$eventId = Validator::sanitizeInt($_GET['id'] ?? 0);
$communityId = AuthMiddleware::getCommunityId();

if (!$eventId || !$communityId) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get event
$query = "SELECT * FROM lottery_events WHERE event_id = :id AND community_id = :community_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $eventId);
$stmt->bindParam(':community_id', $communityId);
$stmt->execute();
$event = $stmt->fetch();

if (!$event) {
    header("Location: /public/group-admin/lottery.php");
    exit;
}

// Get distribution levels for dynamic columns
$levelsQuery = "SELECT * FROM distribution_levels WHERE event_id = :event_id ORDER BY level_number";
$stmt = $db->prepare($levelsQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$levels = $stmt->fetchAll();

$error = '';
$success = '';

// Handle edit winner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_winner'])) {
    $winnerId = Validator::sanitizeInt($_POST['winner_id'] ?? 0);
    $winnerName = Validator::sanitizeString($_POST['winner_name'] ?? '');
    $winnerContact = Validator::sanitizeString($_POST['winner_contact'] ?? '');

    if (!$winnerId) {
        $error = 'Invalid winner ID';
    } else {
        // Update only name and contact
        $updateQuery = "UPDATE lottery_winners SET
                       winner_name = :winner_name,
                       winner_contact = :winner_contact
                       WHERE winner_id = :winner_id AND event_id = :event_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':winner_name', $winnerName);
        $updateStmt->bindParam(':winner_contact', $winnerContact);
        $updateStmt->bindParam(':winner_id', $winnerId);
        $updateStmt->bindParam(':event_id', $eventId);

        if ($updateStmt->execute()) {
            header("Location: ?id=$eventId&success=updated");
            exit;
        } else {
            $error = 'Failed to update winner details';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_winner'])) {
    $ticketNumber = Validator::sanitizeInt($_POST['ticket_number'] ?? 0);
    $prizePosition = $_POST['prize_position'] ?? '';
    $winnerName = Validator::sanitizeString($_POST['winner_name'] ?? '');
    $winnerContact = Validator::sanitizeString($_POST['winner_contact'] ?? '');

    if (!$ticketNumber) {
        $error = 'Please enter a valid ticket number';
    } elseif (!in_array($prizePosition, ['1st', '2nd', '3rd', 'consolation'])) {
        $error = 'Please select a prize position';
    } else {
        // Check if ticket exists in this event
        $checkQuery = "SELECT lb.book_number, bd.distribution_path
                       FROM lottery_books lb
                       LEFT JOIN book_distribution bd ON lb.book_id = bd.book_id
                       WHERE lb.event_id = :event_id
                       AND :ticket_number BETWEEN lb.start_ticket_number AND lb.end_ticket_number
                       LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':event_id', $eventId);
        $checkStmt->bindParam(':ticket_number', $ticketNumber);
        $checkStmt->execute();
        $ticketInfo = $checkStmt->fetch();

        if (!$ticketInfo) {
            $error = 'Ticket number not found in this event';
        } else {
            // Check if already added
            $dupQuery = "SELECT * FROM lottery_winners WHERE event_id = :event_id AND ticket_number = :ticket_number";
            $dupStmt = $db->prepare($dupQuery);
            $dupStmt->bindParam(':event_id', $eventId);
            $dupStmt->bindParam(':ticket_number', $ticketNumber);
            $dupStmt->execute();

            if ($dupStmt->fetch()) {
                $error = 'This ticket number is already added as a winner';
            } else {
                // Insert winner
                $insertQuery = "INSERT INTO lottery_winners (event_id, ticket_number, prize_position, book_number, distribution_path, winner_name, winner_contact, added_by)
                                VALUES (:event_id, :ticket_number, :prize_position, :book_number, :distribution_path, :winner_name, :winner_contact, :added_by)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':event_id', $eventId);
                $insertStmt->bindParam(':ticket_number', $ticketNumber);
                $insertStmt->bindParam(':prize_position', $prizePosition);
                $insertStmt->bindParam(':book_number', $ticketInfo['book_number']);
                $insertStmt->bindParam(':distribution_path', $ticketInfo['distribution_path']);
                $insertStmt->bindParam(':winner_name', $winnerName);
                $insertStmt->bindParam(':winner_contact', $winnerContact);
                $addedBy = AuthMiddleware::getUserId();
                $insertStmt->bindParam(':added_by', $addedBy);

                if ($insertStmt->execute()) {
                    $success = 'Winner added successfully!';
                } else {
                    $error = 'Failed to add winner';
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $winnerId = Validator::sanitizeInt($_GET['delete']);
    $deleteQuery = "DELETE FROM lottery_winners WHERE winner_id = :id AND event_id = :event_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $winnerId);
    $deleteStmt->bindParam(':event_id', $eventId);
    if ($deleteStmt->execute()) {
        header("Location: ?id=$eventId&success=deleted");
        exit;
    }
}

if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'deleted' => 'Winner deleted successfully',
        'updated' => 'Winner details updated successfully',
        default => ''
    };
}

// Get all winners
$winnersQuery = "SELECT * FROM lottery_winners WHERE event_id = :event_id ORDER BY
                 CASE prize_position
                     WHEN '1st' THEN 1
                     WHEN '2nd' THEN 2
                     WHEN '3rd' THEN 3
                     WHEN 'consolation' THEN 4
                 END, added_at";
$stmt = $db->prepare($winnersQuery);
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$winners = $stmt->fetchAll();

// Count by prize
$prizeCounts = [
    '1st' => count(array_filter($winners, fn($w) => $w['prize_position'] === '1st')),
    '2nd' => count(array_filter($winners, fn($w) => $w['prize_position'] === '2nd')),
    '3rd' => count(array_filter($winners, fn($w) => $w['prize_position'] === '3rd')),
    'consolation' => count(array_filter($winners, fn($w) => $w['prize_position'] === 'consolation'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Winners Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <link rel="stylesheet" href="/public/css/lottery-responsive.css">
    <script src="/public/js/toast.js"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }

        .modal-close {
            font-size: 28px;
            font-weight: bold;
            color: var(--gray-500);
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--gray-800);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>

    <div class="header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: var(--spacing-xl) 0; margin-bottom: var(--spacing-xl);">
        <div class="container">
            <h1>🏆 <?php echo htmlspecialchars($event['event_name']); ?> - Winners</h1>
            <p style="margin: 0; opacity: 0.9;">Manage Lottery Winners</p>
        </div>
    </div>

    <div class="container main-content">
        <!-- Back Button at Top -->
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="/public/group-admin/lottery.php" class="btn btn-secondary">← Back to Events</a>
            <a href="/public/group-admin/lottery-reports.php?id=<?php echo $eventId; ?>" class="btn btn-primary">View Reports</a>
        </div>

        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Toast.error(<?php echo json_encode($error); ?>);
                });
            </script>
        <?php endif; ?>

        <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Toast.success(<?php echo json_encode($success); ?>);
                });
            </script>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-bar" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #FFD700;"><?php echo $prizeCounts['1st']; ?></div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">1st Prize</div>
            </div>
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #C0C0C0;"><?php echo $prizeCounts['2nd']; ?></div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">2nd Prize</div>
            </div>
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: #CD7F32;"><?php echo $prizeCounts['3rd']; ?></div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">3rd Prize</div>
            </div>
            <div class="stat-box" style="background: white; padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--info-color);"><?php echo $prizeCounts['consolation']; ?></div>
                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">Consolation</div>
            </div>
        </div>

        <!-- Add Winner Form -->
        <div class="card" style="margin-bottom: var(--spacing-lg);">
            <div class="card-header">
                <h3 class="card-title">Add New Winner</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_winner" value="1">
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label form-label-required">Ticket Number</label>
                            <input type="number" name="ticket_number" class="form-control" required autofocus
                                   placeholder="Enter winning ticket number">
                            <small class="form-text">System will auto-fill book and location details</small>
                        </div>
                        <div class="form-col">
                            <label class="form-label form-label-required">Prize Position</label>
                            <select name="prize_position" class="form-control" required>
                                <option value="">Select Prize</option>
                                <option value="1st">🥇 1st Prize</option>
                                <option value="2nd">🥈 2nd Prize</option>
                                <option value="3rd">🥉 3rd Prize</option>
                                <option value="consolation">🎖️ Consolation Prize</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label">Winner Name (Optional)</label>
                            <input type="text" name="winner_name" class="form-control" placeholder="Enter winner's name">
                        </div>
                        <div class="form-col">
                            <label class="form-label">Contact Number (Optional)</label>
                            <input type="tel" name="winner_contact" class="form-control" placeholder="10-digit mobile" maxlength="10">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Add Winner</button>
                </form>
            </div>
        </div>

        <!-- Winners List -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Winners List (<?php echo count($winners); ?>)</h3>
                <?php if (count($winners) > 0): ?>
                    <a href="/public/group-admin/lottery-winners-export.php?id=<?php echo $eventId; ?>" class="btn btn-primary btn-sm">
                        📥 Export to CSV
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($winners) === 0): ?>
                    <div style="text-align: center; padding: var(--spacing-2xl); color: var(--gray-500);">
                        <div style="font-size: 64px; margin-bottom: var(--spacing-md);">🏆</div>
                        <h3>No Winners Added Yet</h3>
                        <p>Use the form above to add winners for this lottery event.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Prize</th>
                                    <th>Ticket No</th>
                                    <th>Book #</th>
                                    <?php foreach ($levels as $level): ?>
                                        <th><?php echo htmlspecialchars($level['level_name']); ?></th>
                                    <?php endforeach; ?>
                                    <th>Winner Name</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($winners as $winner): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $prizeIcons = ['1st' => '🥇', '2nd' => '🥈', '3rd' => '🥉', 'consolation' => '🎖️'];
                                            echo $prizeIcons[$winner['prize_position']] . ' ' . ucfirst($winner['prize_position']);
                                            ?>
                                        </td>
                                        <td><strong><?php echo $winner['ticket_number']; ?></strong></td>
                                        <td><?php echo $winner['book_number']; ?></td>
                                        <?php
                                        // Parse distribution path into level values
                                        $levelValues = [];
                                        if (!empty($winner['distribution_path'])) {
                                            $levelValues = explode(' > ', $winner['distribution_path']);
                                        }
                                        // Display each level value
                                        for ($i = 0; $i < count($levels); $i++) {
                                            echo '<td>' . htmlspecialchars($levelValues[$i] ?? '-') . '</td>';
                                        }
                                        ?>
                                        <td><?php echo htmlspecialchars($winner['winner_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($winner['winner_contact'] ?? '-'); ?></td>
                                        <td style="white-space: nowrap;">
                                            <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($winner)); ?>)">
                                                Edit
                                            </button>
                                            <a href="?id=<?php echo $eventId; ?>&delete=<?php echo $winner['winner_id']; ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Delete this winner?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Winner Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">Edit Winner Details</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="edit_winner" value="1">
                <input type="hidden" name="winner_id" id="edit_winner_id">

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                    <label class="form-label">Ticket Number</label>
                    <input type="text" id="edit_ticket_number" class="form-control" disabled style="background: var(--gray-100); cursor: not-allowed;">
                    <small class="form-text" style="color: var(--gray-600);">Ticket number cannot be changed</small>
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-md);">
                    <label class="form-label">Winner Name</label>
                    <input type="text" name="winner_name" id="edit_winner_name" class="form-control" placeholder="Enter winner's name">
                </div>

                <div class="form-group" style="margin-bottom: var(--spacing-lg);">
                    <label class="form-label">Contact Number</label>
                    <input type="tel" name="winner_contact" id="edit_winner_contact" class="form-control" placeholder="10-digit mobile" maxlength="10">
                </div>

                <div style="display: flex; gap: var(--spacing-md);">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(winner) {
            document.getElementById('edit_winner_id').value = winner.winner_id;
            document.getElementById('edit_ticket_number').value = winner.ticket_number;
            document.getElementById('edit_winner_name').value = winner.winner_name || '';
            document.getElementById('edit_winner_contact').value = winner.winner_contact || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
