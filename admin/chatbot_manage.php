<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM chatbot_qa WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','Soru-cevap silindi.'); header('Location: chatbot_manage.php'); exit;
}
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE chatbot_qa SET is_active = NOT is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    header('Location: chatbot_manage.php'); exit;
}

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $pdo->prepare("SELECT * FROM chatbot_qa WHERE id=?");
    $editing->execute([(int)$_GET['edit']]); $editing = $editing->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $keywords = trim($_POST['keywords'] ?? '');
    $question = trim($_POST['question'] ?? '');
    $answer   = trim($_POST['answer'] ?? '');
    $category = trim($_POST['category'] ?? 'genel');
    if ($keywords && $question && $answer) {
        if ($id) {
            $pdo->prepare("UPDATE chatbot_qa SET keywords=?,question=?,answer=?,category=? WHERE id=?")->execute([$keywords,$question,$answer,$category,$id]);
            setFlash('success','Güncellendi.');
        } else {
            $pdo->prepare("INSERT INTO chatbot_qa(keywords,question,answer,category,created_by) VALUES(?,?,?,?,?)")->execute([$keywords,$question,$answer,$category,$_SESSION['user_id']]);
            setFlash('success','Eklendi.');
        }
    }
    header('Location: chatbot_manage.php'); exit;
}

$qaList = $pdo->query("SELECT * FROM chatbot_qa ORDER BY category, id")->fetchAll();
$pageTitle = 'Chatbot Yönetimi';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Chatbot Yönetimi</h1><p>Sağlık asistanı soru-cevap veritabanı</p></div></div>
<div class="grid-2">
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-plus"></i> <?=$editing?'Düzenle':'Yeni Soru-Cevap'?></div></div>
  <form method="post">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?=$editing['id']?>"><?php endif; ?>
    <div class="form-group">
      <label class="form-label">Anahtar Kelimeler * <span style="color:var(--text3)">(virgülle ayırın)</span></label>
      <input type="text" name="keywords" class="form-control" value="<?=e($editing['keywords']??'')?>" placeholder="aspirin, ağrı kesici, baş ağrısı" required>
    </div>
    <div class="form-group">
      <label class="form-label">Temsili Soru</label>
      <input type="text" name="question" class="form-control" value="<?=e($editing['question']??'')?>" placeholder="Aspirin hakkında bilgi" required>
    </div>
    <div class="form-group">
      <label class="form-label">Cevap *</label>
      <textarea name="answer" class="form-control" rows="5" required><?=e($editing['answer']??'')?></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Kategori</label>
      <select name="category" class="form-control">
        <?php foreach(['genel','ilaç','genel sağlık','hastalık','güvenlik','takip'] as $cat): ?>
        <option value="<?=$cat?>" <?=($editing['category']??'genel')===$cat?'selected':''?>><?=ucfirst($cat)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:8px;">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
      <?php if ($editing): ?><a href="chatbot_manage.php" class="btn btn-secondary">İptal</a><?php endif; ?>
    </div>
  </form>
</div>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Kayıtlar (<?=count($qaList)?>)</div></div>
  <div style="max-height:560px;overflow-y:auto;">
  <?php foreach($qaList as $qa): ?>
  <div style="padding:12px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:flex-start;">
    <div style="flex:1;min-width:0;">
      <div style="font-weight:600;font-size:.85rem;"><?=e($qa['question'])?></div>
      <div style="font-size:.75rem;color:var(--text3);margin:3px 0;"><?=e(mb_substr($qa['keywords'],0,60,'UTF-8'))?>...</div>
      <span class="pill pill-gray" style="font-size:.7rem;"><?=e($qa['category'])?></span>
      <?php if(!$qa['is_active']): ?><span class="pill pill-danger" style="font-size:.7rem;margin-left:4px;">Pasif</span><?php endif; ?>
    </div>
    <div style="display:flex;gap:6px;flex-shrink:0;">
      <a href="?edit=<?=$qa['id']?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
      <a href="?toggle=<?=$qa['id']?>" class="btn btn-sm btn-secondary"><i class="fas fa-<?=$qa['is_active']?'eye-slash':'eye'?>"></i></a>
      <a href="?delete=<?=$qa['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')"><i class="fas fa-trash"></i></a>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
