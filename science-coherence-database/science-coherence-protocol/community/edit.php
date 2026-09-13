<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/media.php';
$user = require_login();
$isAdmin = !empty($user['is_admin']);

function fetch_post(int $id): ?array {
    $st = db()->prepare('SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    return $p ?: null;
}

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $post = fetch_post($id);
    if (!$post || (!$isAdmin && (int) $post['user_id'] !== (int) $user['id'])) {
        http_response_code(403);
        exit('You cannot edit this post.');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        delete_post_media($id);
        $st = db()->prepare('DELETE FROM posts WHERE id = ?');
        $st->execute([$id]);
        header('Location: edit.php?deleted=1');
        exit;
    }

    $channel = $isAdmin ? ((($_POST['channel'] ?? '') === 'site') ? 'site' : 'community') : $post['channel'];
    $type    = $_POST['type'] ?? $post['type'];
    $allowedTypes = ($isAdmin && $channel === 'site') ? ['article', 'note', 'transmission'] : ['article', 'note'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = $post['type'];
    }
    $title   = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if ($isAdmin) {
        $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_POST['status'] : $post['status'];
    } else {
        $status = 'pending';
    }
    $approvedAt = $post['approved_at'];
    if ($status === 'approved' && !$approvedAt) {
        $approvedAt = date('Y-m-d H:i:s');
    }

    $audio = $_FILES['audio'] ?? null;
    $video = $_FILES['video'] ?? null;
    $mediaErr = null;

    if (mb_strlen($title) < 3 || mb_strlen($title) > 160) {
        $err = 'Title: 3 to 160 characters.';
    } elseif (mb_strlen($excerpt) > 280) {
        $err = 'Excerpt: 280 characters maximum.';
    } elseif (mb_strlen($body) < 100) {
        $err = 'Body: at least 100 characters.';
    } elseif (!validate_media('audio', $audio, $mediaErr) || !validate_media('video', $video, $mediaErr)) {
        $err = $mediaErr;
    } else {
        foreach ((array) ($_POST['remove_media'] ?? []) as $mid) {
            delete_media_row((int) $mid, $id);
        }
        store_media($id, 'audio', $audio);
        store_media($id, 'video', $video);
        $st = db()->prepare('UPDATE posts SET type = ?, channel = ?, title = ?, excerpt = ?, body = ?, status = ?, approved_at = ? WHERE id = ?');
        $st->execute([$type, $channel, $title, $excerpt, $body, $status, $approvedAt, $id]);
        $msg = $isAdmin ? 'Saved.' : 'Saved — your changes were sent back for review.';
    }
    $post = fetch_post($id);
    $editing = $post;
} elseif (isset($_GET['id'])) {
    $editing = fetch_post((int) $_GET['id']);
    if (!$editing || (!$isAdmin && (int) $editing['user_id'] !== (int) $user['id'])) {
        http_response_code(403);
        exit('You cannot edit this post.');
    }
} else {
    $editing = null;
}

if ($editing === null) {
    if ($isAdmin) {
        $rows = db()->query(
            'SELECT p.id, p.type, p.channel, p.title, p.slug, p.status, p.created_at, u.username
             FROM posts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 200'
        )->fetchAll();
    } else {
        $st = db()->prepare(
            'SELECT p.id, p.type, p.channel, p.title, p.slug, p.status, p.created_at, u.username
             FROM posts p JOIN users u ON u.id = p.user_id WHERE p.user_id = ? ORDER BY p.id DESC LIMIT 200'
        );
        $st->execute([$user['id']]);
        $rows = $st->fetchAll();
    }
    page_head('Edit', $isAdmin ? 'All posts — pick one to edit' : 'Your posts — pick one to edit', 'none', 'EDIT');
    ?>
  <h2>Posts <span class="tag"><?= count($rows) ?> total</span></h2>
<?php if (isset($_GET['deleted'])): ?>
  <div class="notice">Post deleted.</div>
<?php endif; ?>
<?php if (!$rows): ?>
  <div class="empty-state">Nothing here yet. <a href="write.php">Write something</a>.</div>
<?php else: ?>
  <table class="log">
    <thead>
      <tr>
        <th style="width:110px;">DATE</th>
        <th style="width:130px;">TYPE</th>
        <th>TITLE</th>
        <th style="width:130px;">AUTHOR</th>
        <th style="width:100px;">STATUS</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e(date('d/m/Y', strtotime($r['created_at']))) ?></td>
        <td><?= $r['channel'] === 'site' ? e(strtoupper($r['type'])) : 'COMM. ' . e(strtoupper($r['type'])) ?></td>
        <td><a href="edit.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></td>
        <td><?= e($r['username']) ?></td>
        <td class="<?= $r['status'] === 'approved' ? 'flag-ok' : ($r['status'] === 'rejected' ? 'flag-x' : '') ?>"><?= e(strtoupper($r['status'])) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
    <?php
    page_foot();
    exit;
}

