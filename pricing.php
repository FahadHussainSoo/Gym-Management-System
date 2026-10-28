<?php
require_once __DIR__ . '/config/session.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'modules/dashboard/index.php');
    exit;
}

$pageTitle = 'Pricing';
$activeNav = 'pricing';
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');

$plans = $pdo->query("SELECT * FROM membership_plans ORDER BY price ASC")->fetchAll();

require_once __DIR__ . '/includes/public-header.php';
?>

<section class="public-hero" style="min-height:48vh;">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-badge"><i class="fa-solid fa-tags"></i> Membership Plans</div>
      <h1 style="font-size:2.4rem;">Simple, transparent pricing</h1>
      <p class="lead">No hidden fees, no long-term contracts required. Pick the plan that fits your goals and get started today.</p>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <?php if (empty($plans)): ?>
      <p class="text-center text-muted">Membership plans will be listed here soon. Please contact us for current pricing.</p>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($plans as $plan): $features = $plan['features'] ? explode('|', $plan['features']) : []; ?>
      <div class="col-md-6 col-lg-3 reveal">
        <div class="pricing-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?>">
          <?php if ($plan['is_featured']): ?><div class="plan-ribbon">Most Popular</div><?php endif; ?>
          <h4><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
          <div class="plan-desc"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></div>
          <div class="plan-price">$<?php echo number_format($plan['price'], 0); ?><span>/<?php echo (int) $plan['duration_months'] === 1 ? 'mo' : $plan['duration_months'] . ' mo'; ?></span></div>
          <ul class="mt-3">
            <?php if (empty($features)): ?>
              <li><i class="fa-solid fa-circle-check"></i> Full gym access</li>
            <?php else: foreach ($features as $f): ?>
              <li><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($f); ?></li>
            <?php endforeach; endif; ?>
          </ul>
          <a href="contact.php" class="btn-plan">Get Started</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="text-center mt-5 reveal">
      <p class="section-subtitle mx-auto mb-3">Not sure which plan is right for you? Our team is happy to help you choose.</p>
      <a href="contact.php" class="btn-plan d-inline-flex" style="width:auto;padding:14px 28px;">Talk to Us <i class="fa-solid fa-arrow-right ms-2"></i></a>
    </div>
  </div>
</section>

<!-- FAQ-style notes -->
<section style="background:var(--bg);">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4 reveal">
        <div class="public-card">
          <h5 class="fw-bold mb-2"><i class="fa-solid fa-rotate me-2" style="color:var(--primary);"></i>Cancel Anytime</h5>
          <p style="font-size:.86rem;color:var(--text-muted);margin:0;">No long-term lock-in on our monthly plan — pause or cancel whenever you need to.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="public-card">
          <h5 class="fw-bold mb-2"><i class="fa-solid fa-tag me-2" style="color:var(--secondary);"></i>No Hidden Fees</h5>
          <p style="font-size:.86rem;color:var(--text-muted);margin:0;">The price you see is the price you pay — no surprise charges.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="public-card">
          <h5 class="fw-bold mb-2"><i class="fa-solid fa-people-arrows me-2" style="color:var(--success);"></i>Upgrade Anytime</h5>
          <p style="font-size:.86rem;color:var(--text-muted);margin:0;">Switch to a longer plan whenever you're ready — just ask our front desk.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
