<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['patient']);
require_once __DIR__ . '/../includes/header.php';

$patientId = (int)$_SESSION['patient_id'];

$upcoming = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('Pending', 'Confirmed')");
$upcoming->execute([$patientId]);
$upcomingCount = $upcoming->fetchColumn();

$completed = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'Completed'");
$completed->execute([$patientId]);
$completedCount = $completed->fetchColumn();

$rxStmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
$rxStmt->execute([$patientId]);
$rxCount = $rxStmt->fetchColumn();

$pendingBills = $pdo->prepare("SELECT COUNT(*) FROM bills WHERE patient_id = ? AND payment_status != 'Paid'");
$pendingBills->execute([$patientId]);
$pendingBillsCount = $pendingBills->fetchColumn();

// Upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, d_u.name AS doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users d_u ON d.user_id = d_u.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC LIMIT 5
");
$stmt->execute([$patientId]);
$list = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Patient Dashboard</h3>
    <a href="book-appointment.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Book Appointment</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Upcoming Visits</div>
            <div class="fs-3 fw-bold text-primary mt-1"><?= $upcomingCount ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Completed Visits</div>
            <div class="fs-3 fw-bold text-success mt-1"><?= $completedCount ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Prescriptions</div>
            <div class="fs-3 fw-bold text-info mt-1"><?= $rxCount ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Pending Invoices</div>
            <div class="fs-3 fw-bold text-danger mt-1"><?= $pendingBillsCount ?></div>
        </div>
    </div>
</div>

<div class="card table-card p-4">
    <h5 class="fw-bold mb-3">Recent Appointment Activity</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Doctor</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                <tr>
                    <td>Dr. <?= e($row['doctor_name']) ?> <span class="text-muted small">(<?= e($row['specialization']) ?>)</span></td>
                    <td><?= e($row['appointment_date']) ?> at <?= date('h:i A', strtotime($row['appointment_time'])) ?></td>
                    <td><?= e($row['reason']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>