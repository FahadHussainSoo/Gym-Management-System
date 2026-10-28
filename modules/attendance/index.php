<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Attendance';

$todayCount = (int)$pdo->query("SELECT COUNT(*) c FROM attendance WHERE DATE(check_in)=CURDATE()")->fetch()['c'];
$currentlyIn = (int)$pdo->query("SELECT COUNT(*) c FROM attendance WHERE check_out IS NULL")->fetch()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="stat-card purple"><i class="fa-solid fa-calendar-check"></i><p>Check-ins Today</p><h3><?php echo $todayCount; ?></h3></div></div>
  <div class="col-md-4"><div class="stat-card cyan"><i class="fa-solid fa-person-walking"></i><p>Currently in Gym</p><h3><?php echo $currentlyIn; ?></h3></div></div>
</div>

<div class="card card-premium mb-4">
  <div class="card-header">Mark Attendance</div>
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-8 position-relative">
        <label class="form-label small fw-600">Search Member (name, code, or phone)</label>
        <input type="text" id="memberSearch" class="form-control" placeholder="Start typing…" autocomplete="off">
        <div id="memberSearchResults" class="list-group position-absolute w-100" style="z-index:1000; max-height:260px; overflow-y:auto; display:none;"></div>
      </div>
      <div class="col-md-4">
        <button class="btn btn-gradient w-100" id="checkinBtn" disabled><i class="fa-solid fa-right-to-bracket me-1"></i>Check In Selected</button>
      </div>
    </div>
    <div id="selectedMemberBox" class="mt-3"></div>
  </div>
</div>

<div class="card card-premium mb-4">
  <div class="card-header">Currently in Gym</div>
  <div class="card-body p-0">
    <table id="openTable" class="table mb-0" style="width:100%">
      <thead><tr><th>Member</th><th>Check-in Time</th><th>Action</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="card card-premium">
  <div class="card-header">Attendance History</div>
  <div class="card-body">
    <table id="historyTable" class="table table-hover" style="width:100%">
      <thead><tr><th>Member</th><th>Check-in</th><th>Check-out</th><th>Duration</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let selectedMemberId = null;
  let historyTable;

  $('#memberSearch').on('input', function () {
    const q = $(this).val().trim();
    selectedMemberId = null;
    $('#checkinBtn').prop('disabled', true);
    if (q.length < 2) { $('#memberSearchResults').hide(); return; }
    $.getJSON(BASE_URL + 'ajax/attendance/search_members.php', { q }, function (res) {
      if (!res.success || !res.data.length) { $('#memberSearchResults').hide(); return; }
      let html = '';
      res.data.forEach(m => {
        html += `<button type="button" class="list-group-item list-group-item-action member-option" data-id="${m.id}" data-name="${$('<div>').text(m.full_name).html()}">
          <strong>${$('<div>').text(m.full_name).html()}</strong> <span class="text-muted small">${m.member_code}</span>
        </button>`;
      });
      $('#memberSearchResults').html(html).show();
    });
  });

  $(document).on('click', '.member-option', function () {
    selectedMemberId = $(this).data('id');
    $('#memberSearch').val($(this).data('name'));
    $('#memberSearchResults').hide();
    $('#checkinBtn').prop('disabled', false);
  });

  $('#checkinBtn').on('click', function () {
    if (!selectedMemberId) return;
    $.post(BASE_URL + 'ajax/attendance/checkin.php', { member_id: selectedMemberId, csrf_token: window.CSRF_TOKEN }, function (res) {
      if (res.success) {
        Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        $('#memberSearch').val(''); selectedMemberId = null; $('#checkinBtn').prop('disabled', true);
        loadOpen(); loadHistory();
      } else {
        Swal.fire('Notice', res.message, 'warning');
      }
    }, 'json');
  });

  function loadOpen() {
    $.getJSON(BASE_URL + 'ajax/attendance/list.php', { open: 1 }, function (res) {
      const $tbody = $('#openTable tbody').empty();
      if (!res.data.length) { $tbody.append('<tr><td colspan="3" class="text-muted p-3">No one currently checked in.</td></tr>'); return; }
      res.data.forEach(a => {
        $tbody.append(`<tr>
          <td>${$('<div>').text(a.full_name).html()} <span class="text-muted small">(${a.member_code})</span></td>
          <td>${a.check_in}</td>
          <td><button class="btn btn-sm btn-outline-gradient btn-checkout" data-id="${a.id}">Check Out</button></td>
        </tr>`);
      });
    });
  }

  function loadHistory() {
    $.getJSON(BASE_URL + 'ajax/attendance/list.php', function (res) {
      if (historyTable) { historyTable.destroy(); $('#historyTable').empty().html('<thead><tr><th>Member</th><th>Check-in</th><th>Check-out</th><th>Duration</th></tr></thead>'); }
      historyTable = $('#historyTable').DataTable({
        data: res.data,
        responsive: true,
        order: [[1, 'desc']],
        columns: [
          { data: null, render: d => `${$('<div>').text(d.full_name).html()} <span class="text-muted small">(${d.member_code})</span>` },
          { data: 'check_in' },
          { data: 'check_out', render: d => d || '<span class="text-muted">Still in gym</span>' },
          { data: null, render: d => d.check_out ? Math.round((new Date(d.check_out) - new Date(d.check_in)) / 60000) + ' min' : '—' }
        ]
      });
    });
  }

  $('#openTable').on('click', '.btn-checkout', function () {
    const id = $(this).data('id');
    $.post(BASE_URL + 'ajax/attendance/checkout.php', { id, csrf_token: window.CSRF_TOKEN }, function (res) {
      if (res.success) { Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }); loadOpen(); loadHistory(); }
      else Swal.fire('Error', res.message, 'error');
    }, 'json');
  });

  loadOpen();
  loadHistory();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
