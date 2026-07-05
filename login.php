<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && verifyPassword($password, $user['password'])) {
            loginUser($user);
            generateTodayDoses();
            setFlash('success', 'Hoş geldiniz, ' . $user['name'] . '!');
            header('Location: ' . SITE_URL . '/dashboard.php');
            exit;
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
$pageTitle = 'Giriş Yap';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card animate-in">
    <div class="auth-logo">
      <i class="fas fa-pills"></i>
      <h1>Panacea Care</h1>
      <p>Hesabınıza giriş yapın</p>
    </div>
    <?php if ($error): ?>
    <div class="flash-message flash-error" style="position:static;margin-bottom:16px;">
      <i class="fas fa-exclamation-circle"></i><?=e($error)?>
    </div>
    <?php endif; ?>
    <form method="post" autocomplete="on">
      <div class="form-group">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" placeholder="ornek@email.com" value="<?=e($_POST['email']??'')?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">
        <i class="fas fa-sign-in-alt"></i> Giriş Yap
      </button>
    </form>
    <div class="auth-footer">
      Hesabınız yok mu? <a href="<?=SITE_URL?>/register.php">Kayıt Olun</a>
    </div>

  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
