<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }

$errors = [];
$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2= $_POST['password2'] ?? '';

    if (!$name)              $errors[] = 'Ad Soyad gereklidir.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
    if (strlen($password) < 6) $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    if ($password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';

    if (!$errors) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Bu e-posta zaten kayıtlı.';
        } else {
            $ins = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'user')");
            $ins->execute([$name, $email, hashPassword($password)]);
            $userId = $pdo->lastInsertId();
            $user   = $pdo->query("SELECT * FROM users WHERE id=$userId")->fetch();
            loginUser($user);
            generateTodayDoses();
            setFlash('success', 'Hesabınız oluşturuldu! Hoş geldiniz!');
            header('Location: ' . SITE_URL . '/dashboard.php');
            exit;
        }
    }
}
$pageTitle = 'Kayıt Ol';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card animate-in">
    <div class="auth-logo">
      <i class="fas fa-pills"></i>
      <h1>Panacea Care</h1>
      <p>Yeni hesap oluşturun</p>
    </div>
    <?php if ($errors): ?>
    <div class="flash-message flash-error" style="position:static;margin-bottom:16px;flex-direction:column;align-items:flex-start;">
      <?php foreach($errors as $err): ?><div><i class="fas fa-exclamation-circle"></i> <?=e($err)?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form method="post">
      <div class="form-group">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="name" class="form-control" placeholder="Adınız Soyadınız" value="<?=e($name)?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?=e($email)?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Şifre</label>
          <input type="password" name="password" class="form-control" placeholder="En az 6 karakter" required>
        </div>
        <div class="form-group">
          <label class="form-label">Şifre Tekrar</label>
          <input type="password" name="password2" class="form-control" placeholder="Şifrenizi tekrar girin" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
        <i class="fas fa-user-plus"></i> Kayıt Ol
      </button>
    </form>
    <div class="auth-footer">
      Zaten hesabınız var mı? <a href="<?=SITE_URL?>/login.php">Giriş Yapın</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
