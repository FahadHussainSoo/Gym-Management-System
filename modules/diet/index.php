<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Diet Plans';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card card-premium">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Diet Plans</span>
    <button class="btn btn-light btn-sm" id="btnAddPlan"><i class="fa-solid fa-plus me-1"></i>New Plan</button>
  </div>
  <div class="card-body">
    <table id="planTable" class="table table-hover" style="width:100%">
      <thead><tr><th>Member</th><th>Title</th><th>Trainer</th><th>Calorie Target</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="modal fade" id="planModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="planModalTitle">New Diet Plan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="planForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" id="d_id">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-600">Member</label><select name="member_id" id="d_member_id" class="form-select" required></select></div>
            <div class="col-md-6"><label class="form-label small fw-600">Trainer</label><select name="trainer_id" id="d_trainer_id" class="form-select"><option value="">— None —</option></select></div>
            <div class="col-md-6"><label class="form-label small fw-600">Title</label><input type="text" name="title" id="d_title" class="form-control" required placeholder="e.g. Cutting Plan — Phase 1"></div>
            <div class="col-md-3"><label class="form-label small fw-600">Goal</label><input type="text" name="goal" id="d_goal" class="form-control" placeholder="e.g. Fat loss"></div>
            <div class="col-md-3"><label class="form-label small fw-600">Calorie Target</label><input type="number" name="calorie_target" id="d_calories" class="form-control" placeholder="e.g. 2200"></div>
            <div class="col-md-4"><label class="form-label small fw-600">Start Date</label><input type="date" name="start_date" id="d_start" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label small fw-600">End Date</label><input type="date" name="end_date" id="d_end" class="form-control"></div>
            <div class="col-md-4"><label class="form-label small fw-600">Status</label>
              <select name="status" id="d_status" class="form-select"><option>Active</option><option>Completed</option><option>Cancelled</option></select>
            </div>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-700 mb-0">Meals</h6>
            <button type="button" class="btn btn-sm btn-outline-gradient" id="addMealRow"><i class="fa-solid fa-plus"></i> Add Meal</button>
          </div>
          <div id="mealRows"></div>
          <input type="hidden" name="meals_json" id="mealsJson">
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
      <div class="modal-header"><h5 class="modal-title">Diet Plan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
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
  const MEAL_TYPES = ['Breakfast','Lunch','Dinner','Snack'];

  function mealRowHtml(m) {
    m = m || {};
    let opts = MEAL_TYPES.map(t => `<option ${m.meal_type === t ? 'selected' : ''}>${t}</option>`).join('');
    return `<div class="row g-2 align-items-center mb-2 meal-row">
      <div class="col-md-2"><select class="form-select form-select-sm meal-type">${opts}</select></div>
      <div class="col-md-6"><input class="form-control form-control-sm meal-items" placeholder="Food items (e.g. Oats, 4 egg whites, banana)" value="${m.food_items || ''}"></div>
      <div class="col-md-2"><input class="form-control form-control-sm meal-cal" type="number" placeholder="Calories" value="${m.calories || ''}"></div>
      <div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger removeRow w-100">&times; Remove</button></div>
    </div>`;
  }
  $('#addMealRow').on('click', () => $('#mealRows').append(mealRowHtml()));
  $(document).on('click', '.removeRow', function () { $(this).closest('.meal-row').remove(); });

  function loadDropdowns(selectedMember, selectedTrainer) {
    $.getJSON(BASE_URL + 'ajax/workout/lookups.php', function (res) {
      const $m = $('#d_member_id').empty();
      res.members.forEach(m => $m.append(`<option value="${m.id}">${m.full_name} (${m.member_code})</option>`));
      if (selectedMember) $m.val(selectedMember);

      const $t = $('#d_trainer_id');
      $t.find('option:not(:first)').remove();
      res.trainers.forEach(t => $t.append(`<option value="${t.id}">${t.full_name}</option>`));
      if (selectedTrainer) $t.val(selectedTrainer);
    });
  }

  function resetForm() {
    $('#planForm')[0].reset();
    $('#d_id').val('');
    $('#mealRows').empty().append(mealRowHtml());
    $('#planModalTitle').text('New Diet Plan');
    loadDropdowns();
  }

  $('#btnAddPlan').on('click', function () { resetForm(); planModal.show(); });

  function loadTable() {
    $.getJSON(BASE_URL + 'ajax/diet/list.php', function (res) {
      if (table) { table.destroy(); $('#planTable').empty().html('<thead><tr><th>Member</th><th>Title</th><th>Trainer</th><th>Calorie Target</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>'); }
      table = $('#planTable').DataTable({
        data: res.data, responsive: true,
        columns: [
          { data: null, render: d => `${d.full_name} <span class="text-muted small">(${d.member_code})</span>` },
          { data: 'title' },
          { data: 'trainer_name', render: d => d || '—' },
          { data: 'calorie_target', render: d => d ? d + ' kcal' : '—' },
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
    $.getJSON(BASE_URL + 'ajax/diet/get.php', { id }, function (res) {
      if (!res.success) return Swal.fire('Error', res.message, 'error');
      const p = res.plan;
      $('#planModalTitle').text('Edit Diet Plan');
      $('#d_id').val(p.id);
      loadDropdowns(p.member_id, p.trainer_id);
      $('#d_title').val(p.title);
      $('#d_goal').val(p.goal);
      $('#d_calories').val(p.calorie_target);
      $('#d_start').val(p.start_date);
      $('#d_end').val(p.end_date);
      $('#d_status').val(p.status);
      $('#mealRows').empty();
      (res.meals.length ? res.meals : [{}]).forEach(m => $('#mealRows').append(mealRowHtml(m)));
      planModal.show();
    });
  });

  $('#planTable').on('click', '.btn-view', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/diet/get.php', { id }, function (res) {
      if (!res.success) return;
      const p = res.plan;
      let rows = res.meals.map(m => `<tr><td>${m.meal_type}</td><td>${m.food_items}</td><td>${m.calories || '—'}</td></tr>`).join('');
      $('#viewBody').html(`
        <h5>${p.title}</h5>
        <p class="text-muted">${p.full_name} (${p.member_code}) &middot; ${p.start_date} → ${p.end_date || 'Ongoing'} &middot; Goal: ${p.goal || '—'} &middot; Target: ${p.calorie_target || '—'} kcal</p>
        <table class="table table-sm"><thead><tr><th>Meal</th><th>Food Items</th><th>Calories</th></tr></thead><tbody>${rows || '<tr><td colspan=3 class="text-muted">No meals added.</td></tr>'}</tbody></table>
      `);
      viewModal.show();
    });
  });

  $('#planTable').on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    Swal.fire({ title: 'Delete this diet plan?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#EF4444' }).then(r => {
      if (!r.isConfirmed) return;
      $.post(BASE_URL + 'ajax/diet/delete.php', { id, csrf_token: window.CSRF_TOKEN }, function (res) {
        if (res.success) { Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false }); loadTable(); }
        else Swal.fire('Error', res.message, 'error');
      }, 'json');
    });
  });

  $('#planForm').on('submit', function (e) {
    e.preventDefault();
    const meals = [];
    $('.meal-row').each(function () {
      const items = $(this).find('.meal-items').val();
      if (!items) return;
      meals.push({
        meal_type: $(this).find('.meal-type').val(),
        food_items: items,
        calories: $(this).find('.meal-cal').val(),
      });
    });
    $('#mealsJson').val(JSON.stringify(meals));
    $.post(BASE_URL + 'ajax/diet/save.php', $(this).serialize(), function (res) {
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
