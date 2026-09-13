<?php
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/media.php';

$post = null;

if (isset($_GET['preview'])) {
    $viewer = require_login();
    $st = db()->prepare(
        'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?'
    );
    $st->execute([(int) $_GET['preview']]);
    $post = $st->fetch();
    if ($post && empty($viewer['is_admin']) && (int) $post['user_id'] !== (int) $viewer['id']) {
        $post = null;
    }
} else {
    $st = db()->prepare(
        "SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id
         WHERE p.slug = ? AND p.status = 'approved'"
    );
    $st->execute([$_GET['slug'] ?? '']);
    $post = $st->fetch();
}

if (!$post) {
    http_response_code(404);
    page_head('Not found', 'Community', 'community', 'COMMUNITY');
    echo '<div class="empty-state">No such post. <a href="/community/">Back to the community feed</a>.</div>';
    page_foot();
    exit;
}

$isSite = ($post['channel'] ?? 'community') === 'site';
$date = $post['approved_at'] ?: $post['created_at'];
$media = post_media((int) $post['id']);

$seq = null;
if ($post['approved_at']) {
    $st = db()->prepare(
        'SELECT COUNT(*) FROM posts p2
         WHERE p2.channel = ? AND p2.type = ? AND p2.status = \'approved\'
           AND (p2.approved_at < ? OR (p2.approved_at = ? AND p2.id <= ?))'
    );
    $st->execute([$post['channel'], $post['type'], $post['approved_at'], $post['approved_at'], $post['id']]);
    $seq = str_pad((string) $st->fetchColumn(), 4, '0', STR_PAD_LEFT);
}

$prev = $next = null;
if ($isSite && $post['status'] === 'approved') {
    $st = db()->prepare(
        "SELECT title, slug, type, channel FROM posts
         WHERE channel = 'site' AND status = 'approved' AND type = ?
           AND (approved_at < ? OR (approved_at = ? AND id < ?))
         ORDER BY approved_at DESC, id DESC LIMIT 1"
    );
    $st->execute([$post['type'], $post['approved_at'], $post['approved_at'], $post['id']]);
    $prev = $st->fetch();
    $st = db()->prepare(
        "SELECT title, slug, type, channel FROM posts
         WHERE channel = 'site' AND status = 'approved' AND type = ?
           AND (approved_at > ? OR (approved_at = ? AND id > ?))
         ORDER BY approved_at ASC, id ASC LIMIT 1"
    );
    $st->execute([$post['type'], $post['approved_at'], $post['approved_at'], $post['id']]);
    $next = $st->fetch();
}

$dateStr = date('d/m/Y', strtotime($date));
if ($isSite && $post['type'] === 'transmission') {
    $tagLabel = 'Transmission' . ($seq ? ' · ' . $seq : '');
    $metabar  = 'Transmission' . ($seq ? ' ' . $seq : '') . ' — ' . $dateStr;
    $tagClass = 'transmission';
} elseif ($isSite) {
    $tagLabel = ucfirst($post['type']) . ' · ' . $post['title'];
    $metabar  = ucfirst($post['type']) . ' — ' . $post['title'] . ', ' . $dateStr;
    $tagClass = e($post['type']);
} else {
    $tagLabel = 'Community · ' . ucfirst($post['type']);
    $metabar  = 'Community ' . $post['type'] . ' — ' . $dateStr;
    $tagClass = 'community';
}

$lede = ($isSite && $post['excerpt'] !== '')
    ? $post['excerpt']
    : 'By ' . $post['username'] . ' — ' . $dateStr . '.';

if ($isSite && $post['type'] === 'transmission') {
    $brand = 'TRANSMISSION';
    $current = 'feed';
} elseif ($isSite) {
    $brand = 'NOTES';
    $current = 'notes';
} else {
    $brand = 'COMMUNITY';
    $current = 'community';
}
page_head($post['title'], $metabar, $current, $brand);
?>
  <div style="max-width:640px; margin:0 auto 14px;">
    <span class="type-tag <?= $tagClass ?>"><?= e($tagLabel) ?></span>
<?php if ($post['status'] !== 'approved'): ?>
    <span class="type-tag" style="margin-left:6px;"><?= e(strtoupper($post['status'])) ?> — PREVIEW</span>
<?php endif; ?>
<?php $me = current_user(); if ($me && (!empty($me['is_admin']) || (int) $me['id'] === (int) $post['user_id'])): ?>
    <a class="type-tag" style="margin-left:6px;" href="/community/edit.php?id=<?= (int) $post['id'] ?>">Edit</a>
<?php endif; ?>
  </div>

  <article class="prose">
    <h1><?= e($post['title']) ?></h1>
    <p class="lede"><?= e($lede) ?></p>
  </article>

<?php foreach ($media as $m): ?>
  <div class="media-block">
<?php if ($m['kind'] === 'audio'): ?>
    <audio controls preload="metadata" src="/community/media/<?= e($m['filename']) ?>"></audio>
<?php else: ?>
    <video controls preload="metadata" src="/community/media/<?= e($m['filename']) ?>"></video>
<?php endif; ?>
  </div>
<?php endforeach; ?>

  <article class="prose">
<?= paragraphs($post['body']) ?>
  </article>

<?php if (strpos($post['body'], '[stamp]') === false): ?>
  <div class="close-stamp">— <?= $isSite ? e(ucfirst($post['type'])) . ', by the author' : 'Community ' . e($post['type']) . ', signed ' . e($post['username']) ?>. <?= e($dateStr) ?>.</div>
<?php endif; ?>

  <nav class="cross-nav">
<?php if ($isSite): ?>
<?php if ($prev): ?>
    <a href="<?= e(post_url($prev)) ?>">← Previous</a>
<?php else: ?>
    <a href="#" class="disabled">← Previous</a>
<?php endif; ?>
    <span class="spacer"></span>
    <a href="<?= $post['type'] === 'transmission' ? '/' : '/notes.php' ?>">↑ All <?= $post['type'] === 'transmission' ? 'transmissions' : 'notes' ?></a>
    <span class="spacer"></span>
<?php if ($next): ?>
    <a href="<?= e(post_url($next)) ?>">Next →</a>
<?php else: ?>
    <a href="#" class="disabled">Next →</a>
<?php endif; ?>
<?php else: ?>
    <a href="/community/">← All community posts</a>
    <span class="spacer"></span>
    <a href="/">↑ Site feed</a>
<?php endif; ?>
  </nav>
<?php
page_foot($isSite ? 'site' : 'community');
