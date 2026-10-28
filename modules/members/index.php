<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Member Management';
$plans = $pdo->query("SELECT id, plan_name FROM membership_plans ORDER BY plan_name")->fetchAll();
include __DIR__ . '/../../includes/header.php';
?>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
  <?php
    $totalMembers  = $pdo->query("SELECT COUNT(*) c FROM members")->fetch()['c'];
    $activeMembers = $pdo->query("SELECT COUNT(*) c FROM members WHERE status='Active'")->fetch()['c'];
    $inactiveMembers = $totalMembers - $activeMembers;
    $expiringSoon  = $pdo->query("SELECT COUNT(*) c FROM members WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetch()['c'];
  ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card purple"><i class="fa-solid fa-users"></i><p>Total Members</p><h3><?php echo $totalMembers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card green"><i class="fa-solid fa-user-check"></i><p>Active</p><h3><?php echo $activeMembers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card pink"><i class="fa-solid fa-user-xmark"></i><p>Inactive</p><h3><?php echo $inactiveMembers; ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card cyan"><i class="fa-solid fa-hourglass-half"></i><p>Expiring in 7 Days</p><h3><?php echo $expiringSoon; ?></h3></div>
  </div>
</div>

<!-- ===== Member List Card ===== -->
<div class="card card-premium">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <span><i class="fa-solid fa-users me-2"></i>All Members</span>
    <button class="btn btn-light btn-sm fw-600" data-bs-toggle="modal" data-bs-target="#memberModal" onclick="openAddMember()">
      <i class="fa-solid fa-user-plus me-1"></i> Add Member
    </button>
  </div>
  <div class="card-body">
    <table id="membersTable" class="table table-hover align-middle w-100">
      <thead>
        <tr>
          <th>Photo</th>
          <th>Code</th>
          <th>Name</th>
          <th>Phone</th>
          <th>Plan</th>
          <th>Join Date</th>
          <th>Expiry</th>
          <th>Status</th>
          <th class="no-print">Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ===== Add / Edit Member Modal ===== -->
<div class="modal fade" id="memberModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px; border:none;">
      <div class="modal-header" style="background:linear-gradient(90deg,#7c3aed,#ec4899); color:#fff;">
        <h5 class="modal-title" id="memberModalTitle"><i class="fa-solid fa-user-plus me-2"></i>Add Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="memberForm">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" id="member_id">

          <!-- Tabs: Basic / Medical / Emergency -->
          <ul class="nav nav-tabs profile-tabs mb-3" id="memberTabs">
            <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic">Basic Info</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-medical">Medical Info</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-emergency">Emergency Contact</button></li>
          </ul>

          <div class="tab-content">
            <!-- Basic Info -->
            <div class="tab-pane fade show active" id="tab-basic">
              <div class="text-center mb-3">
                <img id="previewImg" src="<?php echo BASE_URL; ?>assets/img/default-avatar.png" class="member-thumb-lg mb-2">
                <div>
                  <label class="btn btn-outline-gradient btn-sm">
                    <i class="fa-solid fa-camera me-1"></i> Upload Photo
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" hidden>
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
                <div class="col-md-4">
                  <label class="form-label">Membership Plan</label>
                  <select class="form-select" name="membership_plan_id">
                    <option value="">-- Select Plan --</option>
                    <?php foreach ($plans as $p): ?>
                      <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['plan_name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Join Date *</label>
                  <input type="date" class="form-control" name="join_date" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Expiry Date</label>
                  <input type="date" class="form-control" name="expiry_date">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Medical Info -->
            <div class="tab-pane fade" id="tab-medical">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Blood Group</label>
                  <select class="form-select" name="blood_group">
                    <option value="">--</option>
                    <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                      <option><?php echo $bg; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Height (cm)</label>
                  <input type="number" step="0.1" class="form-control" name="height_cm">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Weight (kg)</label>
                  <input type="number" step="0.1" class="form-control" name="weight_kg">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Doctor Phone</label>
                  <input type="text" class="form-control" name="doctor_phone">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Doctor Name</label>
                  <input type="text" class="form-control" name="doctor_name">
                </div>
                <div class="col-12">
                  <label class="form-label">Medical Conditions</label>
                  <textarea class="form-control" name="medical_conditions" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Allergies</label>
                  <textarea class="form-control" name="allergies" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Current Medications</label>
                  <textarea class="form-control" name="medications" rows="2"></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label">Notes</label>
                  <textarea class="form-control" name="med_notes" rows="2"></textarea>
                </div>
              </div>
            </div>

            <!-- Emergency Contact -->
            <div class="tab-pane fade" id="tab-emergency">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Contact Name *</label>
                  <input type="text" class="form-control" name="contact_name">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Relationship</label>
                  <input type="text" class="form-control" name="relationship" placeholder="e.g. Parent, Spouse">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone *</label>
                  <input type="text" class="form-control" name="ec_phone">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Alternate Phone</label>
                  <input type="text" class="form-control" name="ec_alt_phone">
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <textarea class="form-control" name="ec_address" rows="2"></textarea>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient" id="saveMemberBtn"><i class="fa-solid fa-floppy-disk me-1"></i> Save Member</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
