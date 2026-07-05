<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$userId = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$med = $pdo->prepare("SELECT * FROM medications WHERE id=? AND user_id=?");
$med->execute([$id, $userId]);
$med = $med->fetch();
if (!$med) { setFlash('error','İlaç bulunamadı.'); header('Location:'.SITE_URL.'/medications.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $dosage    = trim($_POST['dosage'] ?? '');
    $frequency = (int)($_POST['frequency'] ?? 1);
    $times     = array_filter($_POST['times'] ?? []);
    $start     = $_POST['start_date'] ?? '';
    $end       = $_POST['end_date'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');
    $color     = $_POST['color'] ?? '#4f8ef7';

    if (!$name || !$dosage || !$start || !$times) $errors[] = 'Lütfen zorunlu alanları doldurun.';

    if (!$errors) {
        sort($times);
        $stmt = $pdo->prepare("UPDATE medications SET name=?,dosage=?,frequency=?,times=?,start_date=?,end_date=?,notes=?,color=? WHERE id=? AND user_id=?");
        $stmt->execute([$name,$dosage,$frequency,json_encode(array_values($times)),$start,$end?:null,$notes,$color,$id,$userId]);
        generateTodayDoses();
        setFlash('success','İlaç güncellendi!');
        header('Location:'.SITE_URL.'/medications.php'); exit;
    }
}

$medTimes = json_decode($med['times'], true) ?? [];
$pageTitle = 'İlaç Düzenle';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div><h1>İlaç Düzenle</h1><p><?=e($med['name'])?> bilgilerini güncelleyin</p></div>
  <a href="<?=SITE_URL?>/medications.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Geri</a>
</div>
<div style="max-width:640px;"><div class="card">
<?php if ($errors): ?>
<div class="flash-message flash-error" style="position:static;margin-bottom:16px;">
  <?php foreach($errors as $e): ?><div><?=e($e)?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<form method="post">
  <div class="form-row">
    <div class="form-group" style="flex:2;">
      <label class="form-label">İlaç Adı *</label>
      <input type="text" name="name" class="form-control" value="<?=e($med['name'])?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Renk</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input type="color" name="color" id="colorPicker" value="<?=e($med['color'])?>" style="width:48px;height:42px;border:none;background:none;cursor:pointer;">
        <div id="colorPreview" style="width:28px;height:28px;border-radius:50%;background:<?=e($med['color'])?>"></div>
      </div>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Doz *</label>
      <input type="text" name="dosage" class="form-control" value="<?=e($med['dosage'])?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Günlük Kullanım</label>
      <select name="frequency" id="freqSelect" class="form-control" onchange="updateTimePickers(this.value)">
        <?php for ($i=1;$i<=6;$i++): ?><option value="<?=$i?>" <?=$med['frequency']==$i?'selected':''?>><?=$i?> kez/gün</option><?php endfor; ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label class="form-label">Kullanım Saatleri *</label>
    <div id="timePickers" style="display:flex;flex-wrap:wrap;gap:10px;"></div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Başlangıç Tarihi *</label>
      <input type="date" name="start_date" class="form-control" value="<?=e($med['start_date'])?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Bitiş Tarihi</label>
      <input type="date" name="end_date" class="form-control" value="<?=e($med['end_date']??'')?>">
    </div>
  </div>
  <div class="form-group">
    <label class="form-label">Notlar</label>
    <textarea name="notes" class="form-control" rows="3"><?=e($med['notes']??'')?></textarea>
  </div>
  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Güncelle</button>
    <a href="<?=SITE_URL?>/medications.php" class="btn btn-secondary">İptal</a>
  </div>
</form>
</div></div>
<?php $timesJson = json_encode($medTimes); ?>
<?php $extraScripts = <<<JS
<script>
const existingTimes = $timesJson;
function updateTimePickers(n) {
    const container = document.getElementById('timePickers');
    container.innerHTML = '';
    const defaults = ['08:00','12:00','18:00','22:00','06:00','15:00'];
    for (let i=0; i<n; i++) {
        const val = existingTimes[i] || defaults[i] || '08:00';
        container.innerHTML += `<input type="time" name="times[]" class="form-control" style="width:130px;" value="\${val}" required>`;
    }
}
updateTimePickers(document.getElementById('freqSelect').value);
document.getElementById('colorPicker').addEventListener('input',function(){document.getElementById('colorPreview').style.background=this.value;});
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>
