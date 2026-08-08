<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['doctor']);
require_once __DIR__ . '/../includes/header.php';

$doctorId = (int)$_SESSION['doctor_id'];

// Status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $apptId = (int)$_POST['appointment_id'];
        $action = $_POST['action'];

        if ($action === 'confirm') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Confirmed' WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$apptId, $doctorId]);
            set_flash('success', 'Appointment confirmed.');
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Rejected' WHERE id = ? AND doctor_id = ?");
            $stmt->execute([$apptId, $doctorId]);
            set_flash('info', 'Appointment rejected.');
        } elseif ($action === 'cancel') {
            $reason = trim($_POST['cancellation_reason'] ?? 'Canceled by doctor');
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Canceled by Doctor', canceled_by = 'Doctor', cancellation_reason = ?, canceled_at = NOW() WHERE id = ? AND doctor_id = ? AND status != 'Completed'");
            $stmt->execute([$reason, $apptId, $doctorId]);
            set_flash('warning', 'Appointment canceled.');
        }
        redirect('appointments.php');
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, pr.id AS prescription_id, b.id AS bill_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN prescriptions pr ON a.id = pr.appointment_id
    LEFT JOIN bills b ON a.id = b.appointment_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$doctorId]);
$appointments = $stmt->fetchAll();
?>

<h3 class="fw-bold mb-4">Manage Patient Appointments</h3>

<div class="card table-card p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Patient</th>
                    <th>Date & Time</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Workflow</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="fw-bold"><?= e($a['patient_name']) ?><br><small class="text-muted"><?= e($a['patient_phone']) ?></small></td>
                    <td><?= e($a['appointment_date']) ?><br><small class="text-muted"><?= date('h:i A', strtotime($a['appointment_time'])) ?></small></td>
                    <td><?= e($a['reason']) ?></td>
                    <td>
                        <?= status_badge($a['status']) ?>
                        <?php if (str_starts_with($a['status'], 'Canceled') || $a['status'] === 'Rejected'): ?>
                            <div class="small text-danger mt-1">Reason: <?= e($a['cancellation_reason'] ?? 'N/A') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['status'] === 'Confirmed'): ?>
                            <a href="prescription-create.php?appointment_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-medical"></i> Prescribe</a>
                        <?php elseif ($a['status'] === 'Completed'): ?>
                            <?php if ($a['prescription_id']): ?>
                                <a href="/print-prescription.php?id=<?= $a['prescription_id'] ?>" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-printer"></i> Rx</a>
                            <?php endif; ?>
                            <?php if (!$a['bill_id']): ?>
                                <a href="bill-create.php?appointment_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-receipt"></i> Generate Bill</a>
                            <?php else: ?>
                                <a href="/print-bill.php?id=<?= $a['bill_id'] ?>" target="_blank" class="btn btn-sm btn-light border text-success"><i class="bi bi-receipt"></i> Invoice</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['status'] === 'Pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                <button type="submit" name="action" value="confirm" class="btn btn-sm btn-success">Accept</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-secondary">Reject</button>
                            </form>
                        <?php elseif ($a['status'] === 'Confirmed'): ?>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $a['id'] ?>">Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Cancellation Modal -->
                <div class="modal fade" id="cancelModal<?= $a['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <div class="modal-header"><h5 class="modal-title">Cancel Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <p>Provide a reason for canceling appointment with <b><?= e($a['patient_name']) ?></b>:</p>
                                <textarea name="cancellation_reason" class="form-control" required rows="3" placeholder="Doctor emergency, reschedule required..."></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>