<?php
/**
 * Login Page
 * GetToKnow Community App
 */

require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (AuthMiddleware::isAuthenticated()) {
    $role = AuthMiddleware::getUserRole();
    $redirect = ($role === 'admin') ? '/public/admin/dashboard.php' : '/public/group-admin/dashboard.php';
    header("Location: {$redirect}");
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = Validator::sanitizeString($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    $validator = new Validator();
    $validator->required('mobile', $mobile, 'Mobile Number')
              ->mobile('mobile', $mobile)
              ->required('password', $password, 'Password');

    if ($validator->passes()) {
        $userModel = new User();
        $user = $userModel->authenticate($mobile, $password);

        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['mobile_number'] = $user['mobile_number'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Get community ID for group admins
            if ($user['role'] === 'group_admin') {
                $_SESSION['community_id'] = $userModel->getCommunityId($user['user_id']);
            }

            // Redirect based on role
            $redirect = ($user['role'] === 'admin') ? '/public/admin/dashboard.php' : '/public/group-admin/dashboard.php';
            header("Location: {$redirect}");
            exit;

        } else {
            $error = 'Invalid mobile number or password';
        }
    } else {
        $errors = $validator->getErrors();
        $error = implode(', ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/public/css/main.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: var(--spacing-md);
        }

        .login-card {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--spacing-2xl);
        }

        .logo-section {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .logo-section h1 {
            color: var(--primary-color);
            font-size: var(--font-size-3xl);
            margin-bottom: var(--spacing-sm);
        }

        .logo-section p {
            color: var(--gray-600);
            font-size: var(--font-size-base);
            margin: 0;
        }

        .mobile-input {
            position: relative;
        }

        .mobile-input::before {
            content: '+91';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: var(--font-size-base);
            font-weight: 500;
            color: var(--gray-600);
            pointer-events: none;
        }

        .mobile-input .form-control {
            padding-left: 60px;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-500);
            font-size: var(--font-size-sm);
            user-select: none;
        }

        .password-wrapper {
            position: relative;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Community Management Platform</p>
            </div>

            <?php if ($timeout): ?>
                <div class="alert alert-warning">
                    Your session has expired. Please login again.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="mobile" class="form-label form-label-required">Mobile Number</label>
                    <div class="mobile-input">
                        <input
                            type="tel"
                            id="mobile"
                            name="mobile"
                            class="form-control"
                            placeholder="Enter 10-digit mobile number"
                            maxlength="10"
                            pattern="[6-9][0-9]{9}"
                            value="<?php echo htmlspecialchars($mobile ?? ''); ?>"
                            required
                            autofocus
                        >
                    </div>
                    <span class="form-help">Enter your registered mobile number</span>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label form-label-required">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            required
                        >
                        <span class="password-toggle" onclick="togglePassword()">Show</span>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Login
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="mb-0" style="color: var(--gray-600); font-size: var(--font-size-sm);">
                    Need help? Contact your administrator
                </p>
            </div>
        </div>

        <div class="text-center mt-3">
            <p style="color: white; font-size: var(--font-size-sm);">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
            </p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = 'Show';
            }
        }

        // Auto-format mobile number (only digits)
        document.getElementById('mobile').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const mobile = document.getElementById('mobile').value;
            const password = document.getElementById('password').value;

            if (mobile.length !== 10 || !mobile.match(/^[6-9][0-9]{9}$/)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit mobile number starting with 6-9');
                return;
            }

            if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                e.preventDefault();
                alert('Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long');
                return;
            }
        });
    </script>
</body>
</html>
