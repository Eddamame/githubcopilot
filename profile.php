<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

start_session();
require_login();

$currentUser = get_current_user_data();
$username    = $currentUser['username'];
$userData    = get_user($username);
$errors      = [];
$success     = '';
$csrf_token  = generate_csrf_token();

// Decode pets from JSON
$pets = json_decode($userData['pets'] ?? '[]', true) ?: [];

// ── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? 'save';

        // ── DELETE ACCOUNT ───────────────────────────────────────────────────
        if ($action === 'delete') {
            // Clean up uploaded files
            if (!empty($userData['profile_photo'])) {
                delete_file($userData['profile_photo'], PROFILES_DIR);
            }
            foreach ($pets as $pet) {
                if (!empty($pet['photo'])) {
                    delete_file($pet['photo'], PETS_DIR);
                }
            }
            delete_user($username);
            logout();
            set_flash_message('info', 'Your account has been deleted.');
            header('Location: index.php');
            exit;
        }

        // ── SAVE PROFILE ─────────────────────────────────────────────────────
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');
        $newPass   = $_POST['new_password']    ?? '';
        $confPass  = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (!empty($phone) && !preg_match('/^\+?[\d\s\-()]{7,20}$/', $phone)) {
            $errors[] = 'Phone number format is invalid.';
        }
        if (!empty($newPass)) {
            if (strlen($newPass) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($newPass !== $confPass) {
                $errors[] = 'Passwords do not match.';
            }
        }

        // Profile photo upload
        $profilePhoto = $userData['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploaded = upload_file($_FILES['profile_photo'], PROFILES_DIR);
            if ($uploaded === false) {
                $errors[] = 'Profile photo upload failed. Use JPG/PNG/GIF under 5 MB.';
            } else {
                // Delete old photo
                if (!empty($profilePhoto)) {
                    delete_file($profilePhoto, PROFILES_DIR);
                }
                $profilePhoto = $uploaded;
            }
        }

        // Pet data
        $petNames   = $_POST['pet_name']   ?? [];
        $petBreeds  = $_POST['pet_breed']  ?? [];
        $petAges    = $_POST['pet_age']    ?? [];
        $updatedPets = [];

        $petCount = count($petNames);
        for ($i = 0; $i < $petCount; $i++) {
            $petName = trim($petNames[$i] ?? '');
            if (empty($petName)) {
                continue;
            }
            $petPhoto = $pets[$i]['photo'] ?? ''; // keep existing photo by default

            if (!empty($_FILES['pet_photo']['name'][$i])) {
                $petFile = [
                    'name'     => $_FILES['pet_photo']['name'][$i],
                    'type'     => $_FILES['pet_photo']['type'][$i],
                    'tmp_name' => $_FILES['pet_photo']['tmp_name'][$i],
                    'error'    => $_FILES['pet_photo']['error'][$i],
                    'size'     => $_FILES['pet_photo']['size'][$i],
                ];
                $uploaded = upload_file($petFile, PETS_DIR);
                if ($uploaded !== false) {
                    // Delete old pet photo
                    if (!empty($petPhoto)) {
                        delete_file($petPhoto, PETS_DIR);
                    }
                    $petPhoto = $uploaded;
                }
            }

            $updatedPets[] = [
                'name'   => htmlspecialchars(strip_tags($petName)),
                'breed'  => htmlspecialchars(strip_tags(trim($petBreeds[$i] ?? ''))),
                'age'    => intval($petAges[$i] ?? 0),
                'photo'  => $petPhoto,
            ];
        }

        if (empty($errors)) {
            $updateData = [
                'full_name'     => $full_name,
                'email'         => $email,
                'phone'         => $phone,
                'profile_photo' => $profilePhoto,
                'pets'          => json_encode($updatedPets),
            ];
            if (!empty($newPass)) {
                $updateData['password'] = password_hash($newPass, PASSWORD_DEFAULT);
            }

            if (update_user($username, $updateData)) {
                // Refresh session
                $refreshed = get_user($username);
                $_SESSION['user'] = [
                    'username'      => $refreshed['username'],
                    'full_name'     => $refreshed['full_name'],
                    'email'         => $refreshed['email'],
                    'phone'         => $refreshed['phone'],
                    'profile_photo' => $refreshed['profile_photo'],
                    'pets'          => $refreshed['pets'],
                ];
                set_flash_message('success', 'Profile updated successfully.');
                header('Location: profile.php');
                exit;
            } else {
                $errors[] = 'Failed to save profile. Please try again.';
            }
        }

        // Re-read fresh data and re-decode pets on error
        $userData = get_user($username);
        $pets     = json_decode($userData['pets'] ?? '[]', true) ?: [];
    }
}

