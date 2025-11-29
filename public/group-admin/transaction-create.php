<?php
/**
 * Create Transaction Campaign
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('group_admin');

$communityId = AuthMiddleware::getCommunityId();

if (!$communityId) {
    die('<div class="alert alert-danger">You are not assigned to any community.</div>');
}

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $campaignName = Validator::sanitizeString($_POST['campaign_name'] ?? '');
        $description = Validator::sanitizeString($_POST['description'] ?? '');

        $validator = new Validator();
        $validator->required('campaign_name', $campaignName, 'Campaign Name');

        if ($validator->fails()) {
            $errors = $validator->getErrors();
        } else {
            $database = new Database();
            $db = $database->getConnection();

            $query = "INSERT INTO transaction_campaigns (community_id, campaign_name, campaign_description, status, created_by)
                      VALUES (:community_id, :name, :description, 'active', :created_by)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':community_id', $communityId);
            $stmt->bindParam(':name', $campaignName);
            $stmt->bindParam(':description', $description);
            $createdBy = AuthMiddleware::getUserId();
            $stmt->bindParam(':created_by', $createdBy);

            if ($stmt->execute()) {
                $campaignId = $db->lastInsertId();
                header("Location: /public/group-admin/transaction-upload.php?id={$campaignId}");
                exit;
            } else {
                $error = 'Failed to create campaign';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .instructions {
            background: var(--info-light);
            border-left: 4px solid var(--info-color);
            padding: var(--spacing-lg);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
        }
        .example-box {
            background: var(--gray-50);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-top: var(--spacing-md);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Create New Campaign</h1>
            <p style="margin: 0; opacity: 0.9;">Step 1 of 4</p>
        </div>
    </div>

    <div class="container main-content">
        <div class="instructions">
            <h3 style="margin-top: 0;">📝 Step 1: Name Your Campaign</h3>
            <p>Give your payment collection campaign a clear name that members will understand.</p>
            <div class="example-box">
                <strong>Examples:</strong>
                <ul style="margin: var(--spacing-sm) 0;">
                    <li>"November 2025 Maintenance"</li>
                    <li>"Diwali Event Contribution"</li>
                    <li>"Annual Society Fees 2025"</li>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Campaign Details</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label form-label-required">Campaign Name</label>
                                <input
                                    type="text"
                                    name="campaign_name"
                                    class="form-control <?php echo isset($errors['campaign_name']) ? 'is-invalid' : ''; ?>"
                                    placeholder="e.g., November 2025 Maintenance"
                                    value="<?php echo htmlspecialchars($_POST['campaign_name'] ?? ''); ?>"
                                    required
                                    autofocus
                                >
                                <?php if (isset($errors['campaign_name'])): ?>
                                    <span class="form-error"><?php echo $errors['campaign_name']; ?></span>
                                <?php endif; ?>
                                <span class="form-help">This name will be shown to members in reminders</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description (Optional)</label>
                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                    placeholder="Add any notes about this collection (optional)"
                                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <span class="form-help">For your reference only</span>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Create & Continue to Upload Members →
                                </button>
                                <a href="/public/group-admin/transactions.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">What Happens Next?</h4>
                    </div>
                    <div class="card-body">
                        <p>After creating the campaign:</p>
                        <ol style="padding-left: var(--spacing-lg);">
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Upload Members</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Add members using CSV file
                                </span>
                            </li>
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Send Reminders</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Use WhatsApp to notify members
                                </span>
                            </li>
                            <li style="margin: var(--spacing-sm) 0;">
                                <strong>Track Payments</strong><br>
                                <span style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    Mark payments as received
                                </span>
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">💡 Quick Tip</h4>
                    </div>
                    <div class="card-body">
                        <p>Include the month/year in the campaign name to easily identify it later.</p>
                        <p style="margin: 0;">You can create unlimited campaigns for different purposes!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
