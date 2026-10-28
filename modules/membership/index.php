<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Membership';

$expiringSoon = (int)$pdo->query("SELECT COUNT(*) c FROM members WHERE status='Active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch()['c'];
$expired = (int)$pdo->query("SELECT COUNT(*) c FROM members WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()")->fetch()['c'];
$activeCount = (int)$pdo->query("SELECT COUNT(*) c FROM members WHERE status='Active'")->fetch()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card purple"><i class="fa-solid fa-id-badge"></i><p>Active Memberships</p><h3><?php echo $activeCount; ?></h3></div></div>
  <div class="col-md-4"><div class="stat-card orange"><i class="fa-solid fa-hourglass-half"></i><p>Expiring in 7 Days</p><h3><?php echo $expiringSoon; ?></h3></div></div>
  <div class="col-md-4"><div class="stat-card pink"><i class="fa-solid fa-triangle-exclamation"></i><p>Expired</p><h3><?php echo $expired; ?></h3></div></div>
</div>

<div class="card card-premium">
  <div class="card-header">All Memberships</div>
  <div class="card-body">
    <table id="membershipTable" class="table table-hover" style="width:100%">
      <thead><tr><th>Member</th><th>Plan</th><th>Join Date</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- RENEW / CHANGE PLAN MODAL -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="planModalTitle">Renew Membership</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="planForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="member_id" id="pm_member_id">
        <input type="hidden" name="mode" id="pm_mode" value="renew">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-600">Plan</label>
            <select name="plan_id" id="pm_plan_id" class="form-select" required></select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-600">Start Date</label>
            <input type="date" name="start_date" id="pm_start_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-600">Amount Paid</label>
            <input type="number" step="0.01" name="amount_paid" id="pm_amount" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-600">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option>Cash</option><option>Card</option><option>Bank Transfer</option><option>Online</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-gradient" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gradient">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let table;
  const planModal = new bootstrap.Modal(document.getElementById('planModal'));

  function statusBadge(m) {
    if (m.status !== 'Active') return '<span class="badge-inactive">Inactive</span>';
    if (!m.expiry_date) return '<span class="badge-active">Active</span>';
    const days = Math.ceil((new Date(m.expiry_date) - new Date()) / 86400000);
    if (days < 0) return '<span class="pay-pill overdue">Expired</span>';
    if (days <= 7) return `<span class="pay-pill pending">Expires in ${days}d</span>`;
    return '<span class="badge-active">Active</span>';
  }

  function loadPlans(selectedId) {
    $.getJSON(BASE_URL + 'ajax/membership/plans.php', function (res) {
      const $sel = $('#pm_plan_id').empty();
      res.data.forEach(p => $sel.append(`<option value="${p.id}" data-price="${p.price}">${p.plan_name} — Rs ${Number(p.price).toLocaleString()}</option>`));
      if (selectedId) $sel.val(selectedId);
      $sel.trigger('change');
    });
  }
  $('#pm_plan_id').on('change', function () {
    $('#pm_amount').val($(this).find(':selected').data('price') || '');
  });

  function loadTable() {
    $.getJSON(BASE_URL + 'ajax/membership/list.php', function (res) {
      if (table) { table.destroy(); $('#membershipTable').empty().html('<thead><tr><th>Member</th><th>Plan</th><th>Join Date</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>'); }
      table = $('#membershipTable').DataTable({
        data: res.data,
        responsive: true,
        columns: [
          { data: null, render: d => `<div><div class="fw-600">${$('<div>').text(d.full_name).html()}</div><div class="small text-muted">${d.member_code}</div></div>` },
          { data: 'plan_name', render: d => d || '<span class="text-muted">No Plan</span>' },
          { data: 'join_date' },
          { data: 'expiry_date', render: d => d || '—' },
          { data: null, render: d => statusBadge(d) },
          { data: null, orderable: false, render: d => `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-gradient btn-renew" data-id="${d.id}" data-plan="${d.plan_id || ''}">Renew</button>
                <button class="btn btn-sm btn-outline-gradient btn-change" data-id="${d.id}" data-plan="${d.plan_id || ''}">Change Plan</button>
                ${d.status === 'Active'
                  ? `<button class="btn btn-sm btn-outline-danger btn-freeze" data-id="${d.id}">Freeze</button>`
                  : `<button class="btn btn-sm btn-outline-gradient btn-unfreeze" data-id="${d.id}">Unfreeze</button>`}
              </div>` }
        ]
      });
    });
  }
  loadTable();

  $('#membershipTable').on('click', '.btn-renew, .btn-change', function () {
    const isRenew = $(this).hasClass('btn-renew');
    $('#pm_mode').val(isRenew ? 'renew' : 'change');
    $('#planModalTitle').text(isRenew ? 'Renew Membership' : 'Change Plan');
    $('#pm_member_id').val($(this).data('id'));
    $('#pm_start_date').val(new Date().toISOString().slice(0, 10));
    loadPlans($(this).data('plan'));
    planModal.show();
  });

  $('#planForm').on('submit', function (e) {
    e.preventDefault();
    $.post(BASE_URL + 'ajax/membership/action.php', $(this).serialize(), function (res) {
      if (res.success) {
        planModal.hide();
        Swal.fire({ icon: 'success', title: res.message, confirmButtonColor: '#4F46E5', timer: 1800, showConfirmButton: false });
        loadTable();
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#4F46E5' });
      }
    }, 'json');
  });

  $('#membershipTable').on('click', '.btn-freeze, .btn-unfreeze', function () {
    const id = $(this).data('id');
    const action = $(this).hasClass('btn-freeze') ? 'freeze' : 'unfreeze';
    Swal.fire({
      title: action === 'freeze' ? 'Freeze this membership?' : 'Reactivate this membership?',
      icon: 'warning', showCancelButton: true, confirmButtonColor: '#4F46E5'
    }).then(r => {
      if (!r.isConfirmed) return;
      $.post(BASE_URL + 'ajax/membership/action.php', { mode: action, member_id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
        if (res.success) { Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }); loadTable(); }
        else Swal.fire('Error', res.message, 'error');
      }, 'json');
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
