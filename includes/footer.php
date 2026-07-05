<?php if (isLoggedIn()): ?>
</main><!-- /.main-content -->
<?php endif; ?>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?php if (isLoggedIn()): ?>
<script src="<?= SITE_URL ?>/assets/js/reminders.js"></script>
<script>
// CSRF token global
window.CSRF_TOKEN = '<?= $csrf ?>';
window.SITE_URL   = '<?= SITE_URL ?>';
window.USER_ID    = <?= $_SESSION['user_id'] ?? 'null' ?>;
</script>
<?php endif; ?>
<?= isset($extraScripts) ? $extraScripts : '' ?>

<script>
// ===== DARK / LIGHT MODE TOGGLE =====
(function(){
  var btn  = document.getElementById('themeToggle');
  var icon = document.getElementById('themeIcon');
  var html = document.documentElement;

  function applyTheme(theme){
    html.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    if(icon){
      icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }
  }

  // Set icon on load
  var saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);

  if(btn){
    btn.addEventListener('click', function(){
      var current = html.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }
})();
</script>
</body>
</html>
