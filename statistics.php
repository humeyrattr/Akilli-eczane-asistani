<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$userId = $_SESSION['user_id'];
$period = $_GET['period'] ?? '30';

// Günlük uyum (seçilen periyot)
$daily = $pdo->prepare("
    SELECT scheduled_date,
           SUM(status='taken') as taken,
           SUM(status='missed') as missed,
           COUNT(*) as total
    FROM dose_logs WHERE user_id=? AND scheduled_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY scheduled_date ORDER BY scheduled_date
");
$daily->execute([$userId, $period]);
$dailyData = $daily->fetchAll();

// Genel istatistikler
$overall = $pdo->prepare("SELECT SUM(status='taken') as taken, SUM(status='missed') as missed, COUNT(*) as total FROM dose_logs WHERE user_id=? AND status!='pending'");
$overall->execute([$userId]);
$overall = $overall->fetch();

// İlaç bazlı uyum
$byMed = $pdo->prepare("
    SELECT m.name, m.color,
           SUM(dl.status='taken') as taken,
           SUM(dl.status='missed') as missed,
           COUNT(dl.id) as total
    FROM dose_logs dl JOIN medications m ON dl.medication_id=m.id
    WHERE dl.user_id=? AND dl.status!='pending' AND dl.scheduled_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY m.id ORDER BY taken DESC
");
$byMed->execute([$userId, $period]);
$byMedData = $byMed->fetchAll();

$pageTitle = 'İstatistikler';
include __DIR__ . '/includes/header.php';

// Build chart data
$labels=$takenArr=$missedArr=[];
foreach ($dailyData as $r) {
    $labels[]   = date('d/m', strtotime($r['scheduled_date']));
    $takenArr[] = $r['taken'];
    $missedArr[]= $r['missed'];
}

$totalTaken   = $overall['taken'] ?? 0;
$totalMissed  = $overall['missed'] ?? 0;
$totalTotal   = $overall['total'] ?? 0;
$compRate     = $totalTotal > 0 ? round(($totalTaken/$totalTotal)*100) : 0;
?>

<div class="page-header">
  <div><h1>İstatistikler</h1><p>İlaç uyum grafikleriniz</p></div>
  <div style="display:flex;gap:8px;">
    <a href="?period=7"  class="btn btn-sm <?=$period=='7'?'btn-primary':'btn-secondary'?>">7 Gün</a>
    <a href="?period=30" class="btn btn-sm <?=$period=='30'?'btn-primary':'btn-secondary'?>">30 Gün</a>
    <a href="?period=90" class="btn btn-sm <?=$period=='90'?'btn-primary':'btn-secondary'?>">90 Gün</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div><div class="stat-value"><?=$compRate?>%</div><div class="stat-label">Genel Uyum Oranı</div></div>
  </div>
  <div class="stat-card">
    <div><div class="stat-value"><?=$totalTaken?></div><div class="stat-label">Toplam Alınan Doz</div></div>
  </div>
  <div class="stat-card">
    <div><div class="stat-value"><?=$totalMissed?></div><div class="stat-label">Toplam Kaçırılan</div></div>
  </div>
  <div class="stat-card">
    <div><div class="stat-value"><?=$totalTotal?></div><div class="stat-label">Toplam Doz</div></div>
  </div>
</div>

<div class="grid-2" style="margin-bottom:20px;">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Günlük Doz Takibi</div></div>
    <canvas id="lineChart" height="250"></canvas>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Alınan / Kaçırılan</div></div>
    <div style="display:flex;align-items:center;justify-content:center;height:250px;">
      <canvas id="pieChart" style="max-height:220px;"></canvas>
    </div>
  </div>
</div>

<!-- Per-medication compliance -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-pills"></i> İlaç Bazlı Uyum</div></div>
  <?php if ($byMedData): ?>
  <?php foreach ($byMedData as $m): 
    $rate = $m['total'] > 0 ? round(($m['taken']/$m['total'])*100) : 0;
  ?>
  <div style="margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.875rem;">
      <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?=e($m['color'])?>;margin-right:6px;"></span><?=e($m['name'])?></span>
      <span style="color:var(--text2);"><?=$m['taken']?>/<?=$m['total']?> — <strong><?=$rate?>%</strong></span>
    </div>
    <div class="progress-bar">
      <div class="progress-fill" style="width:<?=$rate?>%;background:<?=e($m['color'])?>"></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php else: ?>
  <div class="empty-state"><i class="fas fa-chart-bar"></i><h3>Henüz veri yok</h3><p>İlaç kullanmaya başladıktan sonra grafikler burada görünecek.</p></div>
  <?php endif; ?>
</div>

<?php
$labelsJson  = json_encode($labels);
$takenJson   = json_encode($takenArr);
$missedJson  = json_encode($missedArr);
$extraScripts = <<<JS
<script>
Chart.defaults.color = '#8b949e';
Chart.defaults.font.family = 'Inter';
const gridColor = '#21262d';

new Chart(document.getElementById('lineChart'),{
    type:'line',
    data:{labels:$labelsJson,datasets:[
        {label:'Alınan',data:$takenJson,borderColor:'#3fb950',backgroundColor:'rgba(63,185,80,0.1)',tension:.3,fill:true,pointRadius:3},
        {label:'Kaçırılan',data:$missedJson,borderColor:'#f85149',backgroundColor:'rgba(248,81,73,0.1)',tension:.3,fill:true,pointRadius:3}
    ]},
    options:{responsive:true,plugins:{legend:{labels:{color:'#8b949e'}}},scales:{x:{grid:{color:gridColor},ticks:{color:'#8b949e',maxTicksLimit:10}},y:{grid:{color:gridColor},beginAtZero:true,ticks:{stepSize:1}}}}
});

new Chart(document.getElementById('pieChart'),{
    type:'doughnut',
    data:{labels:['Alınan','Kaçırılan'],datasets:[{data:[$totalTaken,$totalMissed],backgroundColor:['rgba(63,185,80,0.8)','rgba(248,81,73,0.8)'],borderColor:['#3fb950','#f85149'],borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},cutout:'65%'}
});
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>
