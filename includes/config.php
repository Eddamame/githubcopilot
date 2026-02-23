<?php
// Base path of the application
define('BASE_PATH', dirname(__DIR__));

// Directory constants
define('UPLOAD_DIR',   BASE_PATH . '/uploads/');
define('DATA_DIR',     BASE_PATH . '/data/');

// CSV file path
define('USERS_CSV',    DATA_DIR  . 'users.csv');

// Session name
define('SESSION_NAME', 'pet_community');

// Upload limits
define('MAX_FILE_SIZE',    5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Upload sub-dirs
define('PROFILES_DIR', UPLOAD_DIR . 'profiles/');
define('PETS_DIR',     UPLOAD_DIR . 'pets/');

// Application URL base (auto-detect)
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
