<?php
require_once __DIR__ . '/../../config/session.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT m.*, p.plan_name FROM members m
                        LEFT JOIN membership_plans p ON p.id = m.membership_plan_id
                        WHERE m.id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    die('Member not found.');
}

$medical = $pdo->prepare("SELECT * FROM member_medical_info WHERE member_id = ?");
$medical->execute([$id]);
$medical = $medical->fetch();

$emergency = $pdo->prepare("SELECT * FROM member_emergency_contacts WHERE member_id = ?");
$emergency->execute([$id]);
$emergency = $emergency->fetch();

$history = $pdo->prepare("SELECT h.*, p.plan_name FROM membership_history h
                           LEFT JOIN membership_plans p ON p.id = h.plan_id
                           WHERE h.member_id = ? ORDER BY h.created_at DESC");
$history->execute([$id]);
$history = $history->fetchAll();

$pageTitle = 'Member Profile';
include __DIR__ . '/../../includes/header.php';
?>

<div class="profile-header d-flex flex-wrap align-items-center gap-4 mb-4">
  <img src="<?php echo BASE_URL . htmlspecialchars($member['profile_picture']); ?>" class="member-thumb-lg">
  <div class="flex-grow-1">
    <h3 class="mb-1"><?php echo htmlspecialchars($member['full_name']); ?></h3>
    <p class="mb-1 opacity-75"><i class="fa-solid fa-id-badge me-1"></i> <?php echo htmlspecialchars($member['member_code']); ?> &nbsp;|&nbsp;
       <i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($member['phone']); ?></p>
    <span class="<?php echo $member['status'] === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
      <?php echo $member['status']; ?>
    </span>
  </div>
  <div class="no-print">
    <a href="print_card.php?id=<?php echo $member['id']; ?>" target="_blank" class="btn btn-light fw-600">
      <i class="fa-solid fa-print me-1"></i> Print Member Card
    </a>
    <a href="index.php" class="btn btn-outline-light">
      <i class="fa-solid fa-arrow-left me-1"></i> Back
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-user me-2"></i>Basic Details</div>
      <div class="card-body">
        <p><b>Gender:</b> <?php echo htmlspecialchars($member['gender']); ?></p>
        <p><b>DOB:</b> <?php echo htmlspecialchars($member['dob'] ?: '—'); ?></p>
        <p><b>Email:</b> <?php echo htmlspecialchars($member['email'] ?: '—'); ?></p>
        <p><b>Address:</b> <?php echo htmlspecialchars($member['address'] ?: '—'); ?></p>
        <p><b>Plan:</b> <?php echo htmlspecialchars($member['plan_name'] ?: '—'); ?></p>
        <p><b>Join Date:</b> <?php echo htmlspecialchars($member['join_date']); ?></p>
        <p class="mb-0"><b>Expiry Date:</b> <?php echo htmlspecialchars($member['expiry_date'] ?: '—'); ?></p>
      </div>
    </div>

    <div class="card card-premium mb-3">
      <div class="card-header"><i class="fa-solid fa-notes-medical me-2"></i>Medical Information</div>
      <div class="card-body">
        <?php if ($medical): ?>
          <p><b>Blood Group:</b> <?php echo htmlspecialchars($medical['blood_group'] ?: '—'); ?></p>
          <p><b>Height/Weight:</b> <?php echo htmlspecialchars($medical['height_cm'] ?: '—'); ?> cm / <?php echo htmlspecialchars($medical['weight_kg'] ?: '—'); ?> kg</p>
          <p><b>Conditions:</b> <?php echo htmlspecialchars($medical['medical_conditions'] ?: 'None reported'); ?></p>
          <p><b>Allergies:</b> <?php echo htmlspecialchars($medical['allergies'] ?: 'None reported'); ?></p>
          <p class="mb-0"><b>Doctor:</b> <?php echo htmlspecialchars($medical['doctor_name'] ?: '—'); ?> (<?php echo htmlspecialchars($medical['doctor_phone'] ?: '—'); ?>)</p>
        <?php else: ?>
          <p class="text-muted mb-0">No medical information on file.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card card-premium">
      <div class="card-header"><i class="fa-solid fa-phone-volume me-2"></i>Emergency Contact</div>
      <div class="card-body">
        <?php if ($emergency): ?>
          <p><b>Name:</b> <?php echo htmlspecialchars($emergency['contact_name']); ?></p>
          <p><b>Relationship:</b> <?php echo htmlspecialchars($emergency['relationship'] ?: '—'); ?></p>
          <p><b>Phone:</b> <?php echo htmlspecialchars($emergency['phone']); ?></p>
          <p class="mb-0"><b>Alt Phone:</b> <?php echo htmlspecialchars($emergency['alt_phone'] ?: '—'); ?></p>
        <?php else: ?>
          <p class="text-muted mb-0">No emergency contact on file.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card card-premium">
      <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>Membership History</div>
      <div class="card-body">
        <?php if ($history): ?>
          <table class="table table-hover align-middle">
            <thead><tr><th>Action</th><th>Plan</th><th>Start</th><th>End</th><th>Remarks</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td><?php echo htmlspecialchars($h['action']); ?></td>
                  <td><?php echo htmlspecialchars($h['plan_name'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($h['start_date'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($h['end_date'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($h['remarks'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($h['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted mb-0">No membership history recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
