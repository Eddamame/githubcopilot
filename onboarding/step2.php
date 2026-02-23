<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';

start_session();

if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

// Must have completed step 1
if (empty($_SESSION['onboarding']['step1_done'])) {
    header('Location: step1.php');
    exit;
}

$errors     = [];
$csrf_token = generate_csrf_token();

// ── POST handler ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');

        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!empty($phone) && !preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) {
            $errors[] = 'Phone number format is invalid (digits, spaces, dashes, parentheses allowed).';
        }

        if (empty($errors)) {
            $_SESSION['onboarding']['full_name']  = $full_name;
            $_SESSION['onboarding']['email']      = $email;
            $_SESSION['onboarding']['phone']      = $phone;
            $_SESSION['onboarding']['step2_done'] = true;
            header('Location: step3.php');
            exit;
        }
    }
}

// Pre-fill
$ob = $_SESSION['onboarding'];
$val = [
    'full_name' => htmlspecialchars($_POST['full_name'] ?? ($ob['full_name'] ?? '')),
    'email'     => htmlspecialchars($_POST['email']     ?? ($ob['email']     ?? '')),
    'phone'     => htmlspecialchars($_POST['phone']     ?? ($ob['phone']     ?? '')),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Step 2 of 5</title>
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
                <span>Step 2 of 5</span><span>Personal Information</span>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar bg-primary" style="width:40%;"></div>
            </div>
            <div class="progress-steps d-flex justify-content-between mt-2">
                <?php
                $labels = ['Account','Personal','Photo','Pets','Confirm'];
                foreach ($labels as $i => $label):
                    $cls = $i < 1 ? 'done' : ($i === 1 ? 'active' : '');
                ?>
                <div class="step-dot <?= $cls ?>">
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
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="step2.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="mb-3">
                        <label for="full_name" class="form-label fw-semibold">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                   value="<?= $val['full_name'] ?>"
                                   placeholder="Your full name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">
                            Email Address <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="<?= $val['email'] ?>"
                                   placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?= $val['phone'] ?>"
                                   placeholder="+1 555 000 0000">
                        </div>
                        <div class="form-text">Optional</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="step1.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-1"></i> Previous
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/script.js"></script>
</body>
</html>
