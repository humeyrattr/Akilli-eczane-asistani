<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// Rol değiştir
if (isset($_GET['toggle_role'])) {
    $uid = (int)$_GET['toggle_role'];
    if ($uid !== $_SESSION['user_id']) {
        $u = $pdo->prepare("SELECT role FROM users WHERE id=?"); $u->execute([$uid]); $u=$u->fetch();
        $newRole = ($u['role']==='admin') ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $uid]);
    }
    header('Location: users.php'); exit;
}
// Sil
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== $_SESSION['user_id']) { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]); setFlash('success','Kullanıcı silindi.'); }
    header('Location: users.php'); exit;
}

$users = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT m.id) as med_count,
           COUNT(DISTINCT dl.id) as dose_count
    FROM users u
    LEFT JOIN medications m ON m.user_id=u.id
    LEFT JOIN dose_logs dl ON dl.user_id=u.id
    GROUP BY u.id ORDER BY u.created_at DESC
")->fetchAll();

$pageTitle = 'Kullanıcı Yönetimi';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div><h1>Kullanıcı Yönetimi</h1><p>Toplam <?=count($users)?> kullanıcı</p></div></div>
<div class="card">
<div class="table-wrap"><table>
  <thead><tr><th>#</th><th>Ad Soyad</th><th>E-posta</th><th>Rol</th><th>İlaç</th><th>Doz</th><th>Kayıt Tarihi</th><th>İşlemler</th></tr></thead>
  <tbody>
  <?php foreach($users as $u): ?>
  <tr>
    <td><?=$u['id']?></td>
    <td><?=e($u['name'])?></td>
    <td><?=e($u['email'])?></td>
    <td><span class="pill <?=$u['role']==='admin'?'pill-warning':'pill-info'?>"><?=$u['role']==='admin'?'Admin':'Kullanıcı'?></span></td>
    <td><?=$u['med_count']?></td>
    <td><?=$u['dose_count']?></td>
    <td><?=formatDate($u['created_at'])?></td>
    <td>
      <?php if ($u['id'] !== $_SESSION['user_id']): ?>
      <a href="?toggle_role=<?=$u['id']?>" class="btn btn-sm btn-secondary" title="Rol Değiştir"><i class="fas fa-exchange-alt"></i></a>
      <a href="?delete=<?=$u['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istiyor musunuz?')"><i class="fas fa-trash"></i></a>
      <?php else: ?><span class="pill pill-gray">Ben</span><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
