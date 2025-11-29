<?php
/**
 * Add New User (Group Admin)
 * GetToKnow Community App
 */

require_once __DIR__ . '/../../config/config.php';
AuthMiddleware::requireRole('admin');

$error = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!AuthMiddleware::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $fullName = Validator::sanitizeString($_POST['full_name'] ?? '');
        $email = Validator::sanitizeString($_POST['email'] ?? '');

        // Validate
        $validator = new Validator();
        $validator->required('mobile', $mobile, 'Mobile Number')
                  ->mobile('mobile', $mobile)
                  ->required('password', $password, 'Password')
                  ->minLength('password', $password, 6)
                  ->required('full_name', $fullName, 'Full Name')
                  ->email('email', $email);

        if ($validator->fails()) {
            $errors = $validator->getErrors();
        } else {
            // Check if mobile exists
            $userModel = new User();
            if ($userModel->mobileExists($mobile)) {
                $error = 'Mobile number already exists';
            } else {
                // Create user
                $userId = $userModel->create([
                    'mobile_number' => $mobile,
                    'password' => $password,
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => 'group_admin',
                    'status' => 'active'
                ]);

                if ($userId) {
                    header("Location: /public/admin/users.php?success=created");
                    exit;
                } else {
                    $error = 'Failed to create user';
                }
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
    <title>Add User - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <link rel="stylesheet" href="/public/css/enhancements.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-xl) 0;
            margin-bottom: var(--spacing-xl);
        }
        .header h1 { color: white; margin: 0; }
        .nav-menu {
            background: var(--white);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }
        .nav-menu a {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
        }
        .nav-menu a:hover {
            background: var(--gray-100);
            text-decoration: none;
        }
        .mobile-input-wrapper {
            position: relative;
        }
        .mobile-prefix {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 500;
            color: var(--gray-600);
            pointer-events: none;
        }
        .mobile-input-wrapper .form-control {
            padding-left: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><?php echo APP_NAME; ?> - Create Group Admin</h1>
        </div>
    </div>

    <div class="container main-content">
        <nav class="nav-menu">
            <ul>
                <li><a href="/public/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/public/admin/users.php" style="font-weight: 600;">Manage Users</a></li>
                <li><a href="/public/admin/communities.php">Manage Communities</a></li>
                <li><a href="/public/logout.php">Logout</a></li>
            </ul>
        </nav>

        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Group Admin</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label form-label-required">Mobile Number</label>
                                <div class="mobile-input-wrapper">
                                    <span class="mobile-prefix">+91</span>
                                    <input
                                        type="tel"
                                        name="mobile"
                                        class="form-control <?php echo isset($errors['mobile']) ? 'is-invalid' : ''; ?>"
                                        placeholder="Enter 10-digit mobile number"
                                        maxlength="10"
                                        pattern="[6-9][0-9]{9}"
                                        value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                                        required
                                    >
                                </div>
                                <?php if (isset($errors['mobile'])): ?>
                                    <span class="form-error"><?php echo $errors['mobile']; ?></span>
                                <?php endif; ?>
                                <span class="form-help">This will be used for login</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Password</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                    placeholder="Minimum 6 characters"
                                    minlength="6"
                                    required
                                >
                                <?php if (isset($errors['password'])): ?>
                                    <span class="form-error"><?php echo $errors['password']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label form-label-required">Full Name</label>
                                <input
                                    type="text"
                                    name="full_name"
                                    class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                                    placeholder="Enter full name"
                                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                    required
                                >
                                <?php if (isset($errors['full_name'])): ?>
                                    <span class="form-error"><?php echo $errors['full_name']; ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email (Optional)</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                    placeholder="Enter email address"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                >
                                <?php if (isset($errors['email'])): ?>
                                    <span class="form-error"><?php echo $errors['email']; ?></span>
                                <?php endif; ?>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div style="display: flex; gap: var(--spacing-md);">
                                <button type="submit" class="btn btn-primary">Create Group Admin</button>
                                <a href="/public/admin/users.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Information</h4>
                    </div>
                    <div class="card-body">
                        <h5>Group Admin Role</h5>
                        <p>Group Admins can:</p>
                        <ul>
                            <li>Manage their assigned community</li>
                            <li>Create lottery events</li>
                            <li>Create transaction campaigns</li>
                            <li>Track payments & collections</li>
                            <li>Generate reports</li>
                        </ul>

                        <h5 style="margin-top: var(--spacing-lg);">Next Steps</h5>
                        <p>After creating:</p>
                        <ol>
                            <li>Assign to a community</li>
                            <li>Share login credentials</li>
                            <li>Guide them through features</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-format mobile number
        document.querySelector('input[name="mobile"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
