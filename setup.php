<?php
/**
 * MedTrack - Veritabanı Kurulum Sayfası
 * Kullanım: http://localhost/bitirme-projesi/setup.php
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbName = 'ilac_takip';

$errors  = [];
$success = [];

try {
    // 1. Adım: DB olmadan bağlan, veritabanını oluştur
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $success[] = '✅ Veritabanı oluşturuldu / zaten mevcut.';

    // 2. Adım: Şimdi veritabanını seçerek yeniden bağlan
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 3. Adım: Tabloları oluştur
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user','admin') DEFAULT 'user',
            avatar VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'medications' => "CREATE TABLE IF NOT EXISTS medications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(200) NOT NULL,
            dosage VARCHAR(100) NOT NULL,
            frequency INT DEFAULT 1,
            times JSON DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            color VARCHAR(7) DEFAULT '#4f8ef7',
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'dose_logs' => "CREATE TABLE IF NOT EXISTS dose_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medication_id INT NOT NULL,
            user_id INT NOT NULL,
            scheduled_date DATE NOT NULL,
            scheduled_time TIME NOT NULL,
            taken_at DATETIME DEFAULT NULL,
            status ENUM('pending','taken','missed') DEFAULT 'pending',
            notes VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'med_database' => "CREATE TABLE IF NOT EXISTS med_database (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            generic_name VARCHAR(200) DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            usage_info TEXT DEFAULT NULL,
            side_effects TEXT DEFAULT NULL,
            warnings TEXT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'chatbot_qa' => "CREATE TABLE IF NOT EXISTS chatbot_qa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            keywords VARCHAR(500) NOT NULL,
            question VARCHAR(500) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(100) DEFAULT 'genel',
            is_active TINYINT(1) DEFAULT 1,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $tname => $tsql) {
        $pdo->exec($tsql);
        $success[] = "✅ Tablo hazır: <strong>$tname</strong>";
    }

    // İndexler (hata olsa bile devam et)
    $indexes = [
        "CREATE INDEX idx_medications_user ON medications(user_id, active)",
        "CREATE INDEX idx_dose_logs_user_date ON dose_logs(user_id, scheduled_date)",
        "CREATE INDEX idx_dose_logs_medication ON dose_logs(medication_id, scheduled_date)",
    ];
    foreach ($indexes as $idx) {
        try { $pdo->exec($idx); } catch (PDOException $e) { /* zaten varsa atla */ }
    }
    $success[] = '✅ İndexler oluşturuldu.';

    // 4. Adım: Admin kullanıcı (yoksa ekle)
    $adminHash = '$2y$10$bJTg.HHbKmEnz6vcRlcrueR.h3uyPTytau27G8Zb4uk.ZhMvPOvGq'; // admin123
    $userHash  = '$2y$10$jmeZb3J3IIzBRLMipbjCDek4E/Os6Ry53HQJ0w7JsuiyO7P7eNSJa'; // test123

    $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $checkAdmin->execute(['admin@ilactakip.com']);
    if (!$checkAdmin->fetch()) {
        $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)")
            ->execute(['Admin','admin@ilactakip.com',$adminHash,'admin']);
        $success[] = '✅ Admin kullanıcısı oluşturuldu.';
    } else {
        $success[] = '⚠️ Admin zaten mevcut, atlandı.';
    }

    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $checkUser->execute(['humeyra@test.com']);
    if (!$checkUser->fetch()) {
        $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)")
            ->execute(['Hümeyra Tatar','humeyra@test.com',$userHash,'user']);
        $success[] = '✅ Test kullanıcısı oluşturuldu.';
    } else {
        $success[] = '⚠️ Test kullanıcısı zaten mevcut, atlandı.';
    }

    // 5. Adım: Örnek veriler
    $medCount = $pdo->query("SELECT COUNT(*) FROM med_database")->fetchColumn();
    if ($medCount == 0) {
        $meds = [
            ['Aspirin','Asetilsalisilik Asit','Ağrı Kesici','Ağrı ve ateş için kullanılan yaygın bir ilaçtır.','Yemekten sonra bol su ile alınız. Günde 1-3 tablet.','Mide rahatsızlığı, bulantı, kanamalara yatkınlık.'],
            ['Parol','Parasetamol','Ağrı Kesici / Ateş Düşürücü','Hafif-orta şiddette ağrılar ve ateş için kullanılır.','Günde en fazla 4 kez, her seferinde 1-2 tablet.','Çok yüksek dozlarda karaciğer hasarı riski.'],
            ['Augmentin','Amoksisilin/Klavulanik Asit','Antibiyotik','Bakteriyel enfeksiyonlar için geniş spektrumlu antibiyotik.','Yemekle birlikte alınız. Tedavi süresini tamamlayın.','Diyare, bulantı, alerjik reaksiyonlar.'],
            ['Vitamax','Multivitamin','Vitamin/Mineral','Günlük vitamin ve mineral ihtiyacını karşılar.','Günde 1 tablet kahvaltıyla alınız.','Genellikle iyi tolere edilir.'],
            ['Lansoprazol','Lansoprazol','Mide Asidi','Mide asidini azaltan proton pompası inhibitörü.','Yemekten 30 dakika önce alınır.','Baş ağrısı, ishal, mide bulantısı.'],
        ];
        $ins = $pdo->prepare("INSERT INTO med_database(name,generic_name,category,description,usage_info,side_effects) VALUES(?,?,?,?,?,?)");
        foreach ($meds as $m) $ins->execute($m);
        $success[] = '✅ Örnek ilaç veritabanı eklendi.';
    } else {
        $success[] = '⚠️ İlaç veritabanı zaten mevcut, atlandı.';
    }

    // 6. Adım: Chatbot Q&A
    $qaCount = $pdo->query("SELECT COUNT(*) FROM chatbot_qa")->fetchColumn();
    if ($qaCount == 0) {
        $qas = [
            ['merhaba,selam,hey','Merhaba!','Merhaba! Ben sizin sağlık asistanınızım. 🏥 İlaçlarınız veya genel sağlık konularında sorularınızı yanıtlamaya hazırım!','genel'],
            ['aspirin,ağrı kesici,baş ağrısı','Aspirin nedir?','Aspirin (Asetilsalisilik Asit), ağrı kesici ve ateş düşürücü özelliklere sahip bir ilaçtır. Mide problemleri olan kişiler dikkatli kullanmalıdır. 💊','ilaç'],
            ['parasetamol,parol,ateş,ateş düşürücü','Parasetamol nedir?','Parasetamol, hafif-orta şiddette ağrılar ve ateş için güvenli bir ilaçtır. Günde 4 defayı aşmamak kaydıyla kullanılabilir. 🌡️','ilaç'],
            ['antibiyotik,antibiyotik kullanımı','Antibiyotik kullanımı','Antibiyotikler yalnızca bakteriyel enfeksiyonlarda kullanılmalıdır. ⚠️ Tedavi süresini tam tamamlayın, bırakmayın!','ilaç'],
            ['ilaç atladım,doz atladım,ilacı unuttum','İlaç dozunu atladım','Bir sonraki doza çok az zaman kaldıysa atladığınız dozu geçin. Telafi için çift doz almayın. 🔔','takip'],
            ['su,günlük su,su içmek','Günlük su tüketimi','Yetişkinler için önerilen günlük su tüketimi 2-2.5 litredir. Çoğu ilaç bol su ile alınmalıdır. 💧','genel sağlık'],
            ['vitamin,vitamin eksikliği','Vitamin takviyesi','Bilinçsiz takviye kullanımı zararlı olabilir. Doktorunuza danışarak kullanmanız önerilir. 🥗','genel sağlık'],
            ['tansiyon,yüksek tansiyon,hipertansiyon','Yüksek tansiyon','Normal kan basıncı 120/80 mmHg altındadır. Tansiyon ilaçlarınızı düzenli alın! 💓','hastalık'],
            ['diyabet,şeker hastalığı,insülin','Diyabet','Diyabet ilaçlarınızı düzenli almanız hayati önem taşır! 🩺','hastalık'],
            ['ilaç etkileşimi,ilaç kombinasyonu','İlaç etkileşimi','⚠️ Kullandığınız tüm ilaçları doktorunuza ve eczacınıza bildirin.','güvenlik'],
            ['teşekkür,sağ ol,tamam','Teşekkürler','Ne olur! 😊 Sağlıklı günler dilerim!','genel'],
            ['yardım,ne yapabilirim,ne sorabilirim','Yardım','İlaç bilgileri, sağlık konuları ve doz tavsiyeleri için sorularınızı yazın! 🤖','genel'],
        ];
        $ins = $pdo->prepare("INSERT INTO chatbot_qa(keywords,question,answer,category) VALUES(?,?,?,?)");
        foreach ($qas as $q) $ins->execute($q);
        $success[] = '✅ Chatbot Q&A verileri eklendi.';
    } else {
        $success[] = '⚠️ Chatbot verileri zaten mevcut, atlandı.';
    }

    $allGood = empty($errors);

} catch (PDOException $e) {
    $connectError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>MedTrack Kurulum</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Inter,sans-serif;background:#0d1117;color:#e6edf3;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px;}
.box{background:#161b22;border:1px solid #30363d;border-radius:12px;padding:40px;max-width:660px;width:100%;}
h1{font-size:1.5rem;margin-bottom:6px;}
.sub{color:#8b949e;font-size:.9rem;margin-bottom:24px;}
.log{background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:16px;font-size:.82rem;line-height:2.2;max-height:300px;overflow-y:auto;}
.success-box{background:rgba(63,185,80,0.1);border:1px solid #3fb950;border-radius:8px;padding:24px;text-align:center;margin-bottom:20px;}
.error-box{background:rgba(248,81,73,0.1);border:1px solid #f85149;border-radius:8px;padding:20px;margin-bottom:20px;}
a.btn{display:inline-flex;align-items:center;gap:8px;background:#4f8ef7;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:16px;font-size:.95rem;}
a.btn:hover{background:#3a7bd5;}
.creds{margin-top:14px;background:#0d1117;border-radius:8px;padding:14px;font-size:.82rem;color:#8b949e;line-height:2;}
.warn{margin-top:14px;font-size:.78rem;color:#f85149;}
</style>
</head>
<body>
<div class="box">
  <h1>🏥 MedTrack Kurulum</h1>
  <p class="sub">Veritabanı otomatik kurulum</p>

  <?php if (isset($connectError)): ?>
  <div class="error-box">
    <strong>❌ MySQL Bağlantı Hatası!</strong><br><br>
    <code><?= htmlspecialchars($connectError) ?></code><br><br>
    <strong>Çözüm:</strong> XAMPP Control Panel'de <strong>MySQL</strong> servisini başlatın, sonra bu sayfayı yenileyin.
  </div>

  <?php elseif (!empty($errors)): ?>
  <div class="error-box">
    <strong>⚠️ Hatalar oluştu:</strong>
    <div class="log" style="margin-top:10px;"><?= implode('<br>', $errors) ?></div>
  </div>
  <div class="log"><?= implode('<br>', $success) ?></div>

  <?php else: ?>
  <div class="success-box">
    <div style="font-size:3rem;margin-bottom:10px;">🎉</div>
    <strong style="font-size:1.15rem;color:#3fb950;">Kurulum Başarıyla Tamamlandı!</strong>
    <div class="creds">
      <strong>👤 Test Kullanıcısı:</strong> humeyra@test.com &nbsp;/&nbsp; <strong>test123</strong><br>
      <strong>👑 Admin:</strong> admin@ilactakip.com &nbsp;/&nbsp; <strong>admin123</strong>
    </div>
    <a href="/bitirme-projesi/login.php" class="btn">🚀 Sisteme Giriş Yap</a>
  </div>
  <div class="log"><?= implode('<br>', $success) ?></div>
  <p class="warn">⚠️ Güvenlik için kurulum bittikten sonra setup.php dosyasını silebilirsiniz.</p>
  <?php endif; ?>
</div>
</body>
</html>
