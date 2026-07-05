<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
generateTodayDoses();
$userId = $_SESSION['user_id'];

$selectedDate = $_GET['date'] ?? today();
$filterStatus = $_GET['status'] ?? 'all';

$sql = "SELECT dl.*, m.name as med_name, m.dosage, m.color FROM dose_logs dl 
        JOIN medications m ON dl.medication_id=m.id
        WHERE dl.user_id=? AND dl.scheduled_date=?";
if ($filterStatus !== 'all') $sql .= " AND dl.status='{$filterStatus}'";
$sql .= " ORDER BY dl.scheduled_time";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, $selectedDate]);
$doses = $stmt->fetchAll();

// Summary
$summary = $pdo->prepare("SELECT status, COUNT(*) as c FROM dose_logs WHERE user_id=? AND scheduled_date=? GROUP BY status");
$summary->execute([$userId, $selectedDate]);
$summaryData = ['taken'=>0,'missed'=>0,'pending'=>0];
foreach ($summary->fetchAll() as $r) $summaryData[$r['status']] = $r['c'];

$pageTitle = 'Doz Takibi';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div><h1>Doz Takibi</h1><p>Günlük ilaç alma durumunuzu yönetin</p></div>
</div>

<!-- Date + Filter -->
<div style="display:flex;gap:12px;align-items:center;margin-bottom:20px;flex-wrap:wrap;">
  <input type="date" id="dateSelect" class="form-control" style="width:180px;" value="<?=e($selectedDate)?>" onchange="window.location='?date='+this.value+'&status=<?=e($filterStatus)?>'">
  <div style="display:flex;gap:8px;">
    <a href="?date=<?=e($selectedDate)?>&status=all"     class="btn btn-sm <?=$filterStatus==='all'?'btn-primary':'btn-secondary'?>">Tümü</a>
    <a href="?date=<?=e($selectedDate)?>&status=pending" class="btn btn-sm <?=$filterStatus==='pending'?'btn-primary':'btn-secondary'?>">Bekleyen <span class="pill pill-warning" style="margin-left:4px;"><?=$summaryData['pending']?></span></a>
    <a href="?date=<?=e($selectedDate)?>&status=taken"   class="btn btn-sm <?=$filterStatus==='taken'?'btn-success':'btn-secondary'?>">Alınan</a>
    <a href="?date=<?=e($selectedDate)?>&status=missed"  class="btn btn-sm <?=$filterStatus==='missed'?'btn-danger':'btn-secondary'?>">Kaçırılan</a>
  </div>
</div>

<!-- Summary Pills -->
<div style="display:flex;gap:10px;margin-bottom:20px;">
  <span class="pill pill-success"><i class="fas fa-check"></i> <?=$summaryData['taken']?> Alınan</span>
  <span class="pill pill-danger"><i class="fas fa-times"></i> <?=$summaryData['missed']?> Kaçırılan</span>
  <span class="pill pill-warning"><i class="fas fa-clock"></i> <?=$summaryData['pending']?> Bekliyor</span>
</div>

<!-- Doses -->
<?php if ($doses): ?>
<div id="doseList">
<?php foreach ($doses as $d): ?>
<div class="dose-item" id="dose_<?=$d['id']?>" style="border-left:3px solid <?=e($d['color'])?>;">
  <div class="dose-time-badge"><?=substr($d['scheduled_time'],0,5)?></div>
  <div class="dose-info">
    <div class="dose-name"><?=e($d['med_name'])?></div>
    <div class="dose-dosage"><?=e($d['dosage'])?></div>
    <?php if ($d['taken_at']): ?><div style="font-size:.72rem;color:var(--success);margin-top:2px;"><i class="fas fa-check-circle"></i> <?=formatDateTime($d['taken_at'])?> alındı</div><?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:10px;">
    <?php if ($d['status'] === 'pending'): ?>
      <button class="btn-take" onclick="doseAction(<?=$d['id']?>, 'taken')"><i class="fas fa-check"></i> Aldım</button>
      <button class="btn-skip" onclick="doseAction(<?=$d['id']?>, 'missed')"><i class="fas fa-times"></i> Atla</button>
    <?php elseif ($d['status'] === 'taken'): ?>
      <span class="pill pill-success"><i class="fas fa-check-circle"></i> Alındı</span>
      <button onclick="doseAction(<?=$d['id']?>, 'pending')" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></button>
    <?php else: ?>
      <span class="pill pill-danger"><i class="fas fa-times-circle"></i> Kaçırıldı</span>
      <button onclick="doseAction(<?=$d['id']?>, 'taken')" class="btn btn-sm btn-secondary"><i class="fas fa-undo"></i></button>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="card">
  <div class="empty-state">
    <i class="fas fa-calendar-xmark"></i>
    <h3>Bu tarih için doz kaydı yok</h3>
    <p>Aktif ilaçlarınız varsa ve bu bugün ise sayfa yenilemeyi deneyin.</p>
  </div>
</div>
<?php endif; ?>

<?php $extraScripts = <<<JS
<script>
async function doseAction(id, status) {
    const el = document.getElementById('dose_'+id);
    const res = await fetch(SITE_URL+'/api/dose_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update',id,status,csrf:CSRF_TOKEN})});
    const data = await res.json();
    if(data.success){ location.reload(); }
}
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>
