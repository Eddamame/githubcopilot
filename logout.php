<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

start_session();
logout();

set_flash_message('success', 'You have been logged out successfully.');
header('Location: index.php');
exit;
