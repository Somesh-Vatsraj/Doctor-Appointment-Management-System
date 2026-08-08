<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['patient']);
require_once __DIR__ . '/../includes/header.php';

$patientId = (int)$_SESSION['patient_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token submission.';
    } else {
        $doctorId = (int)$_POST['doctor_id'];
        $date = $_POST['appointment_date'];
        $time = $_POST['appointment_time'];
        $reason = trim($_POST['reason']);

        // Check if selected date is in the past
        if ($date < date('Y-m-d')) {
            $error = 'Cannot book an appointment for past dates.';
        } else {
            // Anti-double-booking check for same doctor + date + time
            $chk = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('Canceled by Patient', 'Canceled by Doctor', 'Rejected')");
            $chk->execute([$doctorId, $date, $time]);

            if ($chk->fetch()) {
                $error = 'This time slot is already booked for the selected doctor. Please choose a different time.';
            } else {
                $ins = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                $ins->execute([$patientId, $doctorId, $date, $time, $reason]);
                set_flash('success', 'Appointment requested successfully! Awaiting doctor confirmation.');
                redirect('appointments.php');
            }
        }
    }
}

$doctors = $pdo->query("SELECT d.*, u.name FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.name ASC")->fetchAll();
?>

<div class="card table-card p-4" style="max-width: 680px; margin: 0 auto;">
    <h4 class="fw-bold mb-3">Book Doctor Appointment</h4>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="mb-3">
            <label class="form-label fw-semibold">Select Doctor</label>
            <select name="doctor_id" class="form-select" required>
                <option value="">-- Choose Doctor --</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= $d['id'] ?>">Dr. <?= e($d['name']) ?> (<?= e($d['specialization']) ?>) - $<?= number_format((float)$d['consultation_fee'], 2) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Appointment Date</label>
                <input type="date" min="<?= date('Y-m-d') ?>" name="appointment_date" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Preferred Time</label>
                <input type="time" name="appointment_time" class="form-control" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Reason for Visit</label>
            <textarea name="reason" class="form-control" rows="3" required placeholder="Describe your symptoms or reason for visit..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Confirm Booking</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>