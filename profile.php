<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$userId = $_SESSION['user_id'];
$user   = getCurrentUser();
$errors = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth = $_POST['birth_date'] ?? '';
    $newPass  = $_POST['new_password'] ?? '';
    $newPass2 = $_POST['new_password2'] ?? '';

    if (!$name) { $errors = 'Ad Soyad gereklidir.'; }
    else {
        $pdo->prepare("UPDATE users SET name=?,phone=?,birth_date=? WHERE id=?")->execute([$name,$phone,$birth?:null,$userId]);
        if ($newPass) {
            if (strlen($newPass) < 6) $errors = 'Şifre en az 6 karakter olmalıdır.';
            elseif ($newPass !== $newPass2) $errors = 'Şifreler eşleşmiyor.';
            else { $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([hashPassword($newPass),$userId]); }
        }
        if (!$errors) {
            $_SESSION['user_name'] = $name;
            $success = 'Profil güncellendi!';
            $user = getCurrentUser();
        }
    }
}

// Kısa istatistikler
$medCount = $pdo->prepare("SELECT COUNT(*) FROM medications WHERE user_id=? AND active=1"); $medCount->execute([$userId]); $medCount=$medCount->fetchColumn();
$takenCount=$pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND status='taken'"); $takenCount->execute([$userId]); $takenCount=$takenCount->fetchColumn();
$missedCount=$pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND status='missed'"); $missedCount->execute([$userId]); $missedCount=$missedCount->fetchColumn();

$pageTitle = 'Profilim';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header"><div><h1>Profilim</h1><p>Hesap bilgilerinizi yönetin</p></div></div>

<div class="grid-2">
<div>
  <!-- Profile Card -->
  <div class="card" style="margin-bottom:20px;text-align:center;">
    <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#7c3aed);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto 16px;">
      <?=strtoupper(substr($user['name'],0,1))?>
    </div>
    <h2 style="font-size:1.2rem;"><?=e($user['name'])?></h2>
    <p style="color:var(--text2);font-size:.85rem;"><?=e($user['email'])?></p>
    <span class="pill <?=$user['role']==='admin'?'pill-warning':'pill-info'?>" style="margin-top:8px;"><?=$user['role']==='admin'?'👑 Admin':'👤 Kullanıcı'?></span>
    <div style="margin-top:16px;font-size:.78rem;color:var(--text3);">Kayıt: <?=formatDate($user['created_at'])?></div>
  </div>

  <!-- Stats -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Özet İstatistikler</div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;text-align:center;">
      <div><div style="font-size:1.5rem;font-weight:700;color:var(--primary);"><?=$medCount?></div><div style="font-size:.75rem;color:var(--text2);">Aktif İlaç</div></div>
      <div><div style="font-size:1.5rem;font-weight:700;color:var(--success);"><?=$takenCount?></div><div style="font-size:.75rem;color:var(--text2);">Alınan Doz</div></div>
      <div><div style="font-size:1.5rem;font-weight:700;color:var(--danger);"><?=$missedCount?></div><div style="font-size:.75rem;color:var(--text2);">Kaçırılan</div></div>
    </div>
  </div>
</div>

<!-- Edit Form -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-pen"></i> Bilgileri Güncelle</div></div>
  <?php if ($errors): ?><div class="flash-message flash-error" style="position:static;margin-bottom:16px;"><i class="fas fa-exclamation-circle"></i><?=e($errors)?></div><?php endif; ?>
  <?php if ($success): ?><div class="flash-message flash-success" style="position:static;margin-bottom:16px;"><i class="fas fa-check-circle"></i><?=e($success)?></div><?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label class="form-label">Ad Soyad *</label>
      <input type="text" name="name" class="form-control" value="<?=e($user['name'])?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">E-posta <span style="color:var(--text3)">(değiştirilemez)</span></label>
      <input type="email" class="form-control" value="<?=e($user['email'])?>" disabled style="opacity:.5;">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Telefon</label>
        <input type="tel" name="phone" class="form-control" value="<?=e($user['phone']??'')?>">
      </div>
      <div class="form-group">
        <label class="form-label">Doğum Tarihi</label>
        <input type="date" name="birth_date" class="form-control" value="<?=e($user['birth_date']??'')?>">
      </div>
    </div>
    <hr style="border-color:var(--border);margin:20px 0;">
    <p style="font-size:.85rem;color:var(--text2);margin-bottom:14px;">Şifre değiştirmek için aşağıyı doldurun (boş bırakırsanız değişmez):</p>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Yeni Şifre</label>
        <input type="password" name="new_password" class="form-control" placeholder="En az 6 karakter">
      </div>
      <div class="form-group">
        <label class="form-label">Yeni Şifre Tekrar</label>
        <input type="password" name="new_password2" class="form-control" placeholder="Şifreyi tekrar girin">
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
  </form>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
