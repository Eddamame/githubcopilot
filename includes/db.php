<?php
require_once __DIR__ . '/config.php';

/**
 * Read all users from the CSV file.
 *
 * @return array Associative array indexed by username.
 */
function read_users(): array {
    if (!file_exists(USERS_CSV)) {
        return [];
    }

    $users = [];
    $handle = fopen(USERS_CSV, 'r');
    if ($handle === false) {
        return [];
    }

    // Read (and discard) the header row
    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        return [];
    }

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 8) {
            continue;
        }
        $user = [
            'username'          => $row[0],
            'password'          => $row[1],
            'full_name'         => $row[2],
            'email'             => $row[3],
            'phone'             => $row[4],
            'profile_photo'     => $row[5],
            'pets'              => $row[6],
            'registration_date' => $row[7],
        ];
        $users[$user['username']] = $user;
    }

    fclose($handle);
    return $users;
}

/**
 * Write all users back to the CSV file, overwriting previous content.
 *
 * @param array $users Associative array indexed by username.
 */
function write_users(array $users): bool {
    $dir = dirname(USERS_CSV);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $handle = fopen(USERS_CSV, 'w');
    if ($handle === false) {
        return false;
    }

    // Header
    fputcsv($handle, ['username', 'password', 'full_name', 'email', 'phone',
                       'profile_photo', 'pets', 'registration_date']);

    foreach ($users as $user) {
        fputcsv($handle, [
            $user['username'],
            $user['password'],
            $user['full_name'],
            $user['email'],
            $user['phone'],
            $user['profile_photo'],
            $user['pets'],
            $user['registration_date'],
        ]);
    }

    fclose($handle);
    return true;
}

/**
 * Return a single user by username, or null if not found.
 */
function get_user(string $username): ?array {
    $users = read_users();
    return $users[$username] ?? null;
}

/**
 * Add a new user to the CSV.
 *
 * @param array $data Must include all schema fields.
 * @return bool True on success.
 */
function create_user(array $data): bool {
    $users = read_users();
    if (isset($users[$data['username']])) {
        return false; // already exists
    }
    $users[$data['username']] = $data;
    return write_users($users);
}

/**
 * Update an existing user's data in the CSV.
 *
 * @param string $username The user to update.
 * @param array  $data     Fields to merge / replace.
 * @return bool True on success.
 */
function update_user(string $username, array $data): bool {
    $users = read_users();
    if (!isset($users[$username])) {
        return false;
    }
    $users[$username] = array_merge($users[$username], $data);
    $users[$username]['username'] = $username; // username is immutable
    return write_users($users);
}

/**
 * Delete a user from the CSV.
 *
 * @param string $username
 * @return bool True on success.
 */
function delete_user(string $username): bool {
    $users = read_users();
    if (!isset($users[$username])) {
        return false;
    }
    unset($users[$username]);
    return write_users($users);
}

/**
 * Check whether a username already exists in the CSV.
 */
function username_exists(string $username): bool {
    $users = read_users();
    return isset($users[$username]);
}
