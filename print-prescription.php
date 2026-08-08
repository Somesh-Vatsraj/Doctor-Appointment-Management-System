<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$rxId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT pr.*, 
           p_u.name AS patient_name, p_u.phone AS patient_phone, p.gender, p.blood_group, p.date_of_birth,
           d_u.name AS doctor_name, d.specialization, d.qualification,
           a.appointment_date
    FROM prescriptions pr
    JOIN patients p ON pr.patient_id = p.id
    JOIN users p_u ON p.user_id = p_u.id
    JOIN doctors d ON pr.doctor_id = d.id
    JOIN users d_u ON d.user_id = d_u.id
    JOIN appointments a ON pr.appointment_id = a.id
    WHERE pr.id = ?
");
$stmt->execute([$rxId]);
$rx = $stmt->fetch();

if (!$rx) {
    die('Prescription not found.');
}

$meds = $pdo->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
$meds->execute([$rxId]);
$medicines = $meds->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription - <?= e($rx['prescription_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .prescription-container { max-width: 800px; margin: 30px auto; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 40px; }
        .rx-logo { font-size: 2.5rem; font-weight: bold; color: #0d6efd; }
        @media print { .no-print { display: none !important; } .prescription-container { border: none; padding: 0; } }
    </style>
</head>
<body>
<div class="container prescription-container shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print Prescription</button>
    </div>

    <!-- Doctor Letterhead -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="text-primary fw-bold mb-0">Dr. <?= e($rx['doctor_name']) ?></h3>
            <p class="text-muted mb-0"><?= e($rx['qualification']) ?> - <?= e($rx['specialization']) ?></p>
            <small class="text-muted">MediCare General Hospital & Research Center</small>
        </div>
        <div class="col-4 text-end">
            <div class="rx-logo">℞</div>
            <small class="text-muted">Rx #: <b><?= e($rx['prescription_number']) ?></b></small><br>
            <small class="text-muted">Date: <?= e($rx['prescription_date']) ?></small>
        </div>
    </div>

    <!-- Patient Header -->
    <div class="row bg-light p-3 rounded mb-4">
        <div class="col-6"><b>Patient Name:</b> <?= e($rx['patient_name']) ?></div>
        <div class="col-3"><b>Gender:</b> <?= e($rx['gender']) ?></div>
        <div class="col-3"><b>Blood Group:</b> <?= e($rx['blood_group'] ?? 'N/A') ?></div>
    </div>

    <!-- Clinical Findings -->
    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-secondary">Reported Symptoms:</h6>
            <p><?= nl2br(e($rx['symptoms'])) ?></p>
        </div>
        <div class="col-6">
            <h6 class="fw-bold text-secondary">Clinical Diagnosis:</h6>
            <p class="fw-semibold text-primary"><?= nl2br(e($rx['diagnosis'])) ?></p>
        </div>
    </div>

    <!-- Medicines -->
    <h5 class="fw-bold border-bottom pb-2 mb-3">Prescribed Medications</h5>
    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Instructions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($medicines as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td class="fw-bold"><?= e($m['medicine_name']) ?></td>
                <td><?= e($m['dosage']) ?></td>
                <td><?= e($m['frequency']) ?></td>
                <td><?= e($m['duration']) ?></td>
                <td><?= e($m['instructions']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($rx['doctor_notes'])): ?>
    <div class="mb-4">
        <h6 class="fw-bold text-secondary">Doctor's Advice / Notes:</h6>
        <div class="p-3 bg-light rounded"><?= nl2br(e($rx['doctor_notes'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Signature -->
    <div class="row mt-5 pt-4">
        <div class="col-6"></div>
        <div class="col-6 text-end">
            <div style="border-top: 1px solid #333; display: inline-block; width: 200px; padding-top: 5px;">
                <b>Dr. <?= e($rx['doctor_name']) ?></b><br>
                <small class="text-muted">Authorized Signature</small>
            </div>
        </div>
    </div>
</div>
</body>
</html>