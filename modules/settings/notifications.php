<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Notifications';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card card-premium">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span><i class="fa-solid fa-bell me-2"></i>All Notifications</span>
    <button class="btn btn-light btn-sm fw-600" id="btnMarkAllRead"><i class="fa-solid fa-check-double me-1"></i> Mark All Read</button>
  </div>
  <div class="card-body">
    <table id="allNotifTable" class="table table-hover align-middle" style="width:100%">
      <thead><tr><th>Status</th><th>Title</th><th>Message</th><th>When</th><th class="no-print">Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let table;
  const iconFor = { info: 'fa-circle-info', success: 'fa-check', warning: 'fa-triangle-exclamation', danger: 'fa-circle-exclamation' };
  const colorFor = { info: '#06B6D4', success: '#10B981', warning: '#F59E0B', danger: '#EF4444' };

  function load() {
    $.getJSON(BASE_URL + 'ajax/settings/notifications_list_all.php', function (res) {
      if (!res.success) return;
      if (table) { table.destroy(); $('#allNotifTable').empty().html('<thead><tr><th>Status</th><th>Title</th><th>Message</th><th>When</th><th class="no-print">Actions</th></tr></thead>'); }
      table = $('#allNotifTable').DataTable({
        data: res.data, responsive: true, pageLength: 15, order: [[3, 'desc']],
        columns: [
          { data: 'is_read', render: (d, t, row) => Number(d)
              ? '<span class="text-muted small">Read</span>'
              : `<span class="d-inline-flex align-items-center gap-1" style="color:${colorFor[row.type] || '#6b7280'}"><i class="fa-solid ${iconFor[row.type] || 'fa-bell'}"></i> New</span>` },
          { data: 'title' },
          { data: 'message', render: d => d || '—' },
          { data: 'created_at' },
          { data: null, orderable: false, className: 'no-print', render: d => `
              <div class="d-flex gap-1">
                ${Number(d.is_read) ? '' : `<button class="btn btn-sm btn-outline-gradient btn-mark-read" data-id="${d.id}" title="Mark read"><i class="fa-solid fa-check"></i></button>`}
                <button class="btn btn-sm btn-outline-danger btn-delete-notif" data-id="${d.id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  load();

  $('#allNotifTable').on('click', '.btn-mark-read', function () {
    $.post(BASE_URL + 'ajax/settings/notifications_mark_one_read.php', { id: $(this).data('id'), csrf_token: window.CSRF_TOKEN }, function () { load(); }, 'json');
  });

  $('#allNotifTable').on('click', '.btn-delete-notif', function () {
    const id = $(this).data('id');
    Swal.fire({ title: 'Delete this notification?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete' })
      .then(result => {
        if (result.isConfirmed) {
          $.post(BASE_URL + 'ajax/settings/notifications_delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function () { load(); }, 'json');
        }
      });
  });

  $('#btnMarkAllRead').on('click', function () {
    $.post(BASE_URL + 'ajax/settings/notifications_mark_read.php', { csrf_token: window.CSRF_TOKEN }, function () { load(); }, 'json');
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
