<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

start_session();

if (is_logged_in()) {
    header('Location: ../dashboard.php');
    exit;
}

if (empty($_SESSION['onboarding']['step4_done'])) {
    header('Location: step4.php');
    exit;
}

$errors     = [];
$csrf_token = generate_csrf_token();
$ob         = $_SESSION['onboarding'];

// ── POST handler (final submit) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        // Double-check username hasn't been taken between steps
        if (username_exists($ob['username'])) {
            $errors[] = 'Sorry, that username was taken while you were registering. Please go back and choose another.';
        } else {
            $userData = [
                'username'          => $ob['username'],
                'password'          => $ob['password'],
                'full_name'         => $ob['full_name'],
                'email'             => $ob['email'],
                'phone'             => $ob['phone'] ?? '',
                'profile_photo'     => $ob['profile_photo'] ?? '',
                'pets'              => json_encode($ob['pets'] ?? []),
                'registration_date' => date('Y-m-d H:i:s'),
            ];

            if (create_user($userData)) {
                // Wipe onboarding data from session
                unset($_SESSION['onboarding']);
                set_flash_message('success', 'Registration complete! Welcome to the community – please log in.');
                header('Location: ../index.php');
                exit;
            } else {
                $errors[] = 'Could not save your account. Please try again.';
            }
        }
    }
}

// ── Helpers for display ───────────────────────────────────────────────────
$pets = $ob['pets'] ?? [];

function preview_profile_photo(string $filename): string {
    if (!empty($filename) && file_exists(PROFILES_DIR . $filename)) {
        return '../uploads/profiles/' . htmlspecialchars(basename($filename));
    }
    return 'https://ui-avatars.com/api/?name=You&size=200&background=e9ecef&color=6c757d';
}

function preview_pet_photo(string $filename): string {
    if (!empty($filename) && file_exists(PETS_DIR . $filename)) {
        return '../uploads/pets/' . htmlspecialchars(basename($filename));
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Step 5 of 5</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-7">

            <!-- Brand -->
            <div class="text-center mb-3">
                <span class="display-5">🐾</span>
                <h1 class="h4 fw-bold text-primary mt-1">Create Your Account</h1>
            </div>

            <!-- Progress bar -->
            <div class="mb-4">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Step 5 of 5</span><span>Confirmation</span>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-success" style="width:100%;"></div>
                </div>
                <div class="progress-steps d-flex justify-content-between mt-2">
                    <?php
                    $labels = ['Account','Personal','Photo','Pets','Confirm'];
                    foreach ($labels as $i => $label):
                        $cls = $i < 4 ? 'done' : 'active';
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

            <!-- Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-check2-circle text-success me-2"></i>Review Your Information
                </div>
                <div class="card-body p-4">

                    <!-- Profile photo + basic info -->
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <img src="<?= preview_profile_photo($ob['profile_photo'] ?? '') ?>"
                             alt="Profile photo"
                             class="rounded-circle profile-preview-md object-fit-cover flex-shrink-0">
                        <div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($ob['full_name']) ?></h5>
                            <p class="text-muted mb-0 small">@<?= htmlspecialchars($ob['username']) ?></p>
                        </div>
                    </div>

                    <!-- Details table -->
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal ps-0" style="width:35%">
                                    <i class="bi bi-envelope me-1"></i>Email
                                </th>
                                <td><?= htmlspecialchars($ob['email']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal ps-0">
                                    <i class="bi bi-telephone me-1"></i>Phone
                                </th>
                                <td><?= htmlspecialchars($ob['phone'] ?? '—') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pets summary -->
            <?php if (!empty($pets)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-heart-fill text-danger me-2"></i>Your Pets (<?= count($pets) ?>)
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <?php foreach ($pets as $pet): ?>
                        <div class="col-12 col-sm-6">
                            <div class="d-flex align-items-center gap-3 p-2 bg-light rounded">
                                <?php $petPhoto = preview_pet_photo($pet['photo'] ?? ''); ?>
                                <?php if ($petPhoto): ?>
                                    <img src="<?= $petPhoto ?>" alt=""
                                         class="rounded pet-thumb-sm object-fit-cover flex-shrink-0">
                                <?php else: ?>
                                    <div class="pet-thumb-sm rounded bg-secondary d-flex align-items-center justify-content-center flex-shrink-0">
                                        <span>🐾</span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="fw-semibold mb-0"><?= htmlspecialchars($pet['name']) ?></p>
                                    <?php if (!empty($pet['breed'])): ?>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($pet['breed']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($pet['age'])): ?>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars((string)$pet['age']) ?> yr<?= $pet['age'] != 1 ? 's' : '' ?> old</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-info-circle-fill"></i>
                <span>No pets added. You can always add pets later from your profile.</span>
            </div>
            <?php endif; ?>

            <!-- Submit form -->
            <form method="POST" action="step5.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="d-flex justify-content-between">
                    <a href="step4.php" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-1"></i> Previous
                    </a>
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Complete Registration
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/script.js"></script>
</body>
</html>
