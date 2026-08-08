<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['patient']);
require_once __DIR__ . '/../includes/header.php';

$patientId = (int)$_SESSION['patient_id'];

// Cancellation by Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $apptId = (int)$_POST['appointment_id'];
        $reason = trim($_POST['cancellation_reason'] ?? 'Canceled by patient');

        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'Canceled by Patient', canceled_by = 'Patient', cancellation_reason = ?, canceled_at = NOW() 
            WHERE id = ? AND patient_id = ? AND status NOT IN ('Completed', 'Canceled by Patient', 'Canceled by Doctor')
        ");
        $stmt->execute([$reason, $apptId, $patientId]);
        set_flash('info', 'Your appointment has been canceled.');
        redirect('appointments.php');
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, d_u.name AS doctor_name, d.specialization, pr.id AS prescription_id, b.id AS bill_id
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users d_u ON d.user_id = d_u.id
    LEFT JOIN prescriptions pr ON a.id = pr.appointment_id
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$patientId]);
$appointments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">My Appointments</h3>
    <a href="book-appointment.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Book New</a>
</div>

<div class="card table-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Doctor</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Records</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="fw-bold">Dr. <?= e($a['doctor_name']) ?><br><small class="text-muted"><?= e($a['specialization']) ?></small></td>
                    <td><?= e($a['appointment_date']) ?><br><small class="text-muted"><?= date('h:i A', strtotime($a['appointment_time'])) ?></small></td>
                    <td><?= e($a['reason']) ?></td>
                    <td>
                        <?= status_badge($a['status']) ?>
                        <?php if ($a['cancellation_reason']): ?>
                            <div class="small text-danger mt-1">Reason: <?= e($a['cancellation_reason']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['prescription_id']): ?>
                            <a href="/print-prescription.php?id=<?= $a['prescription_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-medical"></i> Rx</a>
                        <?php endif; ?>
                        <?php if ($a['bill_id']): ?>
                            <a href="/print-bill.php?id=<?= $a['bill_id'] ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt"></i> Bill</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (in_array($a['status'], ['Pending', 'Confirmed'], true)): ?>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $a['id'] ?>">Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Patient Cancel Modal -->
                <div class="modal fade" id="cancelModal<?= $a['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <div class="modal-header"><h5 class="modal-title">Cancel Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <label class="form-label">Please explain why you need to cancel:</label>
                                <textarea name="cancellation_reason" class="form-control" required rows="3"></textarea>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-danger">Confirm Cancel</button></div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>