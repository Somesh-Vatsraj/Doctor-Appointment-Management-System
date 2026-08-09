<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'doctor') {
                $doc = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $doc->execute([$user['id']]);
                $_SESSION['doctor_id'] = $doc->fetchColumn();
            } elseif ($user['role'] === 'patient') {
                $pat = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
                $pat->execute([$user['id']]);
                $_SESSION['patient_id'] = $pat->fetchColumn();
            }

            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('/index.php');
        } else {
            $error = 'Invalid email address or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - MediCare Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="container" style="max-width: 440px;">
    <div class="card border-0 shadow-sm p-4 rounded-4">
        <div class="text-center mb-4">
            <i class="bi bi-hospital text-primary fs-1"></i>
            <h4 class="fw-bold mt-2">Sign in to MediCare</h4>
            <p class="text-muted small">Doctor Appointment Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@domain.com">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
        </form>

        <div class="text-center mt-4">
            <span class="text-muted small">Don't have an account?</span>
            <a href="register.php" class="small fw-bold text-decoration-none">Register as Patient</a>
        </div>
        <div class="alert alert-info mt-3 p-2 small text-center">
            Demo: <b>admin@medicare.com</b> | <b>123456</b>
            Demo: <b>sk@medicare.com</b> | <b>123456</b>
            Demo: <b>ss@medicare.com</b> | <b>123456</b>
            Demo: <b>pt@medicare.com</b> | <b>123456</b>
        </div>
    </div>
</div>
</body>
</html>
