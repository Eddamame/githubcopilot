<?php
require_once __DIR__ . '/config.php';

/**
 * Handle a single file upload.
 *
 * @param array  $file            Entry from $_FILES (e.g. $_FILES['profile_photo'])
 * @param string $destination_dir Absolute path to the target directory (with trailing slash)
 * @return string|false  The generated filename on success, or false on failure.
 */
function upload_file(array $file, string $destination_dir): string|false {
    // No file selected is not an error – caller decides
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Size check
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    // MIME type check via finfo (reliable, not spoofable via extension)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        return false;
    }

    // Extension check
    $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($originalExt, ALLOWED_EXTENSIONS, true)) {
        return false;
    }

    // Ensure destination directory exists
    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    // Generate a unique, safe filename
    $newFilename = uniqid('img_', true) . '.' . $originalExt;
    $destination = $destination_dir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return false;
    }

    return $newFilename;
}

/**
 * Delete an uploaded file from its directory.
 *
 * @param string $filename Just the filename (not a path).
 * @param string $dir      Absolute path to the directory (with trailing slash).
 */
function delete_file(string $filename, string $dir): bool {
    if (empty($filename)) {
        return false;
    }
    // Strip any directory traversal attempts
    $filename = basename($filename);
    $path     = $dir . $filename;
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}
