<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
check_auth(['doctor', 'admin']);
require_once __DIR__ . '/../includes/header.php';

$appointmentId = (int)($_GET['appointment_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, d.consultation_fee, d_u.name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users d_u ON d.user_id = d_u.id
    WHERE a.id = ?
");
$stmt->execute([$appointmentId]);
$appt = $stmt->fetch();

if (!$appt) {
    set_flash('danger', 'Invalid appointment record.');
    redirect('/index.php');
}

// Server Side Bill Generation & Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $consultFee = (float)$_POST['consultation_fee'];
        $medCharges = (float)($_POST['medicine_charges'] ?? 0);
        $testCharges = (float)($_POST['test_charges'] ?? 0);
        $otherCharges = (float)($_POST['other_charges'] ?? 0);
        $discountPercent = (float)($_POST['discount_percent'] ?? 0);
        $taxPercent = (float)($_POST['tax_percent'] ?? 0);

        // Strict Server-Side Re-calculation
        $subtotal = $consultFee + $medCharges + $testCharges + $otherCharges;
        $discountAmount = ($subtotal * $discountPercent) / 100;
        $taxable = $subtotal - $discountAmount;
        $taxAmount = ($taxable * $taxPercent) / 100;
        $grandTotal = $taxable + $taxAmount;

        $billNumber = 'BILL-' . strtoupper(uniqid());
        $paymentStatus = $_POST['payment_status'] ?? 'Pending';
        $paymentMethod = $_POST['payment_method'] ?? 'Cash';

        $insBill = $pdo->prepare("
            INSERT INTO bills (bill_number, appointment_id, patient_id, doctor_id, consultation_fee, medicine_charges, test_charges, other_charges, discount, tax, grand_total, payment_status, payment_method, bill_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        $insBill->execute([
            $billNumber, $appointmentId, $appt['patient_id'], $appt['doctor_id'],
            $consultFee, $medCharges, $testCharges, $otherCharges, $discountAmount, $taxAmount, $grandTotal,
            $paymentStatus, $paymentMethod
        ]);
        $billId = (int)$pdo->lastInsertId();

        set_flash('success', 'Invoice generated successfully.');
        redirect('/print-bill.php?id=' . $billId);
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold m-0">Generate Medical Invoice</h3>
</div>

<div class="card table-card p-4" style="max-width: 800px; margin: 0 auto;">
    <form method="POST" id="billingForm">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label text-muted">Patient Name</label><input type="text" class="form-control" readonly value="<?= e($appt['patient_name']) ?>"></div>
            <div class="col-md-6"><label class="form-label text-muted">Doctor</label><input type="text" class="form-control" readonly value="Dr. <?= e($appt['doctor_name']) ?>"></div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Charge Breakdown</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Consultation Fee ($)</label>
                <input type="number" step="0.01" id="consultation_fee" name="consultation_fee" class="form-control calc-trigger" required value="<?= $appt['consultation_fee'] ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Pharmacy / Medicine Charges ($)</label>
                <input type="number" step="0.01" id="medicine_charges" name="medicine_charges" class="form-control calc-trigger" value="0.00">
            </div>
            <div class="col-md-6">
                <label class="form-label">Lab / Test Charges ($)</label>
                <input type="number" step="0.01" id="test_charges" name="test_charges" class="form-control calc-trigger" value="0.00">
            </div>
            <div class="col-md-6">
                <label class="form-label">Other Services ($)</label>
                <input type="number" step="0.01" id="other_charges" name="other_charges" class="form-control calc-trigger" value="0.00">
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Discounts & Taxes</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Discount (%)</label>
                <input type="number" step="0.1" id="discount_percent" name="discount_percent" class="form-control calc-trigger" value="0">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tax (%)</label>
                <input type="number" step="0.1" id="tax_percent" name="tax_percent" class="form-control calc-trigger" value="5">
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">Calculated Discount Amount ($)</label>
                <input type="text" id="calculated_discount" class="form-control bg-light" readonly value="0.00">
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted">Calculated Tax Amount ($)</label>
                <input type="text" id="calculated_tax" class="form-control bg-light" readonly value="0.00">
            </div>
        </div>

        <div class="my-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
            <span class="fs-5 fw-bold">Grand Total:</span>
            <div class="input-group" style="max-width: 200px;">
                <span class="input-group-text">$</span>
                <input type="text" id="grand_total" class="form-control fs-5 fw-bold text-success" readonly value="0.00">
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Payment Status</label>
                <select name="payment_status" class="form-select">
                    <option value="Paid">Paid</option>
                    <option value="Pending">Pending</option>
                    <option value="Partially Paid">Partially Paid</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="UPI">UPI</option>
                    <option value="Online">Online</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Confirm and Issue Bill</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>