<?php
// Veritabanı bağlantı ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ilac_takip');
define('DB_CHARSET', 'utf8mb4');

// Site ayarları
define('SITE_NAME', 'Panacea Care');
define('SITE_URL', 'http://localhost/bitirme-projesi');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage()]));
}

// Tarih/saat yardımcı fonksiyonları
function now() {
    return date('Y-m-d H:i:s');
}

function today() {
    return date('Y-m-d');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d.m.Y H:i', strtotime($datetime));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->d > 0) return $diff->d . ' gün önce';
    if ($diff->h > 0) return $diff->h . ' saat önce';
    if ($diff->i > 0) return $diff->i . ' dakika önce';
    return 'Az önce';
}
