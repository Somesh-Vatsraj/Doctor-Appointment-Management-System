<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

$billId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT b.*, 
           p_u.name AS patient_name, p_u.phone AS patient_phone, p.address,
           d_u.name AS doctor_name, d.specialization,
           a.appointment_date
    FROM bills b
    JOIN patients p ON b.patient_id = p.id
    JOIN users p_u ON p.user_id = p_u.id
    JOIN doctors d ON b.doctor_id = d.id
    JOIN users d_u ON d.user_id = d_u.id
    JOIN appointments a ON b.appointment_id = a.id
    WHERE b.id = ?
");
$stmt->execute([$billId]);
$bill = $stmt->fetch();

if (!$bill) {
    die('Invoice not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= e($bill['bill_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; }
        .invoice-card { max-width: 800px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 8px; border: 1px solid #dee2e6; }
        @media print { .no-print { display: none !important; } .invoice-card { border: none; padding: 0; } }
    </style>
</head>
<body>
<div class="container invoice-card shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="javascript:window.history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-success btn-sm"><i class="bi bi-printer"></i> Print Invoice</button>
    </div>

    <div class="d-flex justify-content-between border-bottom pb-3 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-0"><i class="bi bi-hospital"></i> MediCare Hospital</h3>
            <p class="text-muted small mb-0">Official Consultation & Medical Bill</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">INVOICE</h5>
            <small class="text-muted"># <?= e($bill['bill_number']) ?></small><br>
            <small class="text-muted">Date: <?= e($bill['bill_date']) ?></small>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <h6 class="fw-bold text-muted">Billed To:</h6>
            <div class="fw-bold"><?= e($bill['patient_name']) ?></div>
            <div class="small text-muted"><?= e($bill['patient_phone']) ?></div>
            <div class="small text-muted"><?= e($bill['address'] ?? '') ?></div>
        </div>
        <div class="col-6 text-end">
            <h6 class="fw-bold text-muted">Consulting Doctor:</h6>
            <div class="fw-bold">Dr. <?= e($bill['doctor_name']) ?></div>
            <div class="small text-muted"><?= e($bill['specialization']) ?></div>
        </div>
    </div>

    <table class="table table-bordered mb-4">
        <thead class="table-light">
            <tr>
                <th>Description</th>
                <th class="text-end" style="width: 25%;">Amount ($)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Doctor Consultation Fee</td><td class="text-end"><?= number_format((float)$bill['consultation_fee'], 2) ?></td></tr>
            <tr><td>Medicines & Pharmacy Charges</td><td class="text-end"><?= number_format((float)$bill['medicine_charges'], 2) ?></td></tr>
            <tr><td>Lab / Diagnostic Tests</td><td class="text-end"><?= number_format((float)$bill['test_charges'], 2) ?></td></tr>
            <tr><td>Other Clinical Charges</td><td class="text-end"><?= number_format((float)$bill['other_charges'], 2) ?></td></tr>
            <tr><td class="text-end fw-bold">Discount</td><td class="text-end text-danger">- <?= number_format((float)$bill['discount'], 2) ?></td></tr>
            <tr><td class="text-end fw-bold">Tax</td><td class="text-end">+ <?= number_format((float)$bill['tax'], 2) ?></td></tr>
            <tr class="table-light"><td class="text-end fw-bold fs-5">Grand Total</td><td class="text-end fw-bold fs-5 text-success">$<?= number_format((float)$bill['grand_total'], 2) ?></td></tr>
        </tbody>
    </table>

    <div class="d-flex justify-content-between p-3 bg-light rounded">
        <div><b>Payment Status:</b> <?= status_badge($bill['payment_status']) ?></div>
        <div><b>Payment Method:</b> <span class="fw-bold"><?= e($bill['payment_method']) ?></span></div>
    </div>
</div>
</body>
</html>