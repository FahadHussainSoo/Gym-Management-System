$(function () {
  const BASE_URL = window.location.origin + '/';

  // ===== Init DataTable (only on trainers index page) =====
  // See members.js for why this is wrapped in try/catch.
  let trainersTable;
  try {
    if (!$.fn.DataTable) {
      console.error('DataTables library did not load (check that cdnjs.cloudflare.com is reachable) — trainer list/search will not render, but Add/Edit/Save will still work.');
    } else if ($('#trainersTable').length) {
      trainersTable = $('#trainersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
          url: BASE_URL + 'ajax/trainers/list.php',
          type: 'POST',
          error: function (xhr) {
            console.error('Trainers DataTable AJAX error:', xhr.status, xhr.responseText);
          },
        },
        columns: [
          { orderable: false },
          { orderable: false },
          null, null, null, null, null,
          { orderable: false },
          { orderable: false, className: 'no-print' },
        ],
        order: [[1, 'desc']],
        language: {
          search: '',
          searchPlaceholder: 'Search trainers...',
          emptyTable: 'No trainers found.',
        },
      });
    }
  } catch (err) {
    console.error('Trainers DataTable failed to initialize:', err);
  }

  // ===== Reset modal for Add =====
  window.openAddTrainer = function () {
    $('#trainerForm')[0].reset();
    $('#trainer_id').val('');
    $('#trainerModalTitle').html('<i class="fa-solid fa-user-plus me-2"></i>Add Trainer');
    $('#tPreviewImg').attr('src', BASE_URL + 'assets/img/default-avatar.png');
    showTab('#ttab-basic');
  };

  // Bootstrap 5 has no jQuery plugin bridge — $(el).tab('show') throws
  // "not a function" and silently kills whatever called it. This is the
  // real vanilla-JS equivalent, used everywhere a tab needs to be switched.
  function showTab(paneSelector) {
    const trigger = document.querySelector(`button[data-bs-target="${paneSelector}"]`);
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
  }

  // ===== Preview uploaded photo =====
  $(document).on('change', '#trainer_picture', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => $('#tPreviewImg').attr('src', e.target.result);
      reader.readAsDataURL(file);
    }
  });

  // ===== Edit Trainer =====
  window.editTrainer = function (id) {
    $.getJSON(BASE_URL + 'ajax/trainers/get.php', { id: id }, function (res) {
      if (res.status !== 'success') {
        Swal.fire('Error', res.message || 'Could not load trainer.', 'error');
        return;
      }
      const t = res.trainer;
      const form = $('#trainerForm')[0];
      form.reset();

      $('#trainer_id').val(t.id);
      $('#trainerModalTitle').html('<i class="fa-solid fa-user-pen me-2"></i>Edit Trainer');
      $('#tPreviewImg').attr('src', BASE_URL + t.profile_picture);

      $.each(t, function (key, val) {
        $(`[name="${key}"]`, form).val(val);
      });

      showTab('#ttab-basic');
      new bootstrap.Modal('#trainerModal').show();
    }).fail(function () {
      Swal.fire('Error', 'Failed to fetch trainer details.', 'error');
    });
  };

  // ===== Save Trainer (Add / Edit) via AJAX =====
  $('#saveTrainerBtn').on('click', function () {
    const form = $('#trainerForm')[0];
    if (!form.checkValidity()) {
      // The form has required fields spread across the Basic Info / Salary
      // tabs. A required field sitting in a tab that isn't currently shown
      // is display:none, so the browser can't point to it or show its
      // validation bubble — checkValidity() fails but nothing visible
      // happens, which looks exactly like the button "not working".
      // Fix: jump to whichever tab holds the first invalid field first,
      // then ask the browser to report validity so the message is visible.
      const invalidField = $(form).find(':invalid').first();
      const pane = invalidField.closest('.tab-pane');
      if (pane.length && !pane.hasClass('active')) {
        showTab('#' + pane.attr('id'));
        setTimeout(() => form.reportValidity(), 150);
      } else {
        form.reportValidity();
      }
      return;
    }
    const formData = new FormData(form);
    const btn = $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
      url: BASE_URL + 'ajax/trainers/save.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
    }).done(function (res) {
      if (res.status === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('trainerModal')).hide();
        Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
        if (trainersTable) trainersTable.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message || 'Something went wrong.', 'error');
      }
    }).fail(function () {
      Swal.fire('Error', 'Server error while saving trainer.', 'error');
    }).always(function () {
      btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Trainer');
    });
  });

  // ===== Delete Trainer =====
  window.deleteTrainer = function (id) {
    Swal.fire({
      title: 'Delete this trainer?',
      text: 'This will permanently remove the trainer and their schedule/salary/attendance records. Assigned members will be unassigned.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#7c3aed',
      confirmButtonText: 'Yes, delete',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/trainers/delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.status === 'success') {
            Swal.fire('Deleted!', res.message, 'success');
            if (trainersTable) trainersTable.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  };

  // ===== Toggle Active / Inactive =====
  window.toggleTrainerStatus = function (id, currentStatus) {
    Swal.fire({
      title: 'Change trainer status?',
      text: `This trainer will be marked ${currentStatus === 'Active' ? 'Inactive' : 'Active'}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#7c3aed',
      confirmButtonText: 'Yes, change it',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/trainers/toggle_status.php', { id: id, current_status: currentStatus, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1800, showConfirmButton: false });
            if (trainersTable) trainersTable.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  };
});
