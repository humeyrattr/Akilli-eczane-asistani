-- ============================================================
-- Web Tabanlı Akıllı İlaç Takip Sistemi
-- Veritabanı Şeması
-- ============================================================

CREATE DATABASE IF NOT EXISTS ilac_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ilac_takip;

-- Kullanıcılar
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- İlaç kayıtları (kullanıcıya özel)
CREATE TABLE IF NOT EXISTS medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency INT DEFAULT 1 COMMENT 'Günlük kaç kez',
    times JSON DEFAULT NULL COMMENT 'Kullanım saatleri JSON dizisi',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#4f8ef7',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doz takip günlüğü
CREATE TABLE IF NOT EXISTS dose_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_id INT NOT NULL,
    user_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    taken_at DATETIME DEFAULT NULL,
    status ENUM('pending', 'taken', 'missed') DEFAULT 'pending',
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Genel ilaç veritabanı (admin ekler)
CREATE TABLE IF NOT EXISTS med_database (
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
);

-- Chatbot soru-cevap veritabanı
CREATE TABLE IF NOT EXISTS chatbot_qa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keywords VARCHAR(500) NOT NULL COMMENT 'Virgülle ayrılmış anahtar kelimeler',
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'genel',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- ÖRNEK VERİLER
-- ============================================================

