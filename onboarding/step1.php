<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

start_session();

// Logged-in users don't need onboarding
if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

// Ensure onboarding array exists
if (!isset($_SESSION['onboarding'])) {
    $_SESSION['onboarding'] = [];
}

$errors     = [];
$csrf_token = generate_csrf_token();

// ── POST handler ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Username validation
        if (!preg_match('/^[a-zA-Z0-9]{3,20}$/', $username)) {
            $errors[] = 'Username must be 3–20 alphanumeric characters.';
        } elseif (username_exists($username)) {
            $errors[] = 'That username is already taken. Please choose another.';
        }

        // Password validation
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $_SESSION['onboarding']['username'] = $username;
            $_SESSION['onboarding']['password'] = password_hash($password, PASSWORD_DEFAULT);
            $_SESSION['onboarding']['step1_done'] = true;
            header('Location: step2.php');
            exit;
        }
    }
}

// Pre-fill values on error
$val_username = htmlspecialchars($_POST['username'] ?? ($_SESSION['onboarding']['username'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Step 1 of 5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center min-vh-100 py-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">

        <!-- Brand -->
        <div class="text-center mb-3">
            <span class="display-5">🐾</span>
            <h1 class="h4 fw-bold text-primary mt-1">Create Your Account</h1>
        </div>

        <!-- Progress bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Step 1 of 5</span><span>Username &amp; Password</span>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar bg-primary" style="width:20%;"></div>
            </div>
            <div class="progress-steps d-flex justify-content-between mt-2">
                <?php
                $labels = ['Account','Personal','Photo','Pets','Confirm'];
                foreach ($labels as $i => $label):
                    $active = ($i === 0) ? 'active' : '';
                ?>
                <div class="step-dot <?= $active ?>">
                    <span class="dot"></span>
                    <span class="step-label"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Errors -->
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="step1.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">
                            Username <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="text" id="username" name="username" class="form-control"
                                   value="<?= $val_username ?>"
                                   placeholder="e.g. petlover42"
                                   pattern="[a-zA-Z0-9]{3,20}"
                                   maxlength="20" required autocomplete="username">
                        </div>
                        <div class="form-text">3–20 alphanumeric characters, no spaces.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="At least 8 characters"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-control"
                                   placeholder="Repeat your password"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center bg-transparent py-2">
                <span class="text-muted small">Already have an account?</span>
                <a href="../index.php" class="ms-1 small fw-semibold">Sign in</a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/script.js"></script>
</body>
</html>
