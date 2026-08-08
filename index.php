<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['role'])) {
    match ($_SESSION['role']) {
        'admin'   => redirect('/admin/dashboard.php'),
        'doctor'  => redirect('/doctor/dashboard.php'),
        'patient' => redirect('/patient/dashboard.php'),
        default   => redirect('/auth/login.php')
    };
}
redirect('/auth/login.php');