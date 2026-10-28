(function ($) {
  const $dropdown = $('#notifDropdown');
  if (!$dropdown.length) return;

  const iconFor = { info: 'fa-circle-info', success: 'fa-check', warning: 'fa-triangle-exclamation', danger: 'fa-circle-exclamation' };
  const colorFor = { info: '#06B6D4', success: '#10B981', warning: '#F59E0B', danger: '#EF4444' };

  function timeAgo(dateStr) {
    const diff = (Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  function load() {
    $.getJSON(BASE_URL + 'ajax/settings/notifications_list.php', function (res) {
      if (!res.success) return;
      const items = res.data;
      const unread = items.filter(n => !Number(n.is_read)).length;
      $('#notifDot').toggle(unread > 0);

      if (items.length === 0) {
        $dropdown.html('<div class="text-center text-muted small py-3">No notifications yet.</div>');
        return;
      }

      let html = '<div class="d-flex justify-content-between align-items-center px-2 pb-1"><strong class="small">Notifications</strong>' +
        (unread > 0 ? `<button class="btn btn-sm btn-link p-0" id="markAllRead">Mark all read</button>` : '') + '</div>';

      items.forEach(n => {
        html += `
          <div class="notif-item">
            <div class="notif-ic" style="background:${colorFor[n.type] || '#6b7280'}"><i class="fa-solid ${iconFor[n.type] || 'fa-bell'}"></i></div>
            <div>
              <div class="small fw-600">${$('<div>').text(n.title).html()}</div>
              <div class="small text-muted">${$('<div>').text(n.message || '').html()}</div>
              <div class="small text-muted">${timeAgo(n.created_at)}</div>
            </div>
          </div>`;
      });
      html += `<div class="text-center pt-2 mt-1 border-top"><a href="${BASE_URL}modules/settings/notifications.php" class="small fw-600 text-decoration-none">View all notifications</a></div>`;
      $dropdown.html(html);
    });
  }

  $(document).on('click', '#markAllRead', function (e) {
    e.stopPropagation();
    $.post(BASE_URL + 'ajax/settings/notifications_mark_read.php', { csrf_token: window.CSRF_TOKEN }, function () {
      load();
    });
  });

  load();
  setInterval(load, 60000);
})(jQuery);
