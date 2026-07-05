<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/dashboard.php'); exit; }
$pageTitle = 'Ana Sayfa';
include __DIR__ . '/includes/header.php';
?>
<div class="landing">
<section class="hero">
  <div class="hero-content animate-in">
    <div class="hero-badge"><i class="fas fa-shield-heart"></i> Sağlığınız için akıllı çözüm</div>
    <h1>İlaçlarınızı Hiç<br>Unutmayın</h1>
    <p>Panacea Care ile ilaç kullanımınızı düzenli takip edin, zamanında hatırlatıcılar alın ve yapay zeka destekli sağlık asistanına danışın.</p>
    <div class="hero-btns">
      <a href="<?=SITE_URL?>/register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Hemen Başla</a>
      <a href="<?=SITE_URL?>/login.php"    class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
    </div>
  </div>
</section>

<div class="features">
  <div class="feature-card animate-in">
    <div class="feature-icon">💊</div>
    <h3>İlaç Takibi</h3>
    <p>İlaçlarınızı, dozlarınızı ve kullanım saatlerinizi kolayca yönetin.</p>
  </div>
  <div class="feature-card animate-in">
    <div class="feature-icon">🔔</div>
    <h3>Hatırlatıcılar</h3>
    <p>Belirlediğiniz saatlerde otomatik bildirimler alın, hiçbir dozu kaçırmayın.</p>
  </div>
  <div class="feature-card animate-in">
    <div class="feature-icon">🤖</div>
    <h3>AI Sağlık Asistanı</h3>
    <p>İlaçlar ve genel sağlık konularında 7/24 yapay zeka asistanına danışın.</p>
  </div>
  <div class="feature-card animate-in">
    <div class="feature-icon">📊</div>
    <h3>İstatistikler</h3>
    <p>Doz uyum oranlarınızı grafiklerle takip edin, alışkanlıklarınızı görün.</p>
  </div>
</div>

<div style="text-align:center;padding:40px 20px;color:var(--text3);font-size:.8rem;border-top:1px solid var(--border);">
  &copy; <?=date('Y')?> Panacea Care — Hümeyra Tatar — Bitirme Projesi
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
