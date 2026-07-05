<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM med_database WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success','İlaç silindi.'); header('Location: medications_db.php'); exit;
}

$editing = null;
if (isset($_GET['edit'])) {
    $editing = $pdo->prepare("SELECT * FROM med_database WHERE id=?");
    $editing->execute([(int)$_GET['edit']]); $editing = $editing->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $data = [trim($_POST['name']??''), trim($_POST['generic_name']??''), trim($_POST['category']??''), trim($_POST['description']??''), trim($_POST['usage_info']??''), trim($_POST['side_effects']??''), $_SESSION['user_id']];
    if ($id) {
        $pdo->prepare("UPDATE med_database SET name=?,generic_name=?,category=?,description=?,usage_info=?,side_effects=? WHERE id=?")->execute(array_merge(array_slice($data,0,6),[$id]));
        setFlash('success','İlaç güncellendi.');
    } else {
        $pdo->prepare("INSERT INTO med_database(name,generic_name,category,description,usage_info,side_effects,created_by) VALUES(?,?,?,?,?,?,?)")->execute($data);
        setFlash('success','İlaç eklendi.');
    }
    header('Location: medications_db.php'); exit;
}

$meds = $pdo->query("SELECT * FROM med_database ORDER BY name")->fetchAll();
$pageTitle = 'İlaç Veritabanı';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>İlaç Veritabanı</h1><p>Chatbot'un kullandığı ilaç bilgileri</p></div></div>
<div class="grid-2">
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-plus"></i> <?=$editing?'İlaç Düzenle':'Yeni İlaç Ekle'?></div></div>
  <form method="post">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?=$editing['id']?>"><?php endif; ?>
    <div class="form-row">
      <div class="form-group"><label class="form-label">İlaç Adı *</label><input type="text" name="name" class="form-control" value="<?=e($editing['name']??'')?>" required></div>
      <div class="form-group"><label class="form-label">Jenerik Ad</label><input type="text" name="generic_name" class="form-control" value="<?=e($editing['generic_name']??'')?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" value="<?=e($editing['category']??'')?>" placeholder="Ağrı Kesici, Antibiyotik..."></div>
    <div class="form-group"><label class="form-label">Açıklama</label><textarea name="description" class="form-control" rows="3"><?=e($editing['description']??'')?></textarea></div>
    <div class="form-group"><label class="form-label">Kullanım Bilgisi</label><textarea name="usage_info" class="form-control" rows="2"><?=e($editing['usage_info']??'')?></textarea></div>
    <div class="form-group"><label class="form-label">Yan Etkiler</label><textarea name="side_effects" class="form-control" rows="2"><?=e($editing['side_effects']??'')?></textarea></div>
    <div style="display:flex;gap:8px;">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet</button>
      <?php if ($editing): ?><a href="medications_db.php" class="btn btn-secondary">İptal</a><?php endif; ?>
    </div>
  </form>
</div>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Kayıtlı İlaçlar (<?=count($meds)?>)</div></div>
  <div class="table-wrap" style="max-height:500px;overflow-y:auto;"><table>
    <thead><tr><th>İlaç</th><th>Kategori</th><th>İşlem</th></tr></thead>
    <tbody>
    <?php foreach($meds as $m): ?>
    <tr><td><?=e($m['name'])?><?php if($m['generic_name']): ?><br><span style="font-size:.75rem;color:var(--text3)"><?=e($m['generic_name'])?></span><?php endif; ?></td>
    <td><?=e($m['category']??'-')?></td>
    <td><a href="?edit=<?=$m['id']?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i></a> <a href="?delete=<?=$m['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Silinsin mi?')"><i class="fas fa-trash"></i></a></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
