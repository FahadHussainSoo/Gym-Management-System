<?php
require_once __DIR__ . '/config/session.php';

// Logged-in admins skip the marketing site and go straight to the dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$pageTitle = 'Home';
$activeNav = 'home';
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');

$memberCount  = (int) $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$trainerCount = (int) $pdo->query("SELECT COUNT(*) FROM trainers WHERE status='Active'")->fetchColumn();

$plans = $pdo->query("SELECT * FROM membership_plans ORDER BY price ASC LIMIT 3")->fetchAll();

require_once __DIR__ . '/includes/public-header.php';
?>

<!-- HERO -->
<section class="public-hero">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-badge"><i class="fa-solid fa-fire"></i> Karachi's Premier Fitness Club</div>
      <h1>Build the body you deserve at <?php echo htmlspecialchars($gymName); ?></h1>
      <p class="lead">Expert trainers, modern equipment, and flexible membership plans — everything you need to hit your fitness goals, all in one place.</p>
      <div class="hero-cta">
        <a href="pricing.php" class="btn-hero-primary"><i class="fa-solid fa-bolt"></i> View Membership Plans</a>
        <a href="contact.php" class="btn-hero-outline"><i class="fa-solid fa-phone"></i> Book a Free Tour</a>
      </div>
      <div class="hero-stats">
        <div><div class="num"><?php echo max($memberCount, 500); ?>+</div><div class="label">Active Members</div></div>
        <div><div class="num"><?php echo max($trainerCount, 8); ?>+</div><div class="label">Expert Trainers</div></div>
        <div><div class="num">7</div><div class="label">Days a Week</div></div>
      </div>
    </div>
  </div>
</section>

<!-- ABOUT TEASER -->
<section id="about">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 reveal">
        <div class="section-tag"><i class="fa-solid fa-dumbbell"></i> Why Choose Us</div>
        <h2 class="section-title">A gym built around <span style="color:var(--primary);">your results</span></h2>
        <p class="section-subtitle mb-4">From certified personal trainers to a fully-equipped strength and cardio floor, every part of <?php echo htmlspecialchars($gymName); ?> is designed to help you show up, stay consistent, and see real progress.</p>
        <a href="about.php" class="btn-plan d-inline-flex" style="width:auto;padding:12px 24px;">Learn More About Us <i class="fa-solid fa-arrow-right ms-2"></i></a>
      </div>
      <div class="col-lg-6">
        <div class="row g-3 reveal">
          <div class="col-6">
            <div class="feature-card">
              <div class="icon-box" style="background:linear-gradient(135deg,var(--primary),var(--purple));"><i class="fa-solid fa-person-running"></i></div>
              <h5>Certified Trainers</h5>
              <p>1-on-1 and group coaching from experienced professionals.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <div class="icon-box" style="background:linear-gradient(135deg,var(--secondary),var(--primary));"><i class="fa-solid fa-dumbbell"></i></div>
              <h5>Modern Equipment</h5>
              <p>Fully-equipped strength, cardio & functional training zones.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <div class="icon-box" style="background:linear-gradient(135deg,var(--success),var(--secondary));"><i class="fa-solid fa-calendar-check"></i></div>
              <h5>Flexible Hours</h5>
              <p>Open 7 days a week to fit around your schedule.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <div class="icon-box" style="background:linear-gradient(135deg,var(--orange),var(--pink));"><i class="fa-solid fa-shield-heart"></i></div>
              <h5>Clean & Safe</h5>
              <p>Hygienic facilities with regular equipment maintenance.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PRICING TEASER -->
<section style="background:var(--bg);">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <div class="section-tag mx-auto" style="display:inline-flex;"><i class="fa-solid fa-tags"></i> Membership Plans</div>
      <h2 class="section-title">Simple, transparent pricing</h2>
      <p class="section-subtitle mx-auto">Pick a plan that fits your routine. No hidden fees, cancel anytime.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($plans as $plan): $features = $plan['features'] ? explode('|', $plan['features']) : []; ?>
      <div class="col-md-4 reveal">
        <div class="pricing-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?>">
          <?php if ($plan['is_featured']): ?><div class="plan-ribbon">Most Popular</div><?php endif; ?>
          <h4><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
          <div class="plan-desc"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></div>
          <div class="plan-price">$<?php echo number_format($plan['price'], 0); ?><span>/<?php echo (int) $plan['duration_months'] === 1 ? 'mo' : $plan['duration_months'] . ' mo'; ?></span></div>
          <ul class="mt-3">
            <?php foreach ($features as $f): ?><li><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($f); ?></li><?php endforeach; ?>
          </ul>
          <a href="contact.php" class="btn-plan">Get Started</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4 reveal">
      <a href="pricing.php" style="color:var(--primary);font-weight:700;">See all plans &amp; full details <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
  </div>
</section>

<!-- CTA -->
<section>
  <div class="container reveal">
    <div class="public-cta-banner">
      <h2>Ready to start your fitness journey?</h2>
      <p>Come in for a free tour and consultation — no commitment required.</p>
      <a href="contact.php" class="btn-hero-primary d-inline-flex"><i class="fa-solid fa-calendar-check"></i> Book Your Free Tour</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
