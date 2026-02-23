<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/upload.php';

start_session();

if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

if (empty($_SESSION['onboarding']['step2_done'])) {
    header('Location: step2.php');
    exit;
}

$errors     = [];
$csrf_token = generate_csrf_token();

// ── POST handler ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $profilePhoto = $_SESSION['onboarding']['profile_photo'] ?? '';

        // Upload is optional – skip if no file chosen
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploaded = upload_file($_FILES['profile_photo'], PROFILES_DIR);
            if ($uploaded === false) {
                $errors[] = 'Upload failed. Please use a JPG, PNG, or GIF image under 5 MB.';
            } else {
                // Delete previously uploaded photo if the user re-uploads at this step
                if (!empty($profilePhoto)) {
                    delete_file($profilePhoto, PROFILES_DIR);
                }
                $profilePhoto = $uploaded;
            }
        }

        if (empty($errors)) {
            $_SESSION['onboarding']['profile_photo'] = $profilePhoto;
            $_SESSION['onboarding']['step3_done']    = true;
            header('Location: step4.php');
            exit;
        }
    }
}

$existingPhoto = $_SESSION['onboarding']['profile_photo'] ?? '';
$previewSrc    = '';
if (!empty($existingPhoto) && file_exists(PROFILES_DIR . $existingPhoto)) {
    $previewSrc = '../uploads/profiles/' . htmlspecialchars(basename($existingPhoto));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Step 3 of 5</title>
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
                <span>Step 3 of 5</span><span>Profile Photo</span>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar bg-primary" style="width:60%;"></div>
            </div>
            <div class="progress-steps d-flex justify-content-between mt-2">
                <?php
                $labels = ['Account','Personal','Photo','Pets','Confirm'];
                foreach ($labels as $i => $label):
                    $cls = $i < 2 ? 'done' : ($i === 2 ? 'active' : '');
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
                <form method="POST" action="step3.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="text-center mb-4">
                        <div class="photo-preview-wrapper mx-auto mb-3">
                            <img id="profilePreview"
                                 src="<?= $previewSrc ?: 'https://ui-avatars.com/api/?name=You&size=200&background=e9ecef&color=6c757d' ?>"
                                 alt="Profile preview"
                                 class="rounded-circle profile-preview-lg object-fit-cover">
                        </div>
                        <p class="text-muted small mb-3">Upload a photo so other members can recognise you.</p>

                        <label for="profile_photo" class="form-label fw-semibold">
                            Profile Photo <span class="text-muted">(optional)</span>
                        </label>
                        <input type="file" id="profile_photo" name="profile_photo"
                               class="form-control"
                               accept="image/jpeg,image/png,image/gif"
                               onchange="previewImage(this, 'profilePreview')">
                        <div class="form-text">JPG / PNG / GIF, max 5 MB</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="step2.php" class="btn btn-outline-secondary px-4">
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