$flash = get_flash_message();

// Helper: resolve photo URL or placeholder
function photo_url_p(string $filename, string $type = 'profiles', string $name = 'User'): string {
    if (!empty($filename)) {
        $path = BASE_PATH . '/uploads/' . $type . '/' . basename($filename);
        if (file_exists($path)) {
            return '../uploads/' . $type . '/' . htmlspecialchars(basename($filename));
        }
    }
    return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($name) . '&size=200';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile – Pet Lovers Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🐾 Pet Lovers Community</a>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-grid me-1"></i>Dashboard
            </a>
            <a href="logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h2 class="fw-bold mb-4">
                <i class="bi bi-person-gear text-primary me-2"></i>Edit Profile
            </h2>

            <!-- Flash -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

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

            <form method="POST" action="profile.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="action" value="save">

                <!-- Account Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-person-badge me-2 text-primary"></i>Account Information
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username <span class="text-muted">(cannot be changed)</span></label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($username) ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="form-control"
                                   value="<?= htmlspecialchars($userData['full_name']) ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($userData['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($userData['phone']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password change -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-key me-2 text-primary"></i>Change Password <span class="text-muted small">(leave blank to keep current)</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label fw-semibold">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                       placeholder="Min. 8 characters" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                       autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile photo -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-camera me-2 text-primary"></i>Profile Photo
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img id="profilePreview"
                                 src="<?= photo_url_p($userData['profile_photo'], 'profiles', $userData['full_name']) ?>"
                                 alt="Profile preview" class="rounded-circle profile-preview-sm object-fit-cover">
                            <div class="flex-grow-1">
                                <label for="profile_photo" class="form-label fw-semibold mb-1">Upload New Photo</label>
                                <input type="file" id="profile_photo" name="profile_photo" class="form-control"
                                       accept="image/jpeg,image/png,image/gif"
                                       onchange="previewImage(this, 'profilePreview')">
                                <div class="form-text">JPG / PNG / GIF, max 5 MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pets -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold bg-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-heart-fill me-2 text-danger"></i>My Pets</span>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPetForm()">
                            <i class="bi bi-plus-circle me-1"></i>Add Another Pet
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="petsContainer">
                            <?php foreach ($pets as $idx => $pet): ?>
                            <div class="pet-entry card border-0 bg-light p-3 mb-3" id="pet-<?= $idx ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-semibold mb-0">Pet #<?= $idx + 1 ?></h6>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="removePet(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Pet Name <span class="text-danger">*</span></label>
                                        <input type="text" name="pet_name[]" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($pet['name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Breed</label>
                                        <input type="text" name="pet_breed[]" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($pet['breed'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Age (yrs)</label>
                                        <input type="number" name="pet_age[]" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars((string)($pet['age'] ?? '')) ?>" min="0" max="100">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Photo</label>
                                        <?php if (!empty($pet['photo'])): ?>
                                            <div class="mb-1">
                                                <img src="<?= photo_url_p($pet['photo'], 'pets', $pet['name']) ?>"
                                                     alt="" class="pet-thumb-sm rounded object-fit-cover">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="pet_photo[]" class="form-control form-control-sm"
                                               accept="image/jpeg,image/png,image/gif">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <?php if (empty($pets)): ?>
                            <div class="pet-entry card border-0 bg-light p-3 mb-3" id="pet-0">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-semibold mb-0">Pet #1</h6>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="removePet(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Pet Name <span class="text-danger">*</span></label>
                                        <input type="text" name="pet_name[]" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Breed</label>
                                        <input type="text" name="pet_breed[]" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Age (yrs)</label>
                                        <input type="number" name="pet_age[]" class="form-control form-control-sm" min="0" max="100">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">Photo</label>
                                        <input type="file" name="pet_photo[]" class="form-control form-control-sm"
                                               accept="image/jpeg,image/png,image/gif">
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        <i class="bi bi-check-circle me-1"></i>Save Changes
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>

            <!-- Danger zone -->
            <div class="card border-danger shadow-sm mb-5">
                <div class="card-header bg-danger text-white fw-semibold">
                    <i class="bi bi-exclamation-triangle me-2"></i>Danger Zone
                </div>
                <div class="card-body">
                    <p class="mb-3 text-muted">Deleting your account is permanent and cannot be undone.</p>
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash me-1"></i>Delete My Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to permanently delete your account?</p>
                <p class="text-danger fw-semibold mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action"     value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Yes, Delete My Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
