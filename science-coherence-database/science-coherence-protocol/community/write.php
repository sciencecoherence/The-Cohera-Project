<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/media.php';
$user = require_login();
$isAdmin = !empty($user['is_admin']);

$err = '';
$sent = false;
$published_slug = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $channel = ($isAdmin && (($_POST['channel'] ?? '') === 'site')) ? 'site' : 'community';
    $type    = $_POST['type'] ?? 'article';
    $allowedTypes = $channel === 'site' ? ['article', 'note', 'transmission'] : ['article', 'note'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'article';
    }
    $title   = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body    = trim($_POST['body'] ?? '');

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
        $base = slugify($title);
        $slug = $base;
        for ($i = 2; $i <= 50; $i++) {
            $st = db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ?');
            $st->execute([$slug]);
            if ((int) $st->fetchColumn() === 0) {
                break;
            }
            $slug = $base . '-' . $i;
        }
        if ($isAdmin) {
            $st = db()->prepare("INSERT INTO posts (user_id, type, channel, title, slug, excerpt, body, status, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', NOW())");
            $st->execute([$user['id'], $type, $channel, $title, $slug, $excerpt, $body]);
            $published_slug = $slug;
        } else {
            $st = db()->prepare('INSERT INTO posts (user_id, type, channel, title, slug, excerpt, body) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$user['id'], $type, $channel, $title, $slug, $excerpt, $body]);
        }
        $postId = (int) db()->lastInsertId();
        store_media($postId, 'audio', $audio);
        store_media($postId, 'video', $video);
        $sent = true;
    }
}

page_head('Write', 'Write an article or note', 'none', 'WRITE');
?>
  <div class="form-block">
    <h2>Write <span class="tag">Signed <?= e($user['username']) ?></span></h2>
<?php if ($sent): ?>
<?php if ($published_slug !== null): ?>
    <div class="notice">Published directly — you are the admin. <a href="<?= e(post_url(['channel' => $channel, 'type' => $type, 'slug' => $published_slug])) ?>">View it</a>, <a href="write.php">write another</a>, or <a href="index.php">back to the feed</a>.</div>
<?php else: ?>
    <div class="notice">Received. Your post is awaiting review by the author. It will appear in the community feed once approved. <a href="write.php">Write another</a> or <a href="index.php">back to the feed</a>.</div>
<?php endif; ?>
<?php else: ?>
<?php if ($err): ?>
    <div class="notice err"><?= e($err) ?></div>
<?php endif; ?>
    <form method="post" action="write.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
<?php if ($isAdmin): ?>
      <label for="channel">Publish as</label>
      <select id="channel" name="channel">
        <option value="site" <?= ($_POST['channel'] ?? 'site') === 'site' ? 'selected' : '' ?>>Site — your own transmission or note</option>
        <option value="community" <?= ($_POST['channel'] ?? '') === 'community' ? 'selected' : '' ?>>Community — appears with the community label</option>
      </select>
<?php endif; ?>
      <label for="type">Type</label>
      <select id="type" name="type">
        <option value="article" <?= !in_array($_POST['type'] ?? '', ['note', 'transmission'], true) ? 'selected' : '' ?>>Article — essay, long-form</option>
        <option value="note" <?= ($_POST['type'] ?? '') === 'note' ? 'selected' : '' ?>>Note — technical, framework</option>
<?php if ($isAdmin): ?>
        <option value="transmission" <?= ($_POST['type'] ?? '') === 'transmission' ? 'selected' : '' ?>>Transmission — voice-originated (site only)</option>
<?php endif; ?>
      </select>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required maxlength="160"
             value="<?= e($_POST['title'] ?? '') ?>">
      <label for="excerpt">Excerpt — one line for the feed card (optional)</label>
      <input type="text" id="excerpt" name="excerpt" maxlength="280"
             value="<?= e($_POST['excerpt'] ?? '') ?>">
      <label for="body">Body — separate paragraphs with a blank line</label>
      <textarea id="body" name="body" class="body-input" required><?= e($_POST['body'] ?? '') ?></textarea>
      <label for="audio">Audio file — mp3, m4a, ogg, wav (optional, 200 MB max)</label>
      <input type="file" id="audio" name="audio" accept=".mp3,.m4a,.ogg,.wav,audio/*">
      <label for="video">Video file — mp4, webm (optional, 200 MB max)</label>
      <input type="file" id="video" name="video" accept=".mp4,.webm,video/*">
      <p style="margin-top:18px;"><button type="submit" class="btn"><?= $isAdmin ? 'Publish' : 'Submit for review' ?></button></p>
    </form>
<?php endif; ?>
  </div>
<?php
page_foot();
