<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['doctor']);
require_once __DIR__ . '/../includes/header.php';

$doctorId = (int)$_SESSION['doctor_id'];
$appointmentId = (int)($_GET['appointment_id'] ?? 0);

// Validate Appointment belongs to Doctor
$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, p.gender, p.blood_group, p.date_of_birth
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.id = ? AND a.doctor_id = ? AND a.status = 'Confirmed'
");
$stmt->execute([$appointmentId, $doctorId]);
$appt = $stmt->fetch();

if (!$appt) {
    set_flash('danger', 'Appointment not found or not in Confirmed state.');
    redirect('appointments.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $diagnosis = trim($_POST['diagnosis']);
        $symptoms = trim($_POST['symptoms']);
        $notes = trim($_POST['doctor_notes'] ?? '');
        $medicines = $_POST['medicines'] ?? [];
        $rxNumber = 'RX-' . strtoupper(uniqid());

        $pdo->beginTransaction();
        try {
            $insRx = $pdo->prepare("
                INSERT INTO prescriptions (prescription_number, appointment_id, patient_id, doctor_id, diagnosis, symptoms, doctor_notes, prescription_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $insRx->execute([$rxNumber, $appointmentId, $appt['patient_id'], $doctorId, $diagnosis, $symptoms, $notes]);
            $prescriptionId = (int)$pdo->lastInsertId();

            $insMed = $pdo->prepare("
                INSERT INTO prescription_medicines (prescription_id, medicine_name, dosage, frequency, duration, instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($medicines as $med) {
                if (!empty($med['name'])) {
                    $insMed->execute([
                        $prescriptionId,
                        trim($med['name']),
                        trim($med['dosage']),
                        trim($med['frequency']),
                        trim($med['duration']),
                        trim($med['instructions'] ?? '')
                    ]);
                }
            }

            // Mark Appointment Completed
            $upd = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
            $upd->execute([$appointmentId]);

            $pdo->commit();
            set_flash('success', 'Prescription created successfully.');
            redirect('bill-create.php?appointment_id=' . $appointmentId);
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error: ' . $e->getMessage());
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Create Medical Prescription</h3>
    <a href="appointments.php" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card table-card p-4">
    <div class="row bg-light p-3 rounded mb-4">
        <div class="col-md-4"><b>Patient:</b> <?= e($appt['patient_name']) ?></div>
        <div class="col-md-4"><b>Gender / Blood:</b> <?= e($appt['gender']) ?> / <?= e($appt['blood_group'] ?? 'N/A') ?></div>
        <div class="col-md-4"><b>Date:</b> <?= e($appt['appointment_date']) ?></div>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Symptoms</label>
                <textarea name="symptoms" class="form-control" required rows="2" placeholder="Fever, cough, chest tightness..."></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Diagnosis</label>
                <textarea name="diagnosis" class="form-control" required rows="2" placeholder="Acute Bronchitis..."></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
            <h5 class="fw-bold m-0">Prescribed Medicines</h5>
            <button type="button" class="btn btn-sm btn-primary" id="addMedicineRow"><i class="bi bi-plus"></i> Add Row</button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Medicine Name</th>
                        <th style="width: 15%;">Dosage</th>
                        <th style="width: 20%;">Frequency</th>
                        <th style="width: 15%;">Duration</th>
                        <th>Instructions</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="medicineRows">
                    <tr>
                        <td><input type="text" name="medicines[0][name]" class="form-control" required placeholder="Paracetamol 500mg"></td>
                        <td><input type="text" name="medicines[0][dosage]" class="form-control" required placeholder="1 Tablet"></td>
                        <td><input type="text" name="medicines[0][frequency]" class="form-control" required placeholder="2 times daily"></td>
                        <td><input type="text" name="medicines[0][duration]" class="form-control" required placeholder="5 days"></td>
                        <td><input type="text" name="medicines[0][instructions]" class="form-control" placeholder="After meals"></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 mt-3">
            <label class="form-label fw-bold">Additional Advice / Notes</label>
            <textarea name="doctor_notes" class="form-control" rows="2" placeholder="Drink plenty of warm fluids, rest for 3 days..."></textarea>
        </div>

        <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">Save Prescription & Proceed to Billing</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>