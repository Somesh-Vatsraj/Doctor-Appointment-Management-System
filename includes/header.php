<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$role = $_SESSION['role'] ?? 'guest';
$name = $_SESSION['user_name'] ?? 'User';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicare - Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <?php if (isset($_SESSION['user_id'])): ?>
    <aside class="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <a href="#" class="text-decoration-none text-primary fw-bold fs-4 d-flex align-items-center">
                <i class="bi bi-hospital me-2"></i> MediCare
            </a>
        </div>
        <ul class="sidebar-nav">
            <?php if ($role === 'admin'): ?>
                <li><a href="/admin/dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="/admin/doctors.php" class="nav-link"><i class="bi bi-person-badge"></i> Doctors</a></li>
                <li><a href="/admin/patients.php" class="nav-link"><i class="bi bi-people"></i> Patients</a></li>
                <li><a href="/admin/appointments.php" class="nav-link"><i class="bi bi-calendar2-check"></i> Appointments</a></li>
                <li><a href="/admin/bills.php" class="nav-link"><i class="bi bi-receipt"></i> Billing & Revenue</a></li>
            <?php elseif ($role === 'doctor'): ?>
                <li><a href="/doctor/dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="/doctor/appointments.php" class="nav-link"><i class="bi bi-calendar2-week"></i> My Appointments</a></li>
            <?php elseif ($role === 'patient'): ?>
                <li><a href="/patient/dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="/patient/book-appointment.php" class="nav-link"><i class="bi bi-calendar-plus"></i> Book Appointment</a></li>
                <li><a href="/patient/appointments.php" class="nav-link"><i class="bi bi-calendar2-check"></i> My Appointments</a></li>
                <li><a href="/patient/prescriptions.php" class="nav-link"><i class="bi bi-file-medical"></i> Prescriptions</a></li>
                <li><a href="/patient/bills.php" class="nav-link"><i class="bi bi-credit-card"></i> Invoices & Bills</a></li>
            <?php endif; ?>
            <li class="mt-4"><hr class="dropdown-divider"></li>
            <li><a href="/auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>
    <?php endif; ?>

    <div class="main-content">
        <?php if (isset($_SESSION['user_id'])): ?>
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <button class="btn btn-light d-md-none" id="sidebarToggle"><i class="bi bi-list fs-4"></i></button>
            <div class="fw-semibold text-secondary">Logged in as: <span class="text-dark fw-bold"><?= e(ucfirst($role)) ?></span></div>
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 me-2 text-primary"></i> <?= e($name) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item text-danger" href="/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </header>
        <?php endif; ?>

        <main class="p-4">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>