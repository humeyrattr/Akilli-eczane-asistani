<?php
require_once __DIR__ . '/auth.php';
$flash = getFlash();
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' – ' . SITE_NAME : SITE_NAME . ' | Akıllı İlaç Takip Sistemi' ?></title>
    <meta name="description" content="Panacea Care ile ilaçlarınızı düzenli takip edin, doz hatırlatıcıları alın ve sağlık asistanınıza danışın.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Theme init (before render to avoid flash) -->
    <script>
      (function(){
        var t = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', t);
      })();
    </script>
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-pills"></i>
            <span>Panacea Care</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>



    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/medications.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['medications.php', 'add_medication.php', 'edit_medication.php']) ? 'active' : '' ?>">
                <i class="fas fa-pills"></i>
                <span>İlaçlarım</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/dose_log.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dose_log.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Doz Takibi</span>
                <span class="badge" id="pendingBadge" style="display:none;"></span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/statistics.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'statistics.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>İstatistikler</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/chatbot.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'chatbot.php' ? 'active' : '' ?>">
                <i class="fas fa-robot"></i>
                <span>Sağlık Asistanı</span>
            </a>
        </li>

        <?php if (isAdmin()): ?>
        <li class="nav-separator"><span>Admin</span></li>
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/admin/index.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">
                <i class="fas fa-shield-halved"></i>
                <span>Admin Panel</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>


</nav>

<!-- Top Bar -->
<header class="topbar">
    <button class="topbar-toggle" id="topbarToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-title"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></div>
    <div class="topbar-actions">
        <button class="theme-toggle" id="themeToggle" title="Tema değiştir">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <div class="notif-wrapper">
            <button class="notif-btn" id="notifBtn" title="Bildirimler">
                <i class="fas fa-bell"></i>
                <span class="notif-dot" id="notifDot" style="display:none;"></span>
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <span>Bildirimler</span>
                    <button class="notif-clear" onclick="location.href='dose_log.php'">Hepsini Gör</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Henüz bildirim yok.</div>
                </div>
            </div>
        </div>
        <div class="user-wrapper" style="position:relative;">
            <button class="topbar-user-btn" id="userBtn" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;padding:4px;border-radius:8px;transition:var(--transition);">
                <div class="user-avatar-sm" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg, var(--primary), var(--primary-dark));color:#EFF0E1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;border:2px solid rgba(239,240,225,0.2);">
                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                </div>
                <span class="topbar-user" style="font-weight:500;color:var(--text2);"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                <i class="fas fa-chevron-down" style="font-size:0.7rem;color:var(--text3);"></i>
            </button>
            <div class="notif-dropdown" id="userDropdown" style="width:200px;">
                <div class="notif-header" style="flex-direction:column;align-items:flex-start;padding:12px 16px;">
                    <div style="font-size:0.9rem;color:var(--text);"><?= e($currentUser['name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--text2);font-weight:normal;"><?= $currentUser['role'] === 'admin' ? 'Admin' : 'Kullanıcı' ?></div>
                </div>
                <div class="notif-list" style="padding:4px 0;">
                    <a href="<?= SITE_URL ?>/profile.php" class="notif-item" style="text-decoration:none;color:var(--text);padding:10px 16px;border-bottom:none;">
                        <i class="fas fa-user-circle" style="color:var(--primary);width:20px;text-align:center;"></i> Profilim
                    </a>
                    <div style="border-top:1px solid var(--border);margin:4px 0;"></div>
                    <a href="<?= SITE_URL ?>/logout.php" class="notif-item" style="text-decoration:none;color:var(--danger);padding:10px 16px;border-bottom:none;">
                        <i class="fas fa-sign-out-alt" style="width:20px;text-align:center;"></i> Çıkış Yap
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content Wrapper -->
<main class="main-content" id="mainContent">
<?php else: ?>
<!-- Guest: Minimal nav -->
<nav class="guest-nav">
    <div class="logo">
        <i class="fas fa-pills"></i>
        <span>Panacea Care</span>
    </div>
    <div class="guest-nav-links" style="display:flex;align-items:center;gap:12px;">
        <button class="theme-toggle" id="themeToggle" title="Tema değiştir">
            <i class="fas fa-moon" id="themeIcon"></i>
        </button>
        <a href="<?= SITE_URL ?>/login.php">Giriş Yap</a>
        <a href="<?= SITE_URL ?>/register.php" class="btn-primary-sm">Kayıt Ol</a>
    </div>
</nav>
<?php endif; ?>

<?php if ($flash): ?>
<div class="flash-message flash-<?= e($flash['type']) ?>" id="flashMessage">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
    <?= e($flash['message']) ?>
    <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>
