<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/media.php';
$admin = require_admin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && in_array($action, ['approve', 'reject', 'delete'], true)) {
        if ($action === 'approve') {
            $st = db()->prepare("UPDATE posts SET status = 'approved', approved_at = NOW() WHERE id = ?");
            $st->execute([$id]);
            $msg = 'Approved.';
        } elseif ($action === 'reject') {
            $st = db()->prepare("UPDATE posts SET status = 'rejected', approved_at = NULL WHERE id = ?");
            $st->execute([$id]);
            $msg = 'Rejected.';
        } else {
            delete_post_media($id);
            $st = db()->prepare('DELETE FROM posts WHERE id = ?');
            $st->execute([$id]);
            $msg = 'Deleted permanently.';
        }
    }
}

$pending = db()->query(
    "SELECT p.id, p.type, p.title, p.created_at, u.username
     FROM posts p JOIN users u ON u.id = p.user_id
     WHERE p.status = 'pending' ORDER BY p.created_at ASC"
)->fetchAll();

$recent = db()->query(
    "SELECT p.id, p.type, p.title, p.status, p.created_at, u.username
     FROM posts p JOIN users u ON u.id = p.user_id
     WHERE p.status <> 'pending' ORDER BY p.id DESC LIMIT 10"
)->fetchAll();

page_head('Moderate', 'Review queue', 'none', 'MODERATE');
?>
  <h2>Pending <span class="tag"><?= count($pending) ?> awaiting review</span></h2>
<?php if ($msg): ?>
  <div class="notice"><?= e($msg) ?></div>
<?php endif; ?>
<?php if (!$pending): ?>
  <div class="empty-state">Queue is empty. <b>Nothing awaits review.</b></div>
<?php else: ?>
  <table class="log">
    <thead>
      <tr>
        <th style="width:120px;">DATE</th>
        <th style="width:90px;">TYPE</th>
        <th>TITLE</th>
        <th style="width:140px;">AUTHOR</th>
        <th style="width:190px;">ACTION</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($pending as $p): ?>
      <tr>
        <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
        <td><?= e(strtoupper($p['type'])) ?></td>
        <td><a href="post.php?preview=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a></td>
        <td><?= e($p['username']) ?></td>
        <td>
          <form method="post" action="moderate.php" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" name="action" value="approve" class="btn">Approve</button>
            <button type="submit" name="action" value="reject" class="btn ghost">Reject</button>
            <button type="submit" name="action" value="delete" class="btn ghost" onclick="return confirm('Delete this post permanently?');">Delete</button>
          </form>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

  <h2>Recent decisions <span class="tag">Last 10</span></h2>
<?php if (!$recent): ?>
  <div class="empty-state">No decisions yet.</div>
<?php else: ?>
  <table class="log">
    <thead>
      <tr>
        <th style="width:120px;">DATE</th>
        <th style="width:90px;">TYPE</th>
        <th>TITLE</th>
        <th style="width:140px;">AUTHOR</th>
        <th style="width:100px;">STATUS</th>
        <th style="width:110px;">ACTION</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($recent as $p): ?>
      <tr>
        <td><?= e(date('d/m/Y', strtotime($p['created_at']))) ?></td>
        <td><?= e(strtoupper($p['type'])) ?></td>
        <td><a href="post.php?preview=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a></td>
        <td><?= e($p['username']) ?></td>
        <td class="<?= $p['status'] === 'approved' ? 'flag-ok' : 'flag-x' ?>"><?= e(strtoupper($p['status'])) ?></td>
        <td>
          <form method="post" action="moderate.php" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
            <button type="submit" name="action" value="delete" class="btn ghost" onclick="return confirm('Delete this post permanently?');">Delete</button>
          </form>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php
page_foot();
