<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$msg   = trim($input['message'] ?? '');

if (!$msg) { echo json_encode(['answer'=>'Lütfen bir soru yazın.']); exit; }

// Genel ilaç veritabanında ara
function searchMedDatabase($pdo, $query) {
    $stmt = $pdo->prepare("SELECT * FROM med_database WHERE name LIKE ? OR generic_name LIKE ? LIMIT 1");
    $stmt->execute(["%$query%","%$query%"]);
    $med = $stmt->fetch();
    if ($med) {
        return "💊 <strong>{$med['name']}</strong>" .
               ($med['generic_name'] ? " ({$med['generic_name']})" : '') .
               ($med['category'] ? " — {$med['category']}" : '') .
               "\n\n" . ($med['description'] ?? '') .
               ($med['usage_info'] ? "\n\n📋 <strong>Kullanım:</strong> {$med['usage_info']}" : '') .
               ($med['side_effects'] ? "\n\n⚠️ <strong>Yan Etkiler:</strong> {$med['side_effects']}" : '') .
               "\n\n<em>Bu bilgiler genel amaçlıdır. Doktorunuza danışmanızı öneririz.</em>";
    }
    return null;
}

// Chatbot Q&A'da keyword matching
function searchChatbotQA($pdo, $query) {
    $stmt = $pdo->query("SELECT * FROM chatbot_qa WHERE is_active=1");
    $rows = $stmt->fetchAll();
    $queryLower = mb_strtolower($query, 'UTF-8');
    
    $bestMatch = null; $bestScore = 0;
    foreach ($rows as $row) {
        $keywords = explode(',', $row['keywords']);
        $score = 0;
        foreach ($keywords as $kw) {
            $kw = trim(mb_strtolower($kw, 'UTF-8'));
            if ($kw && mb_strpos($queryLower, $kw, 0, 'UTF-8') !== false) {
                $score += strlen($kw); // daha uzun eşleşme = daha iyi
            }
        }
        if ($score > $bestScore) { $bestScore = $score; $bestMatch = $row; }
    }
    return ($bestScore > 0) ? $bestMatch['answer'] : null;
}

// Kullanıcının kendi ilaçlarını sorgulama
function searchUserMeds($pdo, $userId, $query) {
    $queryLower = mb_strtolower($query, 'UTF-8');
    if (mb_strpos($queryLower, 'ilaç', 0, 'UTF-8') !== false || mb_strpos($queryLower, 'ilacım', 0, 'UTF-8') !== false) {
        $stmt = $pdo->prepare("SELECT name, dosage, times FROM medications WHERE user_id=? AND active=1");
        $stmt->execute([$userId]);
        $meds = $stmt->fetchAll();
        if ($meds) {
            $list = "💊 Aktif ilaçlarınız:\n";
            foreach ($meds as $m) {
                $times = json_decode($m['times'],true) ?? [];
                $list .= "• {$m['name']} ({$m['dosage']}) — " . implode(', ',array_map(fn($t)=>substr($t,0,5),$times)) . "\n";
            }
            return $list;
        }
        return "Şu an aktif ilaç kaydınız bulunmuyor. <a href='".SITE_URL."/add_medication.php'>İlaç eklemek için tıklayın.</a>";
    }
    return null;
}

$userId = $_SESSION['user_id'];
$answer = null;

// 1. Kullanıcı kendi ilaçlarını soruyorsa
$answer = searchUserMeds($pdo, $userId, $msg);

// 2. Chatbot Q&A (Sabit cevapları kapatıp yapay zekaya devrettik)
// if (!$answer) $answer = searchChatbotQA($pdo, $msg);

// 3. İlaç veritabanı (Bunu da yapay zekaya devrettik)
// if (!$answer) $answer = searchMedDatabase($pdo, $msg);

// 4. Ollama (Gemma 4) Yapay Zeka Entegrasyonu
if (!$answer) {
    $url = 'http://localhost:11434/api/generate';
    
    // Çok sert ve net bir sistem promptu: Eczacı kimliği eklendi
    $systemPrompt = "Sen uzman bir eczacı ve 'Panacea Care' ilaç takip asistanısın. Kullanıcı 'parol', 'aspirin' vb. kelimeler kullandığında bunların bir ilaç olduğunu anla ve tıbbi bilgi ver (Parol'ü şifre/parola olarak algılama). Tıbbi sorulara ve ilaçlara eczacı gibi yaklaş. SADECE TÜRKÇE konuşacaksın. Cevapların MAKSİMUM 1 veya 2 CÜMLE olacak. Asla gereksiz bilgi verme.";
    
    $data = [
        'model' => 'gemma4:latest',
        'prompt' => $msg,
        'system' => $systemPrompt,
        'stream' => false,
        'options' => [
            'num_predict' => 500, // Düşünme (think) süreci için yeterli alan bırakıldı
            'temperature' => 0.3
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // İlk yükleme ve yanıt için 120 sn bekleme
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resData = json_decode($response, true);
        if (isset($resData['response'])) {
            $answer = trim($resData['response']);
            
            // Model <think> etiketiyle düşünme süreci üretiyorsa bunu gizle (kapatılmamış olsa bile)
            $answer = preg_replace('/<think>.*?(<\/think>|$)/s', '', $answer);
            $answer = trim($answer);
            
            // Eğer sildikten sonra geriye bir şey kalmadıysa
            if (empty($answer)) {
                $answer = "Yapay zeka yanıt oluşturamadı (Sadece düşünme sürecinde kaldı). Lütfen sorunuzu tekrarlayın.";
            }
        } else {
            $answer = "Yapay zeka yanıt veremedi. Lütfen tekrar deneyin.";
        }
    } else {
        $answer = "Yapay zeka modeli şu an yanıt veremiyor (Zaman aşımı veya kapalı). Lütfen daha sonra tekrar deneyin.";
    }
}

echo json_encode(['answer' => $answer]);
