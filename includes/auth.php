<?php
require_once __DIR__ . '/db.php';

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Admin kontrolü
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Giriş zorunlu
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

// Admin zorunlu
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    }
}

// Kullanıcı bilgilerini session'a yükle
function loginUser($user) {
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email']= $user['email'];
    $_SESSION['role']      = $user['role'];
}

// Çıkış
function logoutUser() {
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Şifre hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Şifre doğrula
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// CSRF token oluştur
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS koruması
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Flash mesaj
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Mevcut kullanıcıyı DB'den getir
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Bugünün dozlarını pending durumuna geçir (her giriş kontrolünde çalışır)
function generateTodayDoses() {
    global $pdo;
    if (!isLoggedIn()) return;
    
    $userId = $_SESSION['user_id'];
    $today  = today();
    
    // Aktif ilaçları al
    $stmt = $pdo->prepare("
        SELECT * FROM medications 
        WHERE user_id = ? AND active = 1 
        AND start_date <= ? 
        AND (end_date IS NULL OR end_date >= ?)
    ");
    $stmt->execute([$userId, $today, $today]);
    $meds = $stmt->fetchAll();
    
    foreach ($meds as $med) {
        $times = json_decode($med['times'], true) ?? [];
        foreach ($times as $time) {
            // Bu doz zaten var mı?
            $check = $pdo->prepare("
                SELECT id FROM dose_logs 
                WHERE medication_id = ? AND scheduled_date = ? AND scheduled_time = ?
            ");
            $check->execute([$med['id'], $today, $time]);
            if (!$check->fetch()) {
                // Yoksa oluştur
                $ins = $pdo->prepare("
                    INSERT INTO dose_logs (medication_id, user_id, scheduled_date, scheduled_time, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $ins->execute([$med['id'], $userId, $today, $time]);
            }
        }
    }
    
    // Geçmiş pending dozları 'missed' yap
    $pdo->prepare("
        UPDATE dose_logs 
        SET status = 'missed'
        WHERE user_id = ? 
        AND status = 'pending'
        AND (scheduled_date < ? OR (scheduled_date = ? AND scheduled_time < SUBTIME(CURTIME(), '00:30:00')))
    ")->execute([$userId, $today, $today]);
}
