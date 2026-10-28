<?php
require_once __DIR__ . '/config/session.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$pageTitle = 'About Us';
$activeNav = 'about';
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
$gymAddress = get_setting($pdo, 'gym_address', '');

$trainers = $pdo->query("SELECT * FROM trainers WHERE status='Active' ORDER BY experience_years DESC LIMIT 3")->fetchAll();
$memberCount  = (int) $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$trainerCount = (int) $pdo->query("SELECT COUNT(*) FROM trainers WHERE status='Active'")->fetchColumn();

require_once __DIR__ . '/includes/public-header.php';
?>

<!-- PAGE HERO -->
<section class="public-hero" style="min-height:52vh;">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-badge"><i class="fa-solid fa-circle-info"></i> About Us</div>
      <h1 style="font-size:2.4rem;">The story behind <?php echo htmlspecialchars($gymName); ?></h1>
      <p class="lead">A community-driven gym built on real coaching, honest programming, and a space where every member is known by name.</p>
    </div>
  </div>
</section>

<!-- STORY -->
<section>
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 reveal">
        <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=900&q=80" alt="Inside the gym" class="about-media">
      </div>
      <div class="col-lg-6 reveal">
        <div class="section-tag"><i class="fa-solid fa-book-open"></i> Our Story</div>
        <h2 class="section-title">Built by trainers, for real results</h2>
        <p class="section-subtitle mb-3"><?php echo htmlspecialchars($gymName); ?> started with a simple idea: fitness coaching should be personal, not generic. Every program, class, and equipment choice is made with our members' actual goals in mind — not just what looks good on a brochure.</p>
        <p class="section-subtitle mb-4">Today we're proud to support a growing community of members working toward strength, weight loss, athletic performance, and everyday health, guided by a team of experienced, certified trainers.</p>
        <div class="row g-3">
          <div class="col-6"><div class="stat-pill"><div class="num"><?php echo max($memberCount, 500); ?>+</div><div class="label">Members Trained</div></div></div>
          <div class="col-6"><div class="stat-pill"><div class="num"><?php echo max($trainerCount, 8); ?>+</div><div class="label">Certified Trainers</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VALUES -->
<section style="background:var(--bg);">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fa-solid fa-star"></i> Our Values</div>
      <h2 class="section-title">What we stand for</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4 reveal">
        <div class="feature-card">
          <div class="icon-box" style="background:linear-gradient(135deg,var(--primary),var(--purple));"><i class="fa-solid fa-handshake"></i></div>
          <h5>Real Coaching</h5>
          <p>Hands-on guidance, not just equipment access. Our trainers actually coach.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="feature-card">
          <div class="icon-box" style="background:linear-gradient(135deg,var(--success),var(--secondary));"><i class="fa-solid fa-chart-line"></i></div>
          <h5>Measurable Progress</h5>
          <p>We track what matters so you can see real, honest progress over time.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="feature-card">
          <div class="icon-box" style="background:linear-gradient(135deg,var(--orange),var(--pink));"><i class="fa-solid fa-people-group"></i></div>
          <h5>Real Community</h5>
          <p>A welcoming space where members support each other, every single day.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRAINERS TEASER -->
<?php if (!empty($trainers)): ?>
<section>
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fa-solid fa-person-running"></i> Meet the Team</div>
      <h2 class="section-title">Some of our expert trainers</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($trainers as $t): ?>
      <div class="col-md-4 reveal">
        <div class="public-card text-center">
          <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--purple));color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;">
            <i class="fa-solid fa-person-running"></i>
          </div>
          <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($t['full_name']); ?></h5>
          <div style="color:var(--primary);font-size:.82rem;font-weight:700;margin-bottom:8px;"><?php echo htmlspecialchars($t['specialization'] ?? 'Fitness Coach'); ?></div>
          <p style="font-size:.83rem;color:var(--text-muted);margin:0;"><?php echo (float) $t['experience_years']; ?> years experience</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section>
  <div class="container reveal">
    <div class="public-cta-banner">
      <h2>Come see the gym for yourself</h2>
      <p>We'd love to show you around and answer any questions.</p>
      <a href="contact.php" class="btn-hero-primary d-inline-flex"><i class="fa-solid fa-calendar-check"></i> Book a Free Tour</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
