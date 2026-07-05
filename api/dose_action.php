<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';
$userId = $_SESSION['user_id'];

if ($action === 'update') {
    $id     = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    if (!in_array($status, ['taken','missed','pending'])) { echo json_encode(['error'=>'Invalid status']); exit; }

    // Önce kaydın bu kullanıcıya ait olduğunu doğrula
    $check = $pdo->prepare("SELECT id FROM dose_logs WHERE id=? AND user_id=?");
    $check->execute([$id, $userId]);
    if (!$check->fetch()) { echo json_encode(['error'=>'Not found']); exit; }

    $takenAt = ($status === 'taken') ? now() : null;
    $pdo->prepare("UPDATE dose_logs SET status=?, taken_at=? WHERE id=?")->execute([$status, $takenAt, $id]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['error'=>'Unknown action']);
