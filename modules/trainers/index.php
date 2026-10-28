<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Trainer Management';
include __DIR__ . '/../../includes/header.php';
?>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
  <?php
    $totalTrainers  = $pdo->query("SELECT COUNT(*) c FROM trainers")->fetch()['c'];
    $activeTrainers = $pdo->query("SELECT COUNT(*) c FROM trainers WHERE status='Active'")->fetch()['c'];
    $inactiveTrainers = $totalTrainers - $activeTrainers;
    $monthlyPayroll = $pdo->query("SELECT COALESCE(SUM(monthly_salary),0) s FROM trainers WHERE status='Active'")->fetch()['s'];
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card purple"><i class="fa-solid fa-person-running"></i><p>Total Trainers</p><h3><?php echo $totalTrainers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card green"><i class="fa-solid fa-user-check"></i><p>Active</p><h3><?php echo $activeTrainers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card pink"><i class="fa-solid fa-user-xmark"></i><p>Inactive</p><h3><?php echo $inactiveTrainers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card cyan"><i class="fa-solid fa-sack-dollar"></i><p>Monthly Payroll</p><h3>Rs <?php echo number_format($monthlyPayroll, 2); ?></h3></div>
  </div>
</div>

<!-- ===== Trainer List Card ===== -->
<div class="card card-premium">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <span><i class="fa-solid fa-person-running me-2"></i>All Trainers</span>
    <button class="btn btn-light btn-sm fw-600" data-bs-toggle="modal" data-bs-target="#trainerModal" onclick="openAddTrainer()">
      <i class="fa-solid fa-user-plus me-1"></i> Add Trainer
    </button>
  </div>
  <div class="card-body">
    <table id="trainersTable" class="table table-hover align-middle w-100">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Code</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Specialization</th>
          <th>Experience</th>
          <th>Salary</th>
          <th>Status</th>
          <th class="no-print">Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ===== Add / Edit Trainer Modal ===== -->
<div class="modal fade" id="trainerModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px; border:none;">
      <div class="modal-header" style="background:linear-gradient(90deg,#7c3aed,#ec4899); color:#fff;">
        <h5 class="modal-title" id="trainerModalTitle"><i class="fa-solid fa-user-plus me-2"></i>Add Trainer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="trainerForm">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" id="trainer_id">

          <ul class="nav nav-tabs profile-tabs mb-3">
            <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#ttab-basic">Basic Info</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#ttab-salary">Salary</button></li>
          </ul>

          <div class="tab-content">
            <!-- Basic Info -->
            <div class="tab-pane fade show active" id="ttab-basic">
              <div class="text-center mb-3">
                <img id="tPreviewImg" src="<?php echo BASE_URL; ?>assets/img/default-avatar.png" class="member-thumb-lg mb-2">
                <div>
                  <label class="btn btn-outline-gradient btn-sm">
                    <i class="fa-solid fa-camera me-1"></i> Upload Photo
                    <input type="file" name="profile_picture" id="trainer_picture" accept="image/*" hidden>
                  </label>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Full Name *</label>
                  <input type="text" class="form-control" name="full_name" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Gender</label>
                  <select class="form-select" name="gender">
                    <option>Male</option><option>Female</option><option>Other</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Date of Birth</label>
                  <input type="date" class="form-control" name="dob">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone *</label>
                  <input type="text" class="form-control" name="phone" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email">
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <textarea class="form-control" name="address" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Specialization</label>
                  <input type="text" class="form-control" name="specialization" placeholder="e.g. Yoga, CrossFit, Strength Training">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Experience (yrs)</label>
                  <input type="number" step="0.5" class="form-control" name="experience_years">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Joining Date *</label>
                  <input type="date" class="form-control" name="joining_date" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Certification</label>
                  <input type="text" class="form-control" name="certification">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Salary -->
            <div class="tab-pane fade" id="ttab-salary">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Monthly Salary *</label>
                  <input type="number" step="0.01" class="form-control" name="monthly_salary" required>
                </div>
              </div>
              <p class="text-muted small mt-3 mb-0">
                <i class="fa-solid fa-circle-info me-1"></i>
                This sets the trainer's base monthly salary. Individual monthly payouts (bonus / deductions) are recorded from the Trainer Profile page.
              </p>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient" id="saveTrainerBtn"><i class="fa-solid fa-floppy-disk me-1"></i> Save Trainer</button>
      </div>
    </div>
  </div>
</div>

<?php $extraScripts = ['assets/js/trainers.js']; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
