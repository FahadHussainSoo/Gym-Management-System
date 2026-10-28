/* =====================================================================
   PUBLIC.JS - marketing site behavior (navbar, mobile menu, reveal, contact form)
   ===================================================================== */

$(document).ready(function () {

  /* ---------------- Sticky navbar shrink-on-scroll ---------------- */
  function syncNavbar() {
    $('#publicNavbar').toggleClass('scrolled', $(window).scrollTop() > 40);
  }
  syncNavbar();
  $(window).on('scroll', syncNavbar);

  /* ---------------- Mobile nav toggle ---------------- */
  $('#publicNavToggle').on('click', function () {
    $('#publicNavLinks').toggleClass('open');
    $('#publicNavOverlay').toggleClass('show');
  });
  $('#publicNavOverlay').on('click', function () {
    $('#publicNavLinks').removeClass('open');
    $(this).removeClass('show');
  });

  /* ---------------- Scroll reveal ---------------- */
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    revealEls.forEach(el => observer.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('in-view'));
  }

  /* ---------------- Contact form AJAX submit ---------------- */
  $('#publicContactForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]');
    const originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Sending...');

    $.ajax({
      url: BASE_URL + 'contact-process.php',
      method: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (res) {
        $btn.prop('disabled', false).html(originalHtml);
        if (res.success) {
          Swal.fire({ icon: 'success', title: 'Message Sent!', text: res.message, confirmButtonColor: '#4F46E5' });
          $('#publicContactForm')[0].reset();
        } else {
          Swal.fire({ icon: 'error', title: 'Something went wrong', text: res.message, confirmButtonColor: '#EF4444' });
        }
      },
      error: function () {
        $btn.prop('disabled', false).html(originalHtml);
        Swal.fire({ icon: 'error', title: 'Server Error', text: 'Please try again in a moment.', confirmButtonColor: '#EF4444' });
      }
    });
  });

});
