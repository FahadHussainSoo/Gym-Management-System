(function () {
  const btn = document.getElementById('themeToggle');
  const icon = document.getElementById('themeToggleIcon');
  if (!btn) return;

  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
  }

  btn.addEventListener('click', function () {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    setCookie('theme_mode', next, 365);
    if (icon) {
      icon.classList.toggle('fa-moon', next === 'light');
      icon.classList.toggle('fa-sun', next === 'dark');
    }
  });
})();
