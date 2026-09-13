<?php
require_once __DIR__ . '/auth.php';

function page_head(string $title, string $metabar, string $current = 'community', string $brand = 'TRANSMISSION'): void {
    $u = current_user();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — TRANSMISSION</title>
<link rel="stylesheet" href="/styles.css">
</head>
<body>

<header class="site-head">
  <div class="brand">
    <?= e($brand) ?>
  </div>
  <nav>
    <a href="/"<?= $current === 'feed' ? ' class="current"' : '' ?>>Feed</a>
    <a href="/notes.php"<?= $current === 'notes' ? ' class="current"' : '' ?>>Notes</a>
    <a href="/directory.php"<?= $current === 'index' ? ' class="current"' : '' ?>>Index</a>
    <a href="/community/"<?= $current === 'community' ? ' class="current"' : '' ?>>Community</a>
<?php if ($u): ?>
    <a href="/community/write.php">Write</a>
    <a href="/community/edit.php">Edit</a>
<?php if ($u['is_admin']): ?>
    <a href="/community/moderate.php">Moderate</a>
<?php endif; ?>
    <a href="/community/logout.php">Log out · <?= e($u['username']) ?></a>
<?php else: ?>
    <a href="/community/login.php">Log in</a>
    <a href="/community/register.php">Join</a>
<?php endif; ?>
  </nav>
</header>

<div class="meta-bar">
  <span><?= e($metabar) ?></span>
</div>

<main>
<?php
}

function page_foot(string $mode = 'community'): void {
    ?>
</main>

<aside class="attribution">
<?php if ($mode === 'site'): ?>
  <span class="label">Authorship note</span>
  This entry has been written and published directly by the site's author.
<?php else: ?>
  <span class="label">Community note</span>
  Posts in this section are written and signed by their authors, and reviewed by the site's author before publication. Transmissions and notes elsewhere on this site remain the author's own.
<?php endif; ?>
</aside>

<footer>
  <span>www.sciencecoherence.com</span>
</footer>

</body>
</html>
<?php
}

function paragraphs(string $body): string {
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($body)) as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (preg_match('/^---\s*(.+?)\s*---$/u', $block, $m)) {
            $out .= '    <div class="prose-rule">— ' . e($m[1]) . ' —</div>' . "\n";
        } elseif (preg_match('/^\[eq(?:\s+([^\]]+))?\]\s*(.+)$/u', $block, $m)) {
            $f = e(trim($m[2]));
            $f = preg_replace('/&lt;(\/?)(sub|sup)&gt;/', '<$1$2>', $f);
            $no = trim($m[1]);
            $out .= '    <div class="eq"><span class="formula">' . $f . '</span>' . ($no !== '' ? '<span class="no">(' . e($no) . ')</span>' : '') . '</div>' . "\n";
        } elseif (preg_match('/^\[stamp\]\s*(.+)$/u', $block, $m)) {
            $out .= '    <div class="close-stamp">' . e($m[1]) . '</div>' . "\n";
        } else {
            $p = nl2br(e($block));
            $p = preg_replace('/\*([^*\n<]+)\*/', '<em>$1</em>', $p);
            $out .= '    <p>' . $p . "</p>\n";
        }
    }
    return $out;
}

function slugify(string $t): string {
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
    if ($s === false || $s === '') {
        $s = $t;
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? substr($s, 0, 140) : 'post';
}
