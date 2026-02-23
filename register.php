<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

start_session();

// Initialise (or reset) the onboarding data in the session
$_SESSION['onboarding'] = [];

header('Location: onboarding/step1.php');
exit;
