<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['doctor']);
require_once __DIR__ . '/../includes/header.php';

$doctorId = (int)$_SESSION['doctor_id'];
$today = date('Y-m-d');

// Doctor Stats
$todayCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
$todayCount->execute([$doctorId, $today]);
$todayAppts = $todayCount->fetchColumn();

$upcomingCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date > ? AND status = 'Confirmed'");
$upcomingCount->execute([$doctorId, $today]);
$upcomingAppts = $upcomingCount->fetchColumn();

$completedCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Completed'");
$completedCount->execute([$doctorId]);
$completedAppts = $completedCount->fetchColumn();

// Fetch Today's List
$todayList = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, p.gender, p.blood_group
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");
$todayList->execute([$doctorId, $today]);
$appointments = $todayList->fetchAll();
?>

<h3 class="fw-bold mb-4">Doctor Dashboard</h3>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Today's Appointments</div>
            <div class="fs-3 fw-bold text-primary mt-1"><?= $todayAppts ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Upcoming Confirmed</div>
            <div class="fs-3 fw-bold text-info mt-1"><?= $upcomingAppts ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Total Completed</div>
            <div class="fs-3 fw-bold text-success mt-1"><?= $completedAppts ?></div>
        </div>
    </div>
</div>

<div class="card table-card p-4">
    <h5 class="fw-bold mb-3">Today's Schedule (<?= date('M d, Y') ?>)</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="5" class="text-center py-3 text-muted">No appointments scheduled for today.</td></tr>
                <?php else: foreach ($appointments as $a): ?>
                    <tr>
                        <td class="fw-bold text-primary"><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                        <td><?= e($a['patient_name']) ?> <span class="badge bg-light text-secondary border ms-1"><?= e($a['gender']) ?> | <?= e($a['blood_group'] ?? 'N/A') ?></span></td>
                        <td><?= e($a['patient_phone']) ?></td>
                        <td><?= status_badge($a['status']) ?></td>
                        <td>
                            <?php if ($a['status'] === 'Confirmed'): ?>
                                <a href="prescription-create.php?appointment_id=<?= $a['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-medical me-1"></i> Prescribe</a>
                            <?php endif; ?>
                            <a href="appointments.php" class="btn btn-sm btn-outline-secondary">Manage</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>