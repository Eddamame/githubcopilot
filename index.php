<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

start_session();

// Already logged-in users go straight to the dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errors[] = 'Please enter both username and password.';
        } else {
            if (login($username, $password)) {
                set_flash_message('success', 'Welcome back, ' . htmlspecialchars($username) . '!');
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}

$flash      = get_flash_message();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Lovers Community – Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-12 col-sm-9 col-md-6 col-lg-4">

        <!-- Logo / Brand -->
        <div class="text-center mb-4">
            <span class="display-4">🐾</span>
            <h1 class="h3 fw-bold text-primary mt-2">Pet Lovers Community</h1>
            <p class="text-muted small">Sign in to your account</p>
        </div>

        <!-- Flash message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Error messages -->
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Login card -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="index.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="username" name="username" class="form-control"
                                   placeholder="Enter your username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   required autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-transparent">
                <span class="text-muted small">Don't have an account?</span>
                <a href="register.php" class="ms-1 small fw-semibold">Join the community</a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
