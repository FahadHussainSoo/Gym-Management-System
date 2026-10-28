<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Class Schedule';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card card-premium">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Weekly Class Schedule</span>
    <button class="btn btn-light btn-sm" id="btnAddClass"><i class="fa-solid fa-plus me-1"></i>New Class</button>
  </div>
  <div class="card-body">
    <table id="scheduleTable" class="table table-hover" style="width:100%">
      <thead><tr><th>Day</th><th>Time</th><th>Class</th><th>Trainer</th><th>Room</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="classModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="classModalTitle">New Class</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="classForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" id="c_id">
        <div class="modal-body">
          <div class="mb-3"><label class="form-label small fw-600">Class Name</label><input type="text" name="class_name" id="c_name" class="form-control" required></div>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-600">Day</label>
              <select name="day_of_week" id="c_day" class="form-select">
                <option>Mon</option><option>Tue</option><option>Wed</option><option>Thu</option><option>Fri</option><option>Sat</option><option>Sun</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small fw-600">Trainer</label><select name="trainer_id" id="c_trainer" class="form-select"><option value="">— None —</option></select></div>
            <div class="col-md-6"><label class="form-label small fw-600">Start Time</label><input type="time" name="start_time" id="c_start" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-600">End Time</label><input type="time" name="end_time" id="c_end" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label small fw-600">Room</label><input type="text" name="room" id="c_room" class="form-control"></div>
            <div class="col-md-6"><label class="form-label small fw-600">Capacity</label><input type="number" name="capacity" id="c_capacity" class="form-control" value="20"></div>
            <div class="col-md-6"><label class="form-label small fw-600">Status</label><select name="status" id="c_status" class="form-select"><option>Active</option><option>Cancelled</option></select></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-gradient" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gradient">Save Class</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let table;
  const classModal = new bootstrap.Modal(document.getElementById('classModal'));
  const DAY_ORDER = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

  function loadTrainers(selected) {
    $.getJSON(BASE_URL + 'ajax/workout/lookups.php', function (res) {
      const $t = $('#c_trainer');
      $t.find('option:not(:first)').remove();
      res.trainers.forEach(t => $t.append(`<option value="${t.id}">${t.full_name}</option>`));
      if (selected) $t.val(selected);
    });
  }

  function resetForm() {
    $('#classForm')[0].reset();
    $('#c_id').val('');
    $('#classModalTitle').text('New Class');
    loadTrainers();
  }
  $('#btnAddClass').on('click', function () { resetForm(); classModal.show(); });

  function loadTable() {
    $.getJSON(BASE_URL + 'ajax/schedule/list.php', function (res) {
      const sorted = res.data.sort((a, b) => DAY_ORDER.indexOf(a.day_of_week) - DAY_ORDER.indexOf(b.day_of_week) || a.start_time.localeCompare(b.start_time));
      if (table) { table.destroy(); $('#scheduleTable').empty().html('<thead><tr><th>Day</th><th>Time</th><th>Class</th><th>Trainer</th><th>Room</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>'); }
      table = $('#scheduleTable').DataTable({
        data: sorted, responsive: true, paging: false, searching: true, info: false, order: [],
        columns: [
          { data: 'day_of_week' },
          { data: null, render: d => `${d.start_time.slice(0,5)} – ${d.end_time.slice(0,5)}` },
          { data: 'class_name' },
          { data: 'trainer_name', render: d => d || '—' },
          { data: 'room', render: d => d || '—' },
          { data: 'capacity' },
          { data: 'status', render: d => `<span class="badge ${d === 'Active' ? 'badge-active' : 'badge-inactive'}">${d}</span>` },
          { data: null, orderable: false, render: d => `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-gradient btn-edit" data-id="${d.id}"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${d.id}"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  loadTable();

  $('#scheduleTable').on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/schedule/get.php', { id }, function (res) {
      if (!res.success) return Swal.fire('Error', res.message, 'error');
      const c = res.data;
      $('#classModalTitle').text('Edit Class');
      $('#c_id').val(c.id);
      $('#c_name').val(c.class_name);
      $('#c_day').val(c.day_of_week);
      $('#c_start').val(c.start_time.slice(0,5));
      $('#c_end').val(c.end_time.slice(0,5));
      $('#c_room').val(c.room);
      $('#c_capacity').val(c.capacity);
      $('#c_status').val(c.status);
      loadTrainers(c.trainer_id);
      classModal.show();
    });
  });

  $('#scheduleTable').on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Swal.fire({ title: 'Delete this class?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#EF4444' }).then(r => {
      if (!r.isConfirmed) return;
      $.post(BASE_URL + 'ajax/schedule/delete.php', { id, csrf_token: window.CSRF_TOKEN }, function (res) {
        if (res.success) { Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false }); loadTable(); }
        else Swal.fire('Error', res.message, 'error');
      }, 'json');
    });
  });

  $('#classForm').on('submit', function (e) {
    e.preventDefault();
    $.post(BASE_URL + 'ajax/schedule/save.php', $(this).serialize(), function (res) {
      if (res.success) {
        classModal.hide();
        Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
        loadTable();
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
