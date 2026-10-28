$(function () {
  const BASE_URL = window.location.origin + '/';

  // ===== Sidebar toggle (mobile) =====
  $('#sidebarToggle').on('click', function () {
    $('#appSidebar').toggleClass('show');
  });

  // ===== Init DataTable (only on members index page) =====
  // Wrapped in try/catch: if this throws for any reason, it must not
  // stop the rest of this script from running — otherwise the Save/Edit/
  // Delete button handlers below would never get bound, and Save would
  // silently do nothing (Cancel still "works" because it's a plain
  // Bootstrap data-bs-dismiss attribute, independent of this file).
  let membersTable;
  try {
    if (!$.fn.DataTable) {
      console.error('DataTables library did not load (check that cdnjs.cloudflare.com is reachable) — member list/search will not render, but Add/Edit/Save will still work.');
    } else if ($('#membersTable').length) {
      membersTable = $('#membersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
          url: BASE_URL + 'ajax/members/list.php',
          type: 'POST',
          error: function (xhr) {
            console.error('Members DataTable AJAX error:', xhr.status, xhr.responseText);
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
          searchPlaceholder: 'Search members...',
          emptyTable: 'No members found.',
        },
      });
    }
  } catch (err) {
    console.error('Members DataTable failed to initialize:', err);
  }

  // ===== Reset modal for Add =====
  window.openAddMember = function () {
    $('#memberForm')[0].reset();
    $('#member_id').val('');
    $('#memberModalTitle').html('<i class="fa-solid fa-user-plus me-2"></i>Add Member');
    $('#previewImg').attr('src', BASE_URL + 'assets/img/default-avatar.png');
    $('button[data-bs-target="#tab-basic"]').tab('show');
  };

  // ===== Preview uploaded photo =====
  $(document).on('change', '#profile_picture', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => $('#previewImg').attr('src', e.target.result);
      reader.readAsDataURL(file);
    }
  });

  // ===== Edit Member =====
  window.editMember = function (id) {
    $.getJSON(BASE_URL + 'ajax/members/get.php', { id: id }, function (res) {
      if (res.status !== 'success') {
        Swal.fire('Error', res.message || 'Could not load member.', 'error');
        return;
      }
      const m = res.member, med = res.medical || {}, ec = res.emergency || {};
      const form = $('#memberForm')[0];
      form.reset();

      $('#member_id').val(m.id);
      $('#memberModalTitle').html('<i class="fa-solid fa-user-pen me-2"></i>Edit Member');
      $('#previewImg').attr('src', BASE_URL + m.profile_picture);

      $.each(m, function (key, val) {
        $(`[name="${key}"]`, form).val(val);
      });
      $.each(med, function (key, val) {
        $(`[name="${key}"]`, form).val(val);
      });
      $('[name="med_notes"]', form).val(med.notes || '');
      $('[name="contact_name"]', form).val(ec.contact_name || '');
      $('[name="relationship"]', form).val(ec.relationship || '');
      $('[name="ec_phone"]', form).val(ec.phone || '');
      $('[name="ec_alt_phone"]', form).val(ec.alt_phone || '');
      $('[name="ec_address"]', form).val(ec.address || '');

      $('button[data-bs-target="#tab-basic"]').tab('show');
      new bootstrap.Modal('#memberModal').show();
    }).fail(function () {
      Swal.fire('Error', 'Failed to fetch member details.', 'error');
    });
  };

  // ===== Save Member (Add / Edit) via AJAX =====
  $('#saveMemberBtn').on('click', function () {
    const form = $('#memberForm')[0];
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    const formData = new FormData(form);
    const btn = $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
      url: BASE_URL + 'ajax/members/save.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
    }).done(function (res) {
      if (res.status === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('memberModal')).hide();
        Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
        if (membersTable) membersTable.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message || 'Something went wrong.', 'error');
      }
    }).fail(function () {
      Swal.fire('Error', 'Server error while saving member.', 'error');
    }).always(function () {
      btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Member');
    });
  });

  // ===== Delete Member =====
  window.deleteMember = function (id) {
    Swal.fire({
      title: 'Delete this member?',
      text: 'This will permanently remove the member and all related records.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#7c3aed',
      confirmButtonText: 'Yes, delete',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/members/delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.status === 'success') {
            Swal.fire('Deleted!', res.message, 'success');
            if (membersTable) membersTable.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  };

  // ===== Toggle Active / Inactive =====
  window.toggleStatus = function (id, currentStatus) {
    Swal.fire({
      title: 'Change member status?',
      text: `This member will be marked ${currentStatus === 'Active' ? 'Inactive' : 'Active'}.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#7c3aed',
      confirmButtonText: 'Yes, change it',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/members/toggle_status.php', { id: id, current_status: currentStatus, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Updated', text: res.message, timer: 1800, showConfirmButton: false });
            if (membersTable) membersTable.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  };
});
