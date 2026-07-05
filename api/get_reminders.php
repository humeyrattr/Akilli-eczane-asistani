<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized', 'pending_count'=>0, 'reminders'=>[]]); exit; }

$userId = $_SESSION['user_id'];
$now    = date('H:i:s');
$today  = today();

// Şu andan 30 dakika öncesi ile 5 dakika sonrası arasında kalan pending dozlar
$stmt = $pdo->prepare("
    SELECT dl.id, m.name as medication_name, dl.scheduled_time
    FROM dose_logs dl JOIN medications m ON dl.medication_id=m.id
    WHERE dl.user_id=? AND dl.scheduled_date=? AND dl.status='pending'
    AND dl.scheduled_time BETWEEN SUBTIME(?,'-00:05:00') AND ADDTIME(?,'-00:00:01')
    ORDER BY dl.scheduled_time
");
$stmt->execute([$userId, $today, $now, $now]);
$reminders = $stmt->fetchAll();

// Toplam bekleyen
$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM dose_logs WHERE user_id=? AND scheduled_date=? AND status='pending'");
$pendingStmt->execute([$userId, $today]);
$pendingCount = (int)$pendingStmt->fetchColumn();

echo json_encode([
    'pending_count' => $pendingCount,
    'reminders'     => $reminders
]);
