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

if (empty($_SESSION['onboarding']['step3_done'])) {
    header('Location: step3.php');
    exit;
}

$errors     = [];
$csrf_token = generate_csrf_token();

// ── POST handler ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $petNames  = $_POST['pet_name']  ?? [];
        $petBreeds = $_POST['pet_breed'] ?? [];
        $petAges   = $_POST['pet_age']   ?? [];

        $savedPets     = $_SESSION['onboarding']['pets'] ?? [];
        $processedPets = [];

        for ($i = 0; $i < count($petNames); $i++) {
            $petName = trim($petNames[$i] ?? '');
            if (empty($petName)) {
                continue; // skip blank entries
            }

            // Preserve previously uploaded photo for this index
            $petPhoto = $savedPets[$i]['photo'] ?? '';

            // Handle new upload for this pet slot
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
                    // Delete old photo for this slot if re-uploaded
                    if (!empty($petPhoto)) {
                        delete_file($petPhoto, PETS_DIR);
                    }
                    $petPhoto = $uploaded;
                } elseif ($petFile['error'] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Pet photo for "' . htmlspecialchars($petName) . '" failed. Use JPG/PNG/GIF under 5 MB.';
                }
            }

            $processedPets[] = [
                'name'  => htmlspecialchars(strip_tags($petName)),
                'breed' => htmlspecialchars(strip_tags(trim($petBreeds[$i] ?? ''))),
                'age'   => intval($petAges[$i] ?? 0),
                'photo' => $petPhoto,
            ];
        }

        if (empty($errors)) {
            $_SESSION['onboarding']['pets']      = $processedPets;
            $_SESSION['onboarding']['step4_done'] = true;
            header('Location: step5.php');
            exit;
        }
    }
}

// Restore previous pet data (in case of error or back-navigation)
$savedPets = $_SESSION['onboarding']['pets'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Step 4 of 5</title>
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
                    <span>Step 4 of 5</span><span>Your Pets</span>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-primary" style="width:80%;"></div>
                </div>
                <div class="progress-steps d-flex justify-content-between mt-2">
                    <?php
                    $labels = ['Account','Personal','Photo','Pets','Confirm'];
                    foreach ($labels as $i => $label):
                        $cls = $i < 3 ? 'done' : ($i === 3 ? 'active' : '');
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
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-heart-fill text-danger me-2"></i>Tell us about your pets</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPetForm()">
                        <i class="bi bi-plus-circle me-1"></i>Add Another Pet
                    </button>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        You can add one or more pets. All fields except name are optional.
                    </p>

                    <form method="POST" action="step4.php" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div id="petsContainer">
                            <?php if (!empty($savedPets)): ?>
                                <?php foreach ($savedPets as $idx => $pet): ?>
                                <div class="pet-entry card border-0 bg-light p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">Pet #<?= $idx + 1 ?></h6>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="removePet(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="pet_name[]" class="form-control form-control-sm"
                                                   value="<?= htmlspecialchars($pet['name']) ?>" required>
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
                                            <?php if (!empty($pet['photo']) && file_exists(PETS_DIR . $pet['photo'])): ?>
                                                <div class="mb-1">
                                                    <img src="../uploads/pets/<?= htmlspecialchars(basename($pet['photo'])) ?>"
                                                         alt="" class="pet-thumb-sm rounded object-fit-cover">
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="pet_photo[]" class="form-control form-control-sm"
                                                   accept="image/jpeg,image/png,image/gif">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Default first pet form -->
                                <div class="pet-entry card border-0 bg-light p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-semibold mb-0">Pet #1</h6>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="removePet(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
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
                        </div><!-- /#petsContainer -->

                        <div class="d-flex justify-content-between mt-3">
                            <a href="step3.php" class="btn btn-outline-secondary px-4">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/script.js"></script>
</body>
</html>
