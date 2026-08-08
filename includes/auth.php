<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function check_auth(array $allowed_roles = []): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        set_flash('danger', 'Please login to access this area.');
        redirect('../auth/login.php');
    }

    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles, true)) {
        set_flash('danger', 'Unauthorized access denied.');
        redirect('../index.php');
    }
}