<?php
require_once __DIR__ . '/db.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid request. Go back and try again.');
    }
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, username, is_admin FROM users WHERE id = ?');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch();
    return $u ?: null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if (!$u['is_admin']) {
        http_response_code(403);
        exit('Admins only.');
    }
    return $u;
}

function post_url(array $p): string {
    $slug = rawurlencode($p['slug']);
    if (($p['channel'] ?? 'community') !== 'site') {
        return '/community/' . $slug;
    }
    return ($p['type'] === 'transmission' ? '/transmissions/' : '/notes/') . $slug;
}
