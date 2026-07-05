<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$userId = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $dosage    = trim($_POST['dosage'] ?? '');
    $frequency = (int)($_POST['frequency'] ?? 1);
    $times     = $_POST['times'] ?? [];
    $start     = $_POST['start_date'] ?? '';
    $end       = $_POST['end_date'] ?? '';
    $notes     = trim($_POST['notes'] ?? '');
    $color     = $_POST['color'] ?? '#4f8ef7';

    if (!$name)     $errors[] = 'İlaç adı gereklidir.';
    if (!$dosage)   $errors[] = 'Doz bilgisi gereklidir.';
    if (!$start)    $errors[] = 'Başlangıç tarihi gereklidir.';
    if (!$times || count(array_filter($times)) < 1) $errors[] = 'En az bir kullanım saati girin.';

    if (!$errors) {
        $times = array_filter($times);
        sort($times);
        $stmt = $pdo->prepare("INSERT INTO medications (user_id,name,dosage,frequency,times,start_date,end_date,notes,color) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$userId, $name, $dosage, $frequency, json_encode(array_values($times)), $start, $end ?: null, $notes, $color]);
        generateTodayDoses();
        setFlash('success', '"' . $name . '" ilacı başarıyla eklendi!');
        header('Location: ' . SITE_URL . '/medications.php'); exit;
    }
}

$pageTitle = 'İlaç Ekle';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div><h1>İlaç Ekle</h1><p>Yeni ilaç bilgilerini girin</p></div>
  <a href="<?=SITE_URL?>/medications.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Geri</a>
</div>

<div style="max-width:640px;">
<div class="card">
<?php if ($errors): ?>
<div class="flash-message flash-error" style="position:static;margin-bottom:16px;flex-direction:column;align-items:flex-start;">
  <?php foreach($errors as $e): ?><div><i class="fas fa-exclamation-circle"></i> <?=e($e)?></div><?php endforeach; ?>
</div>
<?php endif; ?>
<form method="post" id="addMedForm">
  <div class="form-row">
    <div class="form-group" style="flex:2;">
      <label class="form-label">İlaç Adı *</label>
      <input type="text" name="name" class="form-control" placeholder="ör. Aspirin, Parol..." value="<?=e($_POST['name']??'')?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Renk</label>
      <div style="display:flex;gap:8px;align-items:center;">
        <input type="color" name="color" id="colorPicker" value="<?=e($_POST['color']??'#4f8ef7')?>" style="width:48px;height:42px;border:none;background:none;cursor:pointer;">
        <div id="colorPreview" style="width:28px;height:28px;border-radius:50%;background:<?=e($_POST['color']??'#4f8ef7')?>"></div>
      </div>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Doz Bilgisi *</label>
      <input type="text" name="dosage" class="form-control" placeholder="ör. 500mg, 1 tablet" value="<?=e($_POST['dosage']??'')?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Günlük Kullanım Sayısı *</label>
      <select name="frequency" id="freqSelect" class="form-control" onchange="updateTimePickers(this.value)">
        <?php for ($i=1;$i<=6;$i++): ?><option value="<?=$i?>" <?=($_POST['frequency']??1)==$i?'selected':''?>><?=$i?> kez/gün</option><?php endfor; ?>
      </select>
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">Kullanım Saatleri *</label>
    <div id="timePickers" style="display:flex;flex-wrap:wrap;gap:10px;">
      <!-- JS ile doldurulur -->
    </div>
    <div class="form-hint">Günlük kullanım sayısına göre saatler otomatik oluşturulur.</div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label class="form-label">Başlangıç Tarihi *</label>
      <input type="date" name="start_date" class="form-control" value="<?=e($_POST['start_date']??date('Y-m-d'))?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Bitiş Tarihi <span style="color:var(--text3)">(opsiyonel)</span></label>
      <input type="date" name="end_date" class="form-control" value="<?=e($_POST['end_date']??'')?>">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">Notlar</label>
    <textarea name="notes" class="form-control" placeholder="Yemekten önce alınız..." rows="3"><?=e($_POST['notes']??'')?></textarea>
  </div>

  <div style="display:flex;gap:12px;">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> İlacı Kaydet</button>
    <a href="<?=SITE_URL?>/medications.php" class="btn btn-secondary">İptal</a>
  </div>
</form>
</div>
</div>

<?php $savedTimes = json_encode($_POST['times'] ?? []); ?>
<?php $extraScripts = <<<JS
<script>
const savedTimes = $savedTimes;
function updateTimePickers(n) {
    const container = document.getElementById('timePickers');
    container.innerHTML = '';
    const defaults = ['08:00','12:00','18:00','22:00','06:00','15:00'];
    for (let i=0; i<n; i++) {
        const val = savedTimes[i] || defaults[i] || '08:00';
        container.innerHTML += `<input type="time" name="times[]" class="form-control" style="width:130px;" value="\${val}" required>`;
    }
}
updateTimePickers(document.getElementById('freqSelect').value);
document.getElementById('colorPicker').addEventListener('input', function(){
    document.getElementById('colorPreview').style.background = this.value;
});
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>
