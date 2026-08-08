<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['admin']);
require_once __DIR__ . '/../includes/header.php';

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $spec = trim($_POST['specialization']);
        $qual = trim($_POST['qualification']);
        $fee = (float)$_POST['consultation_fee'];
        $exp = (int)$_POST['experience'];
        $password = password_hash('doctor123', PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $u = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'doctor')");
            $u->execute([$name, $email, $phone, $password]);
            $uid = (int)$pdo->lastInsertId();

            $d = $pdo->prepare("INSERT INTO doctors (user_id, specialization, qualification, consultation_fee, experience) VALUES (?, ?, ?, ?, ?)");
            $d->execute([$uid, $spec, $qual, $fee, $exp]);

            $pdo->commit();
            set_flash('success', 'Doctor created successfully! Default password is doctor123');
            redirect('doctors.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle Delete Doctor
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->execute([$userId]);
    set_flash('success', 'Doctor record removed.');
    redirect('doctors.php');
}

$doctors = $pdo->query("
    SELECT d.*, u.name, u.email, u.phone, u.id as user_id 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    ORDER BY d.id DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Doctors Directory</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal"><i class="bi bi-plus-lg me-1"></i> Add Doctor</button>
</div>

<div class="card table-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Doctor Name</th>
                    <th>Specialization</th>
                    <th>Fee</th>
                    <th>Phone</th>
                    <th>Experience</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctors as $doc): ?>
                <tr>
                    <td class="fw-bold">Dr. <?= e($doc['name']) ?><br><span class="text-muted small"><?= e($doc['email']) ?></span></td>
                    <td><span class="badge bg-light text-primary border"><?= e($doc['specialization']) ?></span><br><small><?= e($doc['qualification']) ?></small></td>
                    <td class="fw-semibold text-success">$<?= number_format((float)$doc['consultation_fee'], 2) ?></td>
                    <td><?= e($doc['phone']) ?></td>
                    <td><?= e((string)$doc['experience']) ?> Yrs</td>
                    <td>
                        <a href="?delete=<?= $doc['user_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove this doctor?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Doctor -->
<div class="modal fade" id="addDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add">
            <div class="modal-header"><h5 class="modal-title">Add New Doctor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" required></div>
                <div class="row">
                    <div class="col-6 mb-2"><label class="form-label">Fee ($)</label><input type="number" step="0.01" name="consultation_fee" class="form-control" required></div>
                    <div class="col-6 mb-2"><label class="form-label">Experience (Yrs)</label><input type="number" name="experience" class="form-control" required></div>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Doctor</button></div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>