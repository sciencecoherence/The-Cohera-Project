<?php
require_once __DIR__ . '/db.php';

const MEDIA_MAX_BYTES = 200 * 1024 * 1024;

function media_exts(): array {
    return [
        'audio' => ['mp3', 'm4a', 'ogg', 'wav'],
        'video' => ['mp4', 'webm'],
    ];
}

function validate_media(string $kind, ?array $file, ?string &$err): bool {
    $err = null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return true;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = ucfirst($kind) . ' upload failed — the file may exceed the server upload limit.';
        return false;
    }
    if ($file['size'] > MEDIA_MAX_BYTES) {
        $err = ucfirst($kind) . ': 200 MB maximum.';
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = media_exts()[$kind];
    if (!in_array($ext, $allowed, true)) {
        $err = ucfirst($kind) . ': allowed formats — ' . implode(', ', $allowed) . '.';
        return false;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $prefix = $kind . '/';
    $isM4a = ($kind === 'audio' && $ext === 'm4a');
    if (strpos($mime, $prefix) !== 0 && !$isM4a) {
        $err = ucfirst($kind) . ': the file content does not look like ' . $kind . '.';
        return false;
    }
    return true;
}

function store_media(int $postId, string $kind, ?array $file): void {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dir = __DIR__ . '/../media';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($dir . '/' . $name) ?: '';
        $st = db()->prepare('INSERT INTO media (post_id, kind, filename, original_name, mime) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$postId, $kind, $name, mb_substr($file['name'], 0, 190), $mime]);
    }
}

function post_media(int $postId): array {
    $st = db()->prepare('SELECT id, kind, filename, original_name FROM media WHERE post_id = ? ORDER BY id');
    $st->execute([$postId]);
    return $st->fetchAll();
}

function delete_media_row(int $mediaId, int $postId): void {
    $st = db()->prepare('SELECT filename FROM media WHERE id = ? AND post_id = ?');
    $st->execute([$mediaId, $postId]);
    $row = $st->fetch();
    if ($row) {
        $path = __DIR__ . '/../media/' . $row['filename'];
        if (is_file($path)) {
            unlink($path);
        }
        $st = db()->prepare('DELETE FROM media WHERE id = ?');
        $st->execute([$mediaId]);
    }
}

function delete_post_media(int $postId): void {
    foreach (post_media($postId) as $m) {
        delete_media_row((int) $m['id'], $postId);
    }
}
