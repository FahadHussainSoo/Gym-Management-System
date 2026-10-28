<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Payments';

$totalPaid    = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE payment_status='Paid'")->fetch()['v'];
$totalPending = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE payment_status='Pending'")->fetch()['v'];
$totalOverdue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE payment_status='Overdue'")->fetch()['v'];
$totalCount   = (int)$pdo->query("SELECT COUNT(*) c FROM payments")->fetch()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card green fade-in-up"><i class="fa-solid fa-sack-dollar"></i><p>Total Collected</p><h3>Rs <?php echo number_format($totalPaid); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card orange fade-in-up"><i class="fa-solid fa-hourglass-half"></i><p>Pending</p><h3>Rs <?php echo number_format($totalPending); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card pink fade-in-up"><i class="fa-solid fa-triangle-exclamation"></i><p>Overdue</p><h3>Rs <?php echo number_format($totalOverdue); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card cyan fade-in-up"><i class="fa-solid fa-receipt"></i><p>Transactions</p><h3><?php echo number_format($totalCount); ?></h3></div>
  </div>
</div>

<!-- ===== Payments Card ===== -->
<div class="card card-premium">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <span><i class="fa-solid fa-money-check-dollar me-2"></i>All Payments</span>
    <button class="btn btn-light btn-sm fw-600" id="btnAddPayment"><i class="fa-solid fa-plus me-1"></i> Add Payment</button>
  </div>
  <div class="card-body">
    <table id="paymentsTable" class="table table-hover align-middle" style="width:100%">
      <thead>
        <tr><th>Date</th><th>Member</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th class="no-print">Actions</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ===== Add / Edit Payment Modal ===== -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px; border:none;">
      <div class="modal-header" style="background:linear-gradient(90deg,#4F46E5,#06B6D4); color:#fff;">
        <h5 class="modal-title" id="paymentModalTitle"><i class="fa-solid fa-plus me-2"></i>Add Payment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="paymentForm">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" id="pay_id">
          <div class="mb-3">
            <label class="form-label">Member *</label>
            <select class="form-select" name="member_id" id="pay_member_id" required></select>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Membership Plan</label>
              <select class="form-select" name="plan_id" id="pay_plan_id">
                <option value="">— None —</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Amount (Rs) *</label>
              <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="pay_amount" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Payment Method</label>
              <select class="form-select" name="payment_method" id="pay_method">
                <option>Cash</option><option>Card</option><option>Bank Transfer</option><option>Online</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="payment_status" id="pay_status">
                <option>Paid</option><option>Pending</option><option>Overdue</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Payment Date *</label>
              <input type="date" class="form-control" name="payment_date" id="pay_date" required>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" id="pay_notes" rows="2"></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient" id="savePaymentBtn"><i class="fa-solid fa-floppy-disk me-1"></i> Save Payment</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
  let paymentsTable, membersCache = [], plansCache = [];

  function loadLookups(callback) {
    $.getJSON(BASE_URL + 'ajax/payments/lookups.php', function (res) {
      membersCache = res.members;
      plansCache = res.plans;
      const $m = $('#pay_member_id').empty();
      membersCache.forEach(m => $m.append(`<option value="${m.id}">${m.full_name} (${m.member_code})</option>`));
      const $p = $('#pay_plan_id');
      $p.find('option:not(:first)').remove();
      plansCache.forEach(p => $p.append(`<option value="${p.id}" data-price="${p.price}">${p.plan_name} — Rs ${parseFloat(p.price).toFixed(2)}</option>`));
      if (callback) callback();
    });
  }

  // Auto-fill amount from selected plan (only when adding, and amount currently empty/0)
  $(document).on('change', '#pay_plan_id', function () {
    const price = $(this).find('option:selected').data('price');
    if (price !== undefined && (!$('#pay_amount').val() || parseFloat($('#pay_amount').val()) === 0)) {
      $('#pay_amount').val(price);
    }
  });

  function loadPayments() {
    $.getJSON(BASE_URL + 'ajax/payments/list.php', function (res) {
      if (!res.success) return;
      if (paymentsTable) { paymentsTable.destroy(); $('#paymentsTable').empty().html('<thead><tr><th>Date</th><th>Member</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th class="no-print">Actions</th></tr></thead>'); }
      paymentsTable = $('#paymentsTable').DataTable({
        data: res.data, responsive: true, pageLength: 10, order: [[0, 'desc']],
        columns: [
          { data: 'payment_date' },
          { data: null, render: d => `${d.full_name} <span class="text-muted small">(${d.member_code})</span>` },
          { data: 'plan_name', render: d => d || '—' },
          { data: 'amount', render: d => 'Rs ' + parseFloat(d).toLocaleString(undefined, {minimumFractionDigits:2}) },
          { data: 'payment_method' },
          { data: 'payment_status', render: d => {
              const cls = d === 'Paid' ? 'badge-active' : (d === 'Pending' ? 'badge-pending' : 'badge-inactive');
              return `<span class="${cls}">${d}</span>`;
            } },
          { data: null, orderable: false, className: 'no-print', render: d => `
              <div class="d-flex gap-1">
                <a href="${BASE_URL}modules/payments/print_receipt.php?id=${d.id}" target="_blank" class="btn btn-sm btn-outline-gradient" title="Print Receipt"><i class="fa-solid fa-print"></i></a>
                <button class="btn btn-sm btn-outline-gradient btn-edit-payment" data-id="${d.id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete-payment" data-id="${d.id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  loadPayments();

  // ===== Add Payment =====
  $('#btnAddPayment').on('click', function () {
    $('#paymentForm')[0].reset();
    $('#pay_id').val('');
    $('#paymentModalTitle').html('<i class="fa-solid fa-plus me-2"></i>Add Payment');
    $('#pay_date').val(new Date().toISOString().slice(0, 10));
    loadLookups();
    paymentModal.show();
  });

  // ===== Edit Payment =====
  $('#paymentsTable').on('click', '.btn-edit-payment', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/payments/get.php', { id }, function (res) {
      if (!res.success) return Swal.fire('Error', res.message, 'error');
      const p = res.payment;
      $('#paymentModalTitle').html('<i class="fa-solid fa-pen me-2"></i>Edit Payment');
      loadLookups(function () {
        $('#pay_id').val(p.id);
        $('#pay_member_id').val(p.member_id);
        $('#pay_plan_id').val(p.plan_id || '');
        $('#pay_amount').val(p.amount);
        $('#pay_method').val(p.payment_method);
        $('#pay_status').val(p.payment_status);
        $('#pay_date').val(p.payment_date);
        $('#pay_notes').val(p.notes);
      });
      paymentModal.show();
    });
  });

  // ===== Save Payment =====
  $('#savePaymentBtn').on('click', function () {
    const form = $('#paymentForm')[0];
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    $.post(BASE_URL + 'ajax/payments/save.php', $(form).serialize(), function (res) {
      if (res.success) {
        paymentModal.hide();
        Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
        loadPayments();
      } else {
        Swal.fire('Error', res.message || 'Something went wrong.', 'error');
      }
    }, 'json').fail(function () {
      Swal.fire('Error', 'Server error while saving payment.', 'error');
    }).always(function () {
      btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Payment');
    });
  });

  // ===== Delete Payment =====
  $('#paymentsTable').on('click', '.btn-delete-payment', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Delete this payment?', text: 'This action cannot be undone.', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/payments/delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.success) { Swal.fire('Deleted!', res.message, 'success'); loadPayments(); }
          else Swal.fire('Error', res.message, 'error');
        }, 'json');
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
