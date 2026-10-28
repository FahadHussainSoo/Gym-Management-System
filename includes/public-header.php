<?php
/**
 * Public marketing site header/navbar.
 * Used by index.php (home), about.php, pricing.php, contact.php.
 * Kept separate from includes/header.php (which is the logged-in
 * admin layout with the sidebar) so neither breaks the other.
 *
 * Expects, from the including page:
 *   $pageTitle  (string)
 *   $activeNav  (string) one of: home, about, pricing, contact
 */
require_once __DIR__ . '/../config/session.php';
$gymName = get_setting($pdo, 'gym_name', 'Forge Athletic');
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?><?php echo htmlspecialchars($gymName); ?></title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
<link href="<?php echo BASE_URL; ?>assets/css/public.css" rel="stylesheet">
</head>
<body class="public-site">

<nav class="public-navbar" id="publicNavbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?php echo BASE_URL; ?>index.php" class="public-brand">
      <span class="brand-icon"><i class="fa-solid fa-dumbbell"></i></span>
      <?php echo htmlspecialchars($gymName); ?>
    </a>

    <button class="public-nav-toggle d-lg-none" id="publicNavToggle"><i class="fa-solid fa-bars"></i></button>

    <div class="public-nav-links" id="publicNavLinks">
      <a href="<?php echo BASE_URL; ?>index.php" class="<?php echo $activeNav === 'home' ? 'active' : ''; ?>">Home</a>
      <a href="<?php echo BASE_URL; ?>about.php" class="<?php echo $activeNav === 'about' ? 'active' : ''; ?>">About</a>
      <a href="<?php echo BASE_URL; ?>pricing.php" class="<?php echo $activeNav === 'pricing' ? 'active' : ''; ?>">Pricing</a>
      <a href="<?php echo BASE_URL; ?>contact.php" class="<?php echo $activeNav === 'contact' ? 'active' : ''; ?>">Contact</a>
      <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn-nav-login"><i class="fa-solid fa-right-to-bracket me-1"></i> Staff Login</a>
    </div>
  </div>
</nav>
<div class="public-nav-overlay" id="publicNavOverlay"></div>
