<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Settings';

$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<ul class="nav nav-tabs mb-3" id="settingsTabs">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general"><i class="fa-solid fa-building me-1"></i>Gym Info</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-theme"><i class="fa-solid fa-palette me-1"></i>Theme</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-email"><i class="fa-solid fa-envelope me-1"></i>Email Settings</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-backup"><i class="fa-solid fa-database me-1"></i>Backup &amp; Restore</button></li>
</ul>

<div class="tab-content">

  <!-- GYM INFO -->
  <div class="tab-pane fade show active" id="tab-general">
    <div class="card card-premium">
      <div class="card-header">Gym Information &amp; Logo</div>
      <div class="card-body">
        <form id="generalForm" enctype="multipart/form-data">
          <?php echo csrf_field(); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-600">Gym Name</label>
              <input type="text" name="gym_name" class="form-control" value="<?php echo htmlspecialchars($settings['gym_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-600">Gym Phone</label>
              <input type="text" name="gym_phone" class="form-control" value="<?php echo htmlspecialchars($settings['gym_phone'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-600">Gym Email</label>
              <input type="email" name="gym_email" class="form-control" value="<?php echo htmlspecialchars($settings['gym_email'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-600">Logo</label>
              <div class="d-flex align-items-center gap-3">
                <img src="<?php echo !empty($settings['gym_logo']) ? BASE_URL . htmlspecialchars($settings['gym_logo']) : 'data:,'; ?>" id="logoPreview" style="width:52px;height:52px;border-radius:10px;object-fit:cover;<?php echo empty($settings['gym_logo']) ? 'display:none;' : ''; ?>background:#eee;">
                <input type="file" name="gym_logo" accept="image/png,image/jpeg,image/webp" class="form-control">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-600">Address</label>
              <textarea name="gym_address" class="form-control" rows="2"><?php echo htmlspecialchars($settings['gym_address'] ?? ''); ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-gradient mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <!-- THEME -->
  <div class="tab-pane fade" id="tab-theme">
    <div class="card card-premium">
      <div class="card-header">Theme Settings</div>
      <div class="card-body">
        <p class="text-muted small">Toggle dark/light mode any time from the moon/sun icon in the top bar — it's saved per browser. Below is the app's default color system (defined in <code>assets/css/style.css</code>).</p>
        <div class="row g-2">
          <?php
          $palette = ['Primary' => '#4F46E5', 'Secondary' => '#06B6D4', 'Success' => '#10B981', 'Warning' => '#F59E0B', 'Danger' => '#EF4444', 'Purple' => '#8B5CF6', 'Pink' => '#EC4899', 'Orange' => '#F97316'];
          foreach ($palette as $name => $hex): ?>
            <div class="col-md-3 col-6">
              <div class="d-flex align-items-center gap-2 p-2 border rounded-3">
                <div style="width:28px;height:28px;border-radius:8px;background:<?php echo $hex; ?>;"></div>
                <div><div class="small fw-600"><?php echo $name; ?></div><div class="small text-muted"><?php echo $hex; ?></div></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- EMAIL -->
  <div class="tab-pane fade" id="tab-email">
    <div class="card card-premium">
      <div class="card-header">Email (SMTP) Settings</div>
      <div class="card-body">
        <div class="alert alert-warning small">
          These credentials are stored so the app can send real email once an SMTP library (e.g. PHPMailer) is wired in.
          They are not yet used to send mail automatically — see testing instructions.
        </div>
        <form id="emailForm">
          <?php echo csrf_field(); ?>
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label small fw-600">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"></div>
            <div class="col-md-3"><label class="form-label small fw-600">Port</label><input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>"></div>
            <div class="col-md-3"><label class="form-label small fw-600">Encryption</label>
              <select name="smtp_encryption" class="form-select">
                <?php foreach (['tls','ssl','none'] as $enc): ?>
                  <option value="<?php echo $enc; ?>" <?php echo ($settings['smtp_encryption'] ?? 'tls') === $enc ? 'selected' : ''; ?>><?php echo strtoupper($enc); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label small fw-600">SMTP Username</label><input type="text" name="smtp_username" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>"></div>
            <div class="col-md-6"><label class="form-label small fw-600">SMTP Password</label><input type="password" name="smtp_password" class="form-control" value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>"></div>
          </div>
          <button type="submit" class="btn btn-gradient mt-3"><i class="fa-solid fa-floppy-disk me-1"></i>Save Email Settings</button>
        </form>
      </div>
    </div>
  </div>

  <!-- BACKUP / RESTORE -->
  <div class="tab-pane fade" id="tab-backup">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card card-premium h-100">
          <div class="card-header">Backup Database</div>
          <div class="card-body">
            <p class="text-muted small">Downloads a full <code>.sql</code> dump of every table in <code>gym_system</code>.</p>
            <a href="<?php echo BASE_URL; ?>ajax/settings/backup.php?csrf_token=<?php echo urlencode(csrf_token()); ?>" class="btn btn-gradient"><i class="fa-solid fa-download me-1"></i>Download Backup</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card card-premium h-100">
          <div class="card-header">Restore Database</div>
          <div class="card-body">
            <div class="alert alert-danger small">This runs every statement in the uploaded <code>.sql</code> file against your live database. Back up first — this cannot be undone.</div>
            <form id="restoreForm" enctype="multipart/form-data">
              <?php echo csrf_field(); ?>
              <input type="file" name="sql_file" accept=".sql" class="form-control mb-3" required>
              <button type="submit" class="btn btn-outline-gradient"><i class="fa-solid fa-upload me-1"></i>Restore From File</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
$('#logoPreview').closest('.tab-pane').find('input[type=file]').on('change', function () {
  const f = this.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => { $('#logoPreview').attr('src', e.target.result).show(); };
  r.readAsDataURL(f);
});

function saveForm(formSelector, url, successMsg) {
  $(formSelector).on('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    $.ajax({ url: BASE_URL + url, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
      .done(res => {
        if (res.success) {
          Swal.fire({ icon: 'success', title: successMsg, confirmButtonColor: '#4F46E5', timer: 1800, showConfirmButton: false });
          if (res.reload) setTimeout(() => location.reload(), 900);
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: res.message, confirmButtonColor: '#4F46E5' });
        }
      })
      .fail(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save. Please try again.', confirmButtonColor: '#4F46E5' }));
  });
}

saveForm('#generalForm', 'ajax/settings/save_general.php', 'Gym info updated');
saveForm('#emailForm', 'ajax/settings/save_email.php', 'Email settings saved');

$('#restoreForm').on('submit', function (e) {
  e.preventDefault();
  Swal.fire({
    title: 'Restore database?', text: 'This will overwrite existing data. This cannot be undone.', icon: 'warning',
    showCancelButton: true, confirmButtonText: 'Restore', confirmButtonColor: '#EF4444'
  }).then(result => {
    if (!result.isConfirmed) return;
    const fd = new FormData(document.getElementById('restoreForm'));
    $.ajax({ url: BASE_URL + 'ajax/settings/restore.php', method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
      .done(res => {
        if (res.success) Swal.fire({ icon: 'success', title: 'Database restored', confirmButtonColor: '#4F46E5' });
        else Swal.fire({ icon: 'error', title: 'Restore failed', text: res.message, confirmButtonColor: '#4F46E5' });
      })
      .fail(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Restore failed unexpectedly.', confirmButtonColor: '#4F46E5' }));
  });
});
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
