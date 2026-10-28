<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Workout Plans';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card card-premium">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Workout Plans</span>
    <button class="btn btn-light btn-sm" id="btnAddPlan"><i class="fa-solid fa-plus me-1"></i>New Plan</button>
  </div>
  <div class="card-body">
    <table id="planTable" class="table table-hover" style="width:100%">
      <thead><tr><th>Member</th><th>Title</th><th>Trainer</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="planModalTitle">New Workout Plan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="planForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" id="w_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-600">Member</label><select name="member_id" id="w_member_id" class="form-select" required></select></div>
            <div class="col-md-6"><label class="form-label small fw-600">Trainer</label><select name="trainer_id" id="w_trainer_id" class="form-select"><option value="">— None —</option></select></div>
            <div class="col-md-6"><label class="form-label small fw-600">Title</label><input type="text" name="title" id="w_title" class="form-control" required placeholder="e.g. Strength Foundations — Month 1"></div>
            <div class="col-md-6"><label class="form-label small fw-600">Goal</label><input type="text" name="goal" id="w_goal" class="form-control" placeholder="e.g. Build baseline strength"></div>
            <div class="col-md-4"><label class="form-label small fw-600">Start Date</label><input type="date" name="start_date" id="w_start" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label small fw-600">End Date</label><input type="date" name="end_date" id="w_end" class="form-control"></div>
            <div class="col-md-4"><label class="form-label small fw-600">Status</label>
              <select name="status" id="w_status" class="form-select"><option>Active</option><option>Completed</option><option>Cancelled</option></select>
            </div>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-700 mb-0">Exercises</h6>
            <button type="button" class="btn btn-sm btn-outline-gradient" id="addExerciseRow"><i class="fa-solid fa-plus"></i> Add Exercise</button>
          </div>
          <div id="exerciseRows"></div>
          <input type="hidden" name="exercises_json" id="exercisesJson">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-gradient" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-gradient">Save Plan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Workout Plan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body" id="viewBody"></div>
      <div class="modal-footer no-print">
        <button class="btn btn-outline-gradient" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Print</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let table;
  const planModal = new bootstrap.Modal(document.getElementById('planModal'));
  const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
  const DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

  function exerciseRowHtml(ex) {
    ex = ex || {};
    let dayOpts = DAYS.map(d => `<option ${ex.day_of_week === d ? 'selected' : ''}>${d}</option>`).join('');
    return `<div class="row g-2 align-items-center mb-2 exercise-row">
      <div class="col-md-2"><select class="form-select form-select-sm ex-day">${dayOpts}</select></div>
      <div class="col-md-3"><input class="form-control form-control-sm ex-name" placeholder="Exercise" value="${ex.exercise_name || ''}"></div>
      <div class="col-md-2"><input class="form-control form-control-sm ex-sets" type="number" placeholder="Sets" value="${ex.sets || ''}"></div>
      <div class="col-md-2"><input class="form-control form-control-sm ex-reps" placeholder="Reps" value="${ex.reps || ''}"></div>
      <div class="col-md-2"><input class="form-control form-control-sm ex-notes" placeholder="Notes" value="${ex.notes || ''}"></div>
      <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger removeRow">&times;</button></div>
    </div>`;
  }
  $('#addExerciseRow').on('click', () => $('#exerciseRows').append(exerciseRowHtml()));
  $(document).on('click', '.removeRow', function () { $(this).closest('.exercise-row').remove(); });

  function loadDropdowns(selectedMember, selectedTrainer) {
    $.getJSON(BASE_URL + 'ajax/workout/lookups.php', function (res) {
      const $m = $('#w_member_id').empty();
      res.members.forEach(m => $m.append(`<option value="${m.id}">${m.full_name} (${m.member_code})</option>`));
      if (selectedMember) $m.val(selectedMember);

      const $t = $('#w_trainer_id');
      $t.find('option:not(:first)').remove();
      res.trainers.forEach(t => $t.append(`<option value="${t.id}">${t.full_name}</option>`));
      if (selectedTrainer) $t.val(selectedTrainer);
    });
  }

  function resetForm() {
    $('#planForm')[0].reset();
    $('#w_id').val('');
    $('#exerciseRows').empty().append(exerciseRowHtml());
    $('#planModalTitle').text('New Workout Plan');
    loadDropdowns();
  }

  $('#btnAddPlan').on('click', function () { resetForm(); planModal.show(); });

  function loadTable() {
    $.getJSON(BASE_URL + 'ajax/workout/list.php', function (res) {
      if (table) { table.destroy(); $('#planTable').empty().html('<thead><tr><th>Member</th><th>Title</th><th>Trainer</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>'); }
      table = $('#planTable').DataTable({
        data: res.data, responsive: true,
        columns: [
          { data: null, render: d => `${d.full_name} <span class="text-muted small">(${d.member_code})</span>` },
          { data: 'title' },
          { data: 'trainer_name', render: d => d || '—' },
          { data: null, render: d => `${d.start_date} → ${d.end_date || 'Ongoing'}` },
          { data: 'status', render: d => `<span class="badge ${d === 'Active' ? 'badge-active' : 'badge-inactive'}">${d}</span>` },
          { data: null, orderable: false, render: d => `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-gradient btn-view" data-id="${d.id}"><i class="fa-solid fa-eye"></i></button>
                <button class="btn btn-sm btn-outline-gradient btn-edit" data-id="${d.id}"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${d.id}"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  loadTable();

  $('#planTable').on('click', '.btn-edit', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/workout/get.php', { id }, function (res) {
      if (!res.success) return Swal.fire('Error', res.message, 'error');
      const p = res.plan;
      $('#planModalTitle').text('Edit Workout Plan');
      $('#w_id').val(p.id);
      loadDropdowns(p.member_id, p.trainer_id);
      $('#w_title').val(p.title);
      $('#w_goal').val(p.goal);
      $('#w_start').val(p.start_date);
      $('#w_end').val(p.end_date);
      $('#w_status').val(p.status);
      $('#exerciseRows').empty();
      (res.exercises.length ? res.exercises : [{}]).forEach(ex => $('#exerciseRows').append(exerciseRowHtml(ex)));
      planModal.show();
    });
  });

  $('#planTable').on('click', '.btn-view', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/workout/get.php', { id }, function (res) {
      if (!res.success) return;
      const p = res.plan;
      let rows = res.exercises.map(e => `<tr><td>${e.day_of_week}</td><td>${e.exercise_name}</td><td>${e.sets || '—'}</td><td>${e.reps || '—'}</td><td>${e.notes || '—'}</td></tr>`).join('');
      $('#viewBody').html(`
        <h5>${p.title}</h5>
        <p class="text-muted">${p.full_name} (${p.member_code}) &middot; ${p.start_date} → ${p.end_date || 'Ongoing'} &middot; Goal: ${p.goal || '—'}</p>
        <table class="table table-sm"><thead><tr><th>Day</th><th>Exercise</th><th>Sets</th><th>Reps</th><th>Notes</th></tr></thead><tbody>${rows || '<tr><td colspan=5 class="text-muted">No exercises added.</td></tr>'}</tbody></table>
      `);
      viewModal.show();
    });
  });

  $('#planTable').on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Swal.fire({ title: 'Delete this workout plan?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#EF4444' }).then(r => {
      if (!r.isConfirmed) return;
      $.post(BASE_URL + 'ajax/workout/delete.php', { id, csrf_token: window.CSRF_TOKEN }, function (res) {
        if (res.success) { Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false }); loadTable(); }
        else Swal.fire('Error', res.message, 'error');
      }, 'json');
    });
  });

  $('#planForm').on('submit', function (e) {
    e.preventDefault();
    const exercises = [];
    $('.exercise-row').each(function () {
      const name = $(this).find('.ex-name').val();
      if (!name) return;
      exercises.push({
        day_of_week: $(this).find('.ex-day').val(),
        exercise_name: name,
        sets: $(this).find('.ex-sets').val(),
        reps: $(this).find('.ex-reps').val(),
        notes: $(this).find('.ex-notes').val(),
      });
    });
    $('#exercisesJson').val(JSON.stringify(exercises));
    $.post(BASE_URL + 'ajax/workout/save.php', $(this).serialize(), function (res) {
      if (res.success) {
        planModal.hide();
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
