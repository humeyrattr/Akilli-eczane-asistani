<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$userId = $_SESSION['user_id'];

// Silme işlemi
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM medications WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['delete'], $userId]);
    setFlash('success', 'İlaç silindi.');
    header('Location: ' . SITE_URL . '/medications.php'); exit;
}
// Toggle aktif
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE medications SET active = NOT active WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['toggle'], $userId]);
    header('Location: ' . SITE_URL . '/medications.php'); exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM medications WHERE user_id=?";
if ($filter === 'active')   $sql .= " AND active=1";
if ($filter === 'inactive') $sql .= " AND active=0";
$sql .= " ORDER BY active DESC, name";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$meds = $stmt->fetchAll();

$pageTitle = 'İlaçlarım';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>İlaçlarım</h1>
    <p>Toplam <?=count($meds)?> ilaç kayıtlı</p>
  </div>
  <a href="<?=SITE_URL?>/add_medication.php" class="btn btn-primary"><i class="fas fa-plus"></i> İlaç Ekle</a>
</div>

<!-- Filter -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
  <a href="?filter=all"      class="btn btn-sm <?=$filter==='all'?'btn-primary':'btn-secondary'?>">Tümü</a>
  <a href="?filter=active"   class="btn btn-sm <?=$filter==='active'?'btn-primary':'btn-secondary'?>">Aktif</a>
  <a href="?filter=inactive" class="btn btn-sm <?=$filter==='inactive'?'btn-primary':'btn-secondary'?>">Pasif</a>
</div>

<?php if ($meds): ?>
<div class="med-grid">
<?php foreach ($meds as $m):
    $times = json_decode($m['times'], true) ?? [];
    $isActive = (bool)$m['active'];
    $endSoon = $m['end_date'] && strtotime($m['end_date']) <= strtotime('+3 days');
?>
<div class="med-card" style="--med-color:<?=e($m['color'])?>;<?=!$isActive?'opacity:.6;':''?>">
  <div class="med-card-header">
    <div>
      <div class="med-name"><?=e($m['name'])?></div>
      <div class="med-dosage"><?=e($m['dosage'])?></div>
    </div>
    <span class="pill <?=$isActive?'pill-success':'pill-gray'?>"><?=$isActive?'Aktif':'Pasif'?></span>
  </div>
  <div class="med-meta">
    <span class="pill pill-info"><i class="fas fa-repeat"></i> Günde <?=$m['frequency']?> kez</span>
    <?php if ($m['start_date']): ?><span class="pill pill-gray"><i class="fas fa-calendar"></i> <?=formatDate($m['start_date'])?></span><?php endif; ?>
    <?php if ($m['end_date']): ?><span class="pill <?=$endSoon?'pill-warning':'pill-gray'?>"><i class="fas fa-calendar-xmark"></i> <?=formatDate($m['end_date'])?></span><?php endif; ?>
  </div>
  <?php if ($times): ?>
  <div class="med-times">
    <?php foreach ($times as $t): ?><span class="time-chip"><i class="fas fa-clock"></i> <?=substr($t,0,5)?></span><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($m['notes']): ?><p style="font-size:.78rem;color:var(--text2);margin-top:10px;"><?=e($m['notes'])?></p><?php endif; ?>
  <div class="med-actions">
    <a href="<?=SITE_URL?>/edit_medication.php?id=<?=$m['id']?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> Düzenle</a>
    <a href="?toggle=<?=$m['id']?>" class="btn btn-sm btn-secondary"><i class="fas fa-<?=$isActive?'pause':'play'?>"></i></a>
    <a href="?delete=<?=$m['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu ilacı silmek istediğinizden emin misiniz?')"><i class="fas fa-trash"></i></a>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card">
  <div class="empty-state">
    <i class="fas fa-pills"></i>
    <h3>Henüz ilaç eklenmedi</h3>
    <p>İlaçlarınızı sisteme ekleyerek takip etmeye başlayın.</p>
    <a href="<?=SITE_URL?>/add_medication.php" class="btn btn-primary"><i class="fas fa-plus"></i> İlk İlacı Ekle</a>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