-- Admin kullanıcısı (şifre: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@ilactakip.com', '$2y$10$bJTg.HHbKmEnz6vcRlcrueR.h3uyPTytau27G8Zb4uk.ZhMvPOvGq', 'admin');

-- Test kullanıcısı (şifre: test123)
INSERT INTO users (name, email, password, role) VALUES
('Hümeyra Tatar', 'humeyra@test.com', '$2y$10$jmeZb3J3IIzBRLMipbjCDek4E/Os6Ry53HQJ0w7JsuiyO7P7eNSJa', 'user');

-- Örnek ilaç veritabanı
INSERT INTO med_database (name, generic_name, category, description, usage_info, side_effects) VALUES
('Aspirin', 'Asetilsalisilik Asit', 'Ağrı Kesici', 'Ağrı ve ateş için kullanılan yaygın bir ilaçtır.', 'Yemekten sonra bol su ile alınız. Önerilen doz genellikle günde 1-3 tablettir.', 'Mide rahatsızlığı, bulantı, kanamalara yatkınlık.'),
('Parol', 'Parasetamol', 'Ağrı Kesici / Ateş Düşürücü', 'Hafif-orta şiddette ağrılar ve ateş için kullanılır.', 'Günde en fazla 4 kez, her seferinde 1-2 tablet. 4 saatte bir alınabilir.', 'Çok yüksek dozlarda karaciğer hasarı riski.'),
('Augmentin', 'Amoksisilin/Klavulanik Asit', 'Antibiyotik', 'Bakteriyel enfeksiyonlar için kullanılan geniş spektrumlu antibiyotik.', 'Yemekle birlikte alınız. Tedavi süresince düzenli kullanın.', 'Diyare, bulantı, alerjik reaksiyonlar.'),
('Vitamax', 'Multivitamin', 'Vitamin/Mineral', 'Günlük vitamin ve mineral ihtiyacını karşılamak için kullanılır.', 'Günde 1 tablet kahvaltıyla birlikte alınız.', 'Genellikle iyi tolere edilir.'),
('Lansoprazol', 'Lansoprazol', 'Mide Asidi', 'Mide asidini azaltan proton pompası inhibitörü.', 'Genellikle yemekten 30 dakika önce alınır.', 'Baş ağrısı, ishal, mide bulantısı.');

-- Chatbot soru-cevap verileri
INSERT INTO chatbot_qa (keywords, question, answer, category) VALUES
('merhaba,selam,hey,nasılsın', 'Merhaba!', 'Merhaba! Ben sizin sağlık asistanınızım. 🏥 İlaçlarınız, dozlarınız veya genel sağlık konularında sorularınızı yanıtlamaya hazırım. Size nasıl yardımcı olabilirim?', 'genel'),
('aspirin,aspirin nedir,ağrı kesici', 'Aspirin hakkında bilgi', 'Aspirin (Asetilsalisilik Asit), ağrı kesici, ateş düşürücü ve kan sulandırıcı özelliklere sahip bir ilaçtır. Baş ağrısı, diş ağrısı ve kas ağrıları için kullanılır. Mide problemleri olan kişiler dikkatli kullanmalıdır. 💊', 'ilaç'),
('parasetamol,parol,ateş,ateş düşürücü', 'Parasetamol (Parol)', 'Parasetamol, hafif-orta şiddette ağrılar ve ateş için kullanılan güvenli bir ilaçtır. Günde 4 defayı aşmamak kaydıyla kullanılabilir. Karaciğer hastalığı olanlarda dikkatli kullanılmalıdır. 🌡️', 'ilaç'),
('antibiyotik,antibiyotik kullanımı,antibiyotik ne zaman', 'Antibiyotik Kullanımı', 'Antibiyotikler yalnızca bakteriyel enfeksiyonlarda kullanılmalıdır. Viral enfeksiyonlarda (grip, soğuk algınlığı) etkisizdir. ⚠️ Önemli: Tedavi süresini tam tamamlayın, kendinizi iyi hissetseniz bile bırakmayın.', 'ilaç'),
('ilaç atladım,doz atladım,ilacı unutttum', 'İlaç Dozunu Atladım', 'Bir dozu atladıysanız: Eğer bir sonraki doza çok az zaman kaldıysa atladığınız dozu geçin ve normal zamanınızda devam edin. Kaçırılan dozu telafi etmek için çift doz almayın. 🔔 Sistemdeki hatırlatıcıları aktif tutarsanız bu sorunu önleyebilirsiniz!', 'takip'),
('su,su içmek,günlük su', 'Günlük Su Tüketimi', 'Yetişkinler için önerilen günlük su tüketimi 2-2.5 litredir. Çoğu ilaç bol su ile alınmalıdır. Su içmek ilacın emilimini kolaylaştırır ve böbrek sağlığını destekler. 💧', 'genel sağlık'),
('vitamin,vitamin eksikliği,vitamin almak', 'Vitamin Takviyesi', 'Vitaminler vücut fonksiyonları için gereklidir. Ancak bilinçsiz takviye kullanımı zararlı olabilir. A, D, E, K vitaminleri yağda çözündüğünden aşırı alındığında birikim yapabilir. Doktorunuza danışarak kullanmanız önerilir. 🥗', 'genel sağlık'),
('uyku,uyku düzeni,uyku problemi', 'Uyku ve Sağlık', 'Yetişkinler için 7-9 saat kaliteli uyku önerilir. Yetersiz uyku bağışıklık sistemini zayıflatır ve ilaçların etkisini değiştirebilir. Düzenli uyku saatleri oluşturmak önemlidir. 😴', 'genel sağlık'),
('egzersiz,spor,fiziksel aktivite', 'Egzersiz ve Sağlık', 'Düzenli fiziksel aktivite genel sağlık için çok önemlidir. Haftada en az 150 dakika orta yoğunlukta egzersiz önerilir. Bazı ilaçlar egzersiz kapasitesini etkileyebilir, doktorunuza danışın. 🏃', 'genel sağlık'),
('tansiyon,yüksek tansiyon,hipertansiyon', 'Yüksek Tansiyon', 'Normal kan basıncı 120/80 mmHg altındadır. Hipertansiyon (yüksek tansiyon) kalp hastalığı riskini artırır. Tuz tüketimini azaltmak, düzenli egzersiz ve gerektiğinde ilaç kullanımı önemlidir. Tansiyon ilaçlarınızı düzenli alın! 💓', 'hastalık'),
('diyabet,şeker hastalığı,insülin', 'Diyabet', 'Diyabet, kan şekeri seviyesinin anormal yükselmesiyle karakterize kronik bir hastalıktır. İlaç tedavisi, diyet ve egzersizle kontrol altına alınabilir. Diyabet ilaçlarınızı düzenli almanız hayati önem taşır! 🩺', 'hastalık'),
('ilaç etkileşimi,ilaç kombinasyonu,birden fazla ilaç', 'İlaç Etkileşimi', '⚠️ Birden fazla ilaç kullanırken dikkatli olun! Bazı ilaçlar birbirinin etkisini azaltabilir ya da yan etkileri artırabilir. Kullandığınız tüm ilaçları (bitkisel dahil) doktorunuza ve eczacınıza bildirin.', 'güvenlik'),
('teşekkür,sağ ol,tamam', 'Teşekkürler', 'Ne olur! 😊 Sağlıklı günler dilerim. Başka sorularınız olursa her zaman buradayım. Unutmayın, düzenli ilaç kullanımı sağlığınız için çok önemli!', 'genel'),
('yardım,ne yapabilirim,ne sorabilirm', 'Yardım', '🤖 Bana şunları sorabilirsiniz:\n• İlaç bilgileri (aspirin, parol, vb.)\n• İlaç kullanım tavsiyeleri\n• Genel sağlık konuları (tansiyon, diyabet, vb.)\n• Doz atlama durumları\n• Vitamin ve takviye kullanımı\n\nSorunuzu yazın, yardımcı olmaya çalışayım!', 'genel');

-- İndexler (performans için)
CREATE INDEX idx_medications_user ON medications(user_id, active);
CREATE INDEX idx_dose_logs_user_date ON dose_logs(user_id, scheduled_date);
CREATE INDEX idx_dose_logs_medication ON dose_logs(medication_id, scheduled_date);
