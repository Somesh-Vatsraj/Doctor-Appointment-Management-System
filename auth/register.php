<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security check failed. Please refresh.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = $_POST['gender'] ?? 'Male';
        $dob = $_POST['date_of_birth'] ?? null;
        $blood_group = $_POST['blood_group'] ?? '';
        $password = $_POST['password'] ?? '';

        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already registered.';
            } else {
                $pdo->beginTransaction();
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'patient')");
                    $ins->execute([$name, $email, $phone, $hashed]);
                    $userId = (int)$pdo->lastInsertId();

                    $insPat = $pdo->prepare("INSERT INTO patients (user_id, date_of_birth, gender, blood_group) VALUES (?, ?, ?, ?)");
                    $insPat->execute([$userId, $dob, $gender, $blood_group]);

                    $pdo->commit();
                    set_flash('success', 'Registration successful! You can now log in.');
                    redirect('login.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Error saving profile: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration - MediCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 540px;">
    <div class="card border-0 shadow-sm p-4 rounded-4">
        <h4 class="fw-bold mb-3">Create Patient Account</h4>
        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Male</option><option>Female</option><option>Other</option></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Birth Date</label><input type="date" name="date_of_birth" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Blood Group</label><input type="text" name="blood_group" class="form-control" placeholder="e.g. O+"></div>
            </div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Register Account</button>
            <div class="text-center mt-3"><a href="login.php" class="small text-decoration-none">Already registered? Log in</a></div>
        </form>
    </div>
</div>
</body>
</html>