document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Mobile Toggle
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('d-none');
        });
    }

    // Dynamic Prescription Medicines Form Rows
    const addMedicineBtn = document.getElementById('addMedicineRow');
    const medicineTable = document.getElementById('medicineRows');
    if (addMedicineBtn && medicineTable) {
        addMedicineBtn.addEventListener('click', () => {
            const rowCount = medicineTable.querySelectorAll('tr').length;
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="text" name="medicines[${rowCount}][name]" class="form-control" required placeholder="e.g. Amoxicillin 500mg"></td>
                <td><input type="text" name="medicines[${rowCount}][dosage]" class="form-control" required placeholder="e.g. 1 Tablet"></td>
                <td><input type="text" name="medicines[${rowCount}][frequency]" class="form-control" required placeholder="e.g. 2 times daily"></td>
                <td><input type="text" name="medicines[${rowCount}][duration]" class="form-control" required placeholder="e.g. 5 days"></td>
                <td><input type="text" name="medicines[${rowCount}][instructions]" class="form-control" placeholder="e.g. After meals"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-row"><i class="bi bi-trash"></i></button></td>
            `;
            medicineTable.appendChild(newRow);
        });

        medicineTable.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row')) {
                const row = e.target.closest('tr');
                if (medicineTable.querySelectorAll('tr').length > 1) {
                    row.remove();
                } else {
                    alert('Prescription must have at least one medicine.');
                }
            }
        });
    }

    // Dynamic Billing Auto Calculation
    const billForm = document.getElementById('billingForm');
    if (billForm) {
        const calculateTotal = () => {
            const fee = parseFloat(document.getElementById('consultation_fee').value) || 0;
            const meds = parseFloat(document.getElementById('medicine_charges').value) || 0;
            const tests = parseFloat(document.getElementById('test_charges').value) || 0;
            const other = parseFloat(document.getElementById('other_charges').value) || 0;
            const discPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
            const taxPercent = parseFloat(document.getElementById('tax_percent').value) || 0;

            const subtotal = fee + meds + tests + other;
            const discount = (subtotal * discPercent) / 100;
            const taxable = subtotal - discount;
            const tax = (taxable * taxPercent) / 100;
            const grandTotal = taxable + tax;

            document.getElementById('calculated_discount').value = discount.toFixed(2);
            document.getElementById('calculated_tax').value = tax.toFixed(2);
            document.getElementById('grand_total').value = grandTotal.toFixed(2);
        };

        billForm.querySelectorAll('.calc-trigger').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        calculateTotal();
    }
});
