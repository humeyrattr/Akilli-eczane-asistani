<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// İstatistikler
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalMeds  = $pdo->query("SELECT COUNT(*) FROM medications")->fetchColumn();
$totalDoses = $pdo->query("SELECT COUNT(*) FROM dose_logs")->fetchColumn();
$takenRate  = $pdo->query("SELECT ROUND(SUM(status='taken')/COUNT(*)*100,1) FROM dose_logs WHERE status!='pending'")->fetchColumn() ?? 0;

// Son kayıt olan kullanıcılar
$recentUsers = $pdo->query("SELECT id,name,email,created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Günlük doz istatistikleri (son 7 gün)
$dailyStats = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as c FROM dose_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day
")->fetchAll();

$pageTitle = 'Admin Panel';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
  <div><h1>Admin Panel</h1><p>Sistem yönetimi</p></div>
</div>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div><div class="stat-value"><?=$totalUsers?></div><div class="stat-label">Kullanıcı</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-pills"></i></div><div><div class="stat-value"><?=$totalMeds?></div><div class="stat-label">Toplam İlaç</div></div></div>
  <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-calendar-check"></i></div><div><div class="stat-value"><?=$totalDoses?></div><div class="stat-label">Toplam Doz</div></div></div>
  <div class="stat-card"><div class="stat-icon green"><i class="fas fa-percent"></i></div><div><div class="stat-value"><?=$takenRate?>%</div><div class="stat-label">Genel Uyum Oranı</div></div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Son Kayıt Olan Kullanıcılar</div><a href="<?=SITE_URL?>/admin/users.php" class="btn btn-sm btn-secondary">Tümü</a></div>
    <div class="table-wrap"><table>
      <thead><tr><th>Ad</th><th>E-posta</th><th>Kayıt</th></tr></thead>
      <tbody>
      <?php foreach($recentUsers as $u): ?>
      <tr><td><?=e($u['name'])?></td><td><?=e($u['email'])?></td><td><?=formatDate($u['created_at'])?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-link"></i> Hızlı Linkler</div></div>
    <div class="admin-nav">
      <a href="<?=SITE_URL?>/admin/users.php"><i class="fas fa-users"></i> Kullanıcı Yönetimi</a>
      <a href="<?=SITE_URL?>/admin/medications_db.php"><i class="fas fa-pills"></i> İlaç Veritabanı</a>
      <a href="<?=SITE_URL?>/admin/chatbot_manage.php"><i class="fas fa-robot"></i> Chatbot Yönetimi</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
