<?php
require_once __DIR__ . '/config/session.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$pageTitle = 'Contact Us';
$activeNav = 'contact';
$gymName    = get_setting($pdo, 'gym_name', 'Forge Athletic');
$gymAddress = get_setting($pdo, 'gym_address', '');
$gymPhone   = get_setting($pdo, 'gym_phone', '');
$gymEmail   = get_setting($pdo, 'gym_email', '');

require_once __DIR__ . '/includes/public-header.php';
?>

<section class="public-hero" style="min-height:44vh;">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-badge"><i class="fa-solid fa-envelope"></i> Get In Touch</div>
      <h1 style="font-size:2.4rem;">We'd love to hear from you</h1>
      <p class="lead">Questions about membership, a free tour, or anything else — send us a message and our team will get back to you shortly.</p>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5 reveal">
        <div class="contact-info-card">
          <h3>Contact Information</h3>
          <p class="lead-text">Reach out directly, or fill out the form and we'll respond within 24 hours.</p>

          <div class="contact-info-item">
            <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
            <div><h6>Address</h6><span><?php echo htmlspecialchars($gymAddress ?: 'Visit us at our location'); ?></span></div>
          </div>
          <div class="contact-info-item">
            <div class="icon-box"><i class="fa-solid fa-phone"></i></div>
            <div><h6>Phone</h6><span><?php echo htmlspecialchars($gymPhone ?: '—'); ?></span></div>
          </div>
          <div class="contact-info-item">
            <div class="icon-box"><i class="fa-solid fa-envelope"></i></div>
            <div><h6>Email</h6><span><?php echo htmlspecialchars($gymEmail ?: '—'); ?></span></div>
          </div>
          <div class="contact-info-item">
            <div class="icon-box"><i class="fa-solid fa-clock"></i></div>
            <div><h6>Hours</h6><span>Mon – Sun: 6:00 AM – 11:00 PM</span></div>
          </div>

          <div class="footer-social mt-4">
            <a href="#" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
            <a href="#" title="GitHub"><i class="fa-brands fa-github"></i></a>
          </div>
        </div>
      </div>

      <div class="col-lg-7 reveal">
        <div class="contact-form-card">
          <h4 class="fw-bold mb-1">Send Us a Message</h4>
          <p class="section-subtitle mb-4">Fill in the form below and we'll get back to you shortly.</p>

          <form id="publicContactForm" class="form-public">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label>Full Name</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label>Phone (optional)</label>
                <input type="text" name="phone" class="form-control">
              </div>
              <div class="col-md-6">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="e.g. Membership Inquiry">
              </div>
              <div class="col-12">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="5" required></textarea>
              </div>
            </div>
            <button type="submit" class="btn-plan mt-4" style="width:auto;padding:13px 30px;">
              <i class="fa-solid fa-paper-plane me-2"></i>Send Message
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<section style="background:var(--bg);padding-top:0;">
  <div class="container reveal">
    <div class="ratio ratio-21x9" style="border-radius:20px;overflow:hidden;box-shadow:0 8px 24px rgba(17,24,39,.08);">
      <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=67.0%2C24.8%2C67.1%2C24.9&layer=mapnik" style="border:0;" loading="lazy"></iframe>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
