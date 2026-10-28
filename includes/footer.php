  </main>
</div>
</div>

<footer class="site-footer">
  <div class="container text-center py-4">
    <div class="footer-social mb-2">
      <a href="#" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
      <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
      <a href="#" title="GitHub"><i class="fa-brands fa-github"></i></a>
    </div>
    <div class="footer-copy small">&copy; 2026 All Rights Reserved | Developed By: Fahad Hussain</div>
  </div>
</footer>

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
  // Automatically attach the CSRF token to every AJAX POST (form data or plain object),
  // so existing $.post / $.ajax calls across the app don't need individual edits.
  $(document).ajaxSend(function (event, jqxhr, settings) {
    if (settings.type && settings.type.toUpperCase() !== 'POST') return;
    if (settings.data instanceof FormData) {
      if (!settings.data.has('csrf_token')) settings.data.append('csrf_token', window.CSRF_TOKEN);
    } else if (typeof settings.data === 'string') {
      if (settings.data.indexOf('csrf_token=') === -1) {
        settings.data += (settings.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
      }
    }
  });
</script>
<!-- Bootstrap 5 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<!-- Chart.js (Reports) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/theme.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/notifications.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/members.js"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
  <?php foreach ($extraScripts as $script): ?>
    <script src="<?php echo BASE_URL . $script; ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
