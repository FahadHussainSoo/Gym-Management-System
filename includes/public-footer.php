<?php
/**
 * Public marketing site footer. Visually matches includes/footer.php
 * (same .site-footer / .footer-social styling, same copyright line)
 * but is a standalone partial - it does NOT close the admin sidebar
 * layout divs, since public pages never open them.
 */
?>
<footer class="site-footer public-footer">
  <div class="container text-center py-5">
    <div class="public-footer-brand mb-3">
      <i class="fa-solid fa-dumbbell me-2"></i><?php echo htmlspecialchars($gymName ?? get_setting($pdo, 'gym_name', 'Forge Athletic')); ?>
    </div>
    <div class="public-footer-links mb-3">
      <a href="<?php echo BASE_URL; ?>index.php">Home</a>
      <a href="<?php echo BASE_URL; ?>about.php">About</a>
      <a href="<?php echo BASE_URL; ?>pricing.php">Pricing</a>
      <a href="<?php echo BASE_URL; ?>contact.php">Contact</a>
      <a href="<?php echo BASE_URL; ?>auth/login.php">Staff Login</a>
    </div>
    <div class="footer-social mb-3">
      <a href="#" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
      <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
      <a href="#" title="GitHub"><i class="fa-brands fa-github"></i></a>
    </div>
    <div class="footer-copy small">&copy; 2026 All Rights Reserved | Developed By: Fahad Hussain</div>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
  window.CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
</script>
<script src="<?php echo BASE_URL; ?>assets/js/public.js"></script>
</body>
</html>
