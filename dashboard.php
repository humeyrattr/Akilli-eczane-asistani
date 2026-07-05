<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
generateTodayDoses();
$userId = $_SESSION['user_id'];

// İstatistik verileri
$totalMeds   = $pdo->prepare("SELECT COUNT(*) FROM medications WHERE user_id=? AND active=1");
$totalMeds->execute([$userId]); $totalMeds = $totalMeds->fetchColumn();

$todayTaken  = $pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND scheduled_date=? AND status='taken'");
$todayTaken->execute([$userId, today()]); $todayTaken = $todayTaken->fetchColumn();

$todayMissed = $pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND scheduled_date=? AND status='missed'");
$todayMissed->execute([$userId, today()]); $todayMissed = $todayMissed->fetchColumn();

$todayPending= $pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND scheduled_date=? AND status='pending'");
$todayPending->execute([$userId, today()]); $todayPending = $todayPending->fetchColumn();

// Son 7 günlük uyum
$weekStats = $pdo->prepare("
    SELECT scheduled_date, 
           SUM(status='taken') as taken, 
           SUM(status='missed') as missed,
           SUM(status='pending') as pending
    FROM dose_logs 
    WHERE user_id=? AND scheduled_date >= DATE_SUB(CURDATE(),INTERVAL 6 DAY)
    GROUP BY scheduled_date ORDER BY scheduled_date
");
$weekStats->execute([$userId]);
$weekData = $weekStats->fetchAll();

// Bugünkü pending dozlar
$todayDoses = $pdo->prepare("
    SELECT dl.*, m.name as med_name, m.dosage, m.color
    FROM dose_logs dl JOIN medications m ON dl.medication_id=m.id
    WHERE dl.user_id=? AND dl.scheduled_date=? AND dl.status='pending'
    ORDER BY dl.scheduled_time
");
$todayDoses->execute([$userId, today()]);
$todayDoses = $todayDoses->fetchAll();

// Aktif ilaçlar
$activeMeds = $pdo->prepare("SELECT * FROM medications WHERE user_id=? AND active=1 ORDER BY name LIMIT 5");
$activeMeds->execute([$userId]);
$activeMeds = $activeMeds->fetchAll();

// Genel uyum oranı (son 30 gün)
$compRate = $pdo->prepare("SELECT SUM(status='taken') as t, COUNT(*) as total FROM dose_logs WHERE user_id=? AND scheduled_date >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND status != 'pending'");
$compRate->execute([$userId]);
$compRow = $compRate->fetch();
$complianceRate = $compRow['total'] > 0 ? round(($compRow['t'] / $compRow['total']) * 100) : 0;

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <p><?=date('d F Y, l', strtotime('now'))?> — Bugünün İlaç Özeti</p>
  </div>
  <a href="<?=SITE_URL?>/add_medication.php" class="btn btn-primary"><i class="fas fa-plus"></i> İlaç Ekle</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-pills"></i></div>
    <div><div class="stat-value"><?=$totalMeds?></div><div class="stat-label">Aktif İlaç</div></div>
  </div>
  <div class="stat-card">
    <div><div class="stat-value"><?=$todayTaken?></div><div class="stat-label">Bugün Alınan</div></div>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px;">
  <!-- Today's Doses -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-calendar-day"></i> Bugünkü Dozlar</div>
      <a href="<?=SITE_URL?>/dose_log.php" class="btn btn-sm btn-secondary">Tümü</a>
    </div>
    <?php if ($todayDoses): ?>
      <?php foreach ($todayDoses as $dose): ?>
      <div class="dose-item" id="dose_<?=$dose['id']?>">
        <div class="dose-time-badge"><?=substr($dose['scheduled_time'],0,5)?></div>
        <div class="dose-info">
          <div class="dose-name"><?=e($dose['med_name'])?></div>
          <div class="dose-dosage"><?=e($dose['dosage'])?></div>
        </div>
        <div class="dose-actions">
          <button class="btn-take" onclick="doseAction(<?=$dose['id']?>, 'taken')"><i class="fas fa-check"></i> Aldım</button>
          <button class="btn-skip" onclick="doseAction(<?=$dose['id']?>, 'missed')"><i class="fas fa-times"></i></button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state" style="padding:30px 10px;">
        <i class="fas fa-check-double"></i>
        <h3>Harika!</h3>
        <p>Bugün için bekleyen doz yok.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Compliance & Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-chart-bar"></i> Haftalık Uyum</div>
      <span class="pill pill-<?=$complianceRate>=80?'success':($complianceRate>=50?'warning':'danger')?>"><?=$complianceRate?>% Uyum</span>
    </div>
    <div class="progress-bar" style="margin-bottom:20px;">
      <div class="progress-fill" style="width:<?=$complianceRate?>%"></div>
    </div>
    <canvas id="weekChart" height="180"></canvas>
  </div>
</div>

<!-- Active Meds Quick View -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list-check"></i> Aktif İlaçlarım</div>
    <a href="<?=SITE_URL?>/medications.php" class="btn btn-sm btn-secondary">Tümünü Gör</a>
  </div>
  <?php if ($activeMeds): ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>İlaç Adı</th><th>Doz</th><th>Günlük Kullanım</th><th>Saatler</th></tr></thead>
    <tbody>
    <?php foreach ($activeMeds as $m): 
        $times = json_decode($m['times'], true) ?? [];
    ?>
    <tr>
      <td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?=e($m['color'])?>;margin-right:8px;"></span><?=e($m['name'])?></td>
      <td><?=e($m['dosage'])?></td>
      <td><?=$m['frequency']?> kez/gün</td>
      <td><?=implode(', ', array_map(fn($t)=>substr($t,0,5), $times))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <i class="fas fa-pills"></i>
    <h3>Henüz ilaç eklenmedi</h3>
    <p>İlk ilacınızı ekleyerek başlayın.</p>
    <a href="<?=SITE_URL?>/add_medication.php" class="btn btn-primary"><i class="fas fa-plus"></i> İlaç Ekle</a>
  </div>
  <?php endif; ?>
</div>

<?php
$labels = []; $takenArr = []; $missedArr = [];
$last7 = [];
for ($i=6;$i>=0;$i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $last7[$d] = ['taken'=>0,'missed'=>0];
}
foreach ($weekData as $row) $last7[$row['scheduled_date']] = $row;
foreach ($last7 as $date => $row) {
    $labels[]    = date('d/m', strtotime($date));
    $takenArr[]  = $row['taken'];
    $missedArr[] = $row['missed'];
}
$labelsJson  = json_encode($labels);
$takenJson   = json_encode($takenArr);
$missedJson  = json_encode($missedArr);
?>

<?php $extraScripts = <<<JS
<script>
const ctx = document.getElementById('weekChart');
if(ctx){
    new Chart(ctx,{
        type:'bar',
        data:{
            labels:$labelsJson,
            datasets:[
                {label:'Alınan',data:$takenJson,backgroundColor:'rgba(63,185,80,0.6)',borderColor:'#3fb950',borderWidth:1,borderRadius:6},
                {label:'Kaçırılan',data:$missedJson,backgroundColor:'rgba(248,81,73,0.6)',borderColor:'#f85149',borderWidth:1,borderRadius:6}
            ]
        },
        options:{responsive:true,plugins:{legend:{labels:{color:'#8b949e',font:{family:'Inter'}}}},scales:{x:{ticks:{color:'#8b949e'},grid:{color:'#21262d'}},y:{ticks:{color:'#8b949e',stepSize:1},grid:{color:'#21262d'},beginAtZero:true}}}
    });
}
async function doseAction(id, status) {
    const el = document.getElementById('dose_'+id);
    const res = await fetch(SITE_URL+'/api/dose_action.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update',id,status,csrf:CSRF_TOKEN})});
    const data = await res.json();
    if(data.success && el){ el.style.opacity='0'; el.style.transform='translateX(20px)'; setTimeout(()=>el.remove(),300); }
}
</script>
JS;
include __DIR__ . '/includes/footer.php';
?>