$media = post_media((int) $editing['id']);
page_head('Edit — ' . $editing['title'], 'Editing post #' . (int) $editing['id'], 'none', 'EDIT');
?>
  <div class="form-block">
    <h2>Edit <span class="tag"><?= e($editing['slug']) ?></span></h2>
<?php if ($msg): ?>
    <div class="notice"><?= e($msg) ?> <a href="post.php?preview=<?= (int) $editing['id'] ?>">Preview</a> · <a href="edit.php">All posts</a></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="notice err"><?= e($err) ?></div>
<?php endif; ?>
    <form method="post" action="edit.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
<?php if ($isAdmin): ?>
      <label for="channel">Publish as</label>
      <select id="channel" name="channel">
        <option value="site" <?= $editing['channel'] === 'site' ? 'selected' : '' ?>>Site — your own transmission or note</option>
        <option value="community" <?= $editing['channel'] === 'community' ? 'selected' : '' ?>>Community — appears with the community label</option>
      </select>
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="approved" <?= $editing['status'] === 'approved' ? 'selected' : '' ?>>Approved — public</option>
        <option value="pending" <?= $editing['status'] === 'pending' ? 'selected' : '' ?>>Pending — hidden</option>
        <option value="rejected" <?= $editing['status'] === 'rejected' ? 'selected' : '' ?>>Rejected — hidden</option>
      </select>
<?php endif; ?>
      <label for="type">Type</label>
      <select id="type" name="type">
        <option value="article" <?= $editing['type'] === 'article' ? 'selected' : '' ?>>Article — essay, long-form</option>
        <option value="note" <?= $editing['type'] === 'note' ? 'selected' : '' ?>>Note — technical, framework</option>
<?php if ($isAdmin): ?>
        <option value="transmission" <?= $editing['type'] === 'transmission' ? 'selected' : '' ?>>Transmission — voice-originated (site only)</option>
<?php endif; ?>
      </select>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required maxlength="160" value="<?= e($editing['title']) ?>">
      <label for="excerpt">Excerpt</label>
      <input type="text" id="excerpt" name="excerpt" maxlength="280" value="<?= e($editing['excerpt']) ?>">
      <label for="body">Body — separate paragraphs with a blank line</label>
      <textarea id="body" name="body" class="body-input" required><?= e($editing['body']) ?></textarea>
<?php if ($media): ?>
      <label>Attached media — tick to remove on save</label>
<?php foreach ($media as $m): ?>
      <p style="margin:4px 0;"><input type="checkbox" name="remove_media[]" value="<?= (int) $m['id'] ?>" id="rm<?= (int) $m['id'] ?>" style="width:auto;"> <label for="rm<?= (int) $m['id'] ?>" style="display:inline; text-transform:none; margin:0;"><?= e(strtoupper($m['kind'])) ?> — <?= e($m['original_name'] ?: $m['filename']) ?></label></p>
<?php endforeach; ?>
<?php endif; ?>
      <label for="audio">Add audio — mp3, m4a, ogg, wav (optional)</label>
      <input type="file" id="audio" name="audio" accept=".mp3,.m4a,.ogg,.wav,audio/*">
      <label for="video">Add video — mp4, webm (optional)</label>
      <input type="file" id="video" name="video" accept=".mp4,.webm,video/*">
      <p style="margin-top:18px;">
        <button type="submit" class="btn">Save</button>
        <a class="btn ghost" href="post.php?preview=<?= (int) $editing['id'] ?>" style="text-decoration:none;">Preview</a>
      </p>
    </form>
    <form method="post" action="edit.php" onsubmit="return confirm('Delete this post and its media files permanently?');" style="margin-top:10px;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
      <button type="submit" name="action" value="delete" class="btn ghost">Delete post</button>
    </form>
  </div>
<?php
page_foot();
