<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

start_session();
require_login();

$currentUser = get_current_user_data();
$allUsers    = read_users();
$flash       = get_flash_message();

// Helper: resolve photo URL or return placeholder
function photo_url(string $filename, string $type = 'profiles'): string {
    if (!empty($filename)) {
        $path = BASE_PATH . '/uploads/' . $type . '/' . basename($filename);
        if (file_exists($path)) {
            return 'uploads/' . $type . '/' . htmlspecialchars(basename($filename));
        }
    }
    return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($filename ?: 'User') . '&size=200';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Pet Lovers Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">🐾 Pet Lovers Community</a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="text-white small me-2">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($currentUser['username']) ?>
            </span>
            <a href="profile.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-pencil-square me-1"></i>Edit Profile
            </a>
            <a href="logout.php" class="btn btn-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>Community Members
        </h2>
        <span class="badge bg-primary rounded-pill fs-6"><?= count($allUsers) ?> member<?= count($allUsers) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($allUsers)): ?>
        <div class="text-center py-5 text-muted">
            <span class="display-1">🐾</span>
            <p class="mt-3 fs-5">No members yet. Be the first to join!</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($allUsers as $user):
                $pets       = json_decode($user['pets'] ?? '[]', true) ?: [];
                $isCurrentUser = ($user['username'] === $currentUser['username']);
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm member-card <?= $isCurrentUser ? 'border-primary border-2' : '' ?>">
                    <?php if ($isCurrentUser): ?>
                        <div class="card-header bg-primary text-white text-center py-1 small fw-semibold">
                            <i class="bi bi-star-fill me-1"></i>You
                        </div>
                    <?php endif; ?>
                    <div class="card-body text-center p-3">
                        <!-- Profile photo -->
                        <img src="<?= photo_url($user['profile_photo']) ?>"
                             alt="<?= htmlspecialchars($user['full_name']) ?>"
                             class="rounded-circle member-avatar mb-3 object-fit-cover">

                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['full_name']) ?></h5>
                        <p class="text-muted small mb-2">@<?= htmlspecialchars($user['username']) ?></p>

                        <?php if (!empty($user['email'])): ?>
                            <p class="small text-muted mb-1">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                            </p>
                        <?php endif; ?>

                        <!-- Pets -->
                        <?php if (!empty($pets)): ?>
                            <div class="mt-2">
                                <p class="small fw-semibold mb-1 text-primary">
                                    <i class="bi bi-heart-fill me-1"></i>Pets (<?= count($pets) ?>)
                                </p>
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    <?php foreach ($pets as $pet): ?>
                                        <span class="badge bg-light text-dark border pet-badge" title="<?= htmlspecialchars($pet['breed'] ?? '') ?>">
                                            🐾 <?= htmlspecialchars($pet['name'] ?? '') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="small text-muted mt-2 mb-0"><em>No pets listed</em></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($isCurrentUser): ?>
                        <div class="card-footer bg-transparent text-center border-0 pb-3">
                            <a href="profile.php" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-pencil-square me-1"></i>Edit My Profile
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
