<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['admin']);
require_once __DIR__ . '/../includes/header.php';

// Fetch Aggregate Statistics
$totalDocs = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalAppts = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$completedAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn();
$canceledAppts = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status LIKE 'Canceled%' OR status = 'Rejected'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(grand_total), 0) FROM bills WHERE payment_status = 'Paid'")->fetchColumn();

// Recent Appointments
$recent = $pdo->query("
    SELECT a.*, p_u.name AS patient_name, d_u.name AS doctor_name, d.specialization 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id JOIN users p_u ON p.user_id = p_u.id
    JOIN doctors d ON a.doctor_id = d.id JOIN users d_u ON d.user_id = d_u.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 5
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Admin Dashboard</h3>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Doctors</div>
            <div class="fs-4 fw-bold text-primary mt-1"><?= $totalDocs ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Patients</div>
            <div class="fs-4 fw-bold text-success mt-1"><?= $totalPatients ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Appointments</div>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $totalAppts ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Completed</div>
            <div class="fs-4 fw-bold text-info mt-1"><?= $completedAppts ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Canceled</div>
            <div class="fs-4 fw-bold text-danger mt-1"><?= $canceledAppts ?></div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card dashboard-card p-3 bg-white">
            <div class="text-muted small fw-semibold">Total Revenue</div>
            <div class="fs-4 fw-bold text-success mt-1">$<?= number_format((float)$totalRevenue, 2) ?></div>
        </div>
    </div>
</div>

<div class="card table-card p-4">
    <h5 class="fw-bold mb-3">Recent Appointments</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td class="fw-semibold"><?= e($r['patient_name']) ?></td>
                    <td>Dr. <?= e($r['doctor_name']) ?> <span class="text-muted small">(<?= e($r['specialization']) ?>)</span></td>
                    <td><?= e($r['appointment_date']) ?> at <?= date('h:i A', strtotime($r['appointment_time'])) ?></td>
                    <td><?= e($r['reason']) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>