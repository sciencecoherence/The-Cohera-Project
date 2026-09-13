<?php
require_once __DIR__ . '/inc/layout.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username)) {
        $err = 'Username: 3 to 40 characters — letters, numbers, dot, dash, underscore.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'A valid email is required.';
    } elseif (strlen($pass) < 8) {
        $err = 'Password: at least 8 characters.';
    } else {
        $isFirst = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
        try {
            $st = db()->prepare('INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, ?)');
            $st->execute([$username, $email, password_hash($pass, PASSWORD_DEFAULT), $isFirst ? 1 : 0]);
            $_SESSION['user_id'] = (int) db()->lastInsertId();
            session_regenerate_id(true);
            header('Location: write.php');
            exit;
        } catch (PDOException $ex) {
            $err = 'That username or email is already taken.';
        }
    }
}

page_head('Join', 'Community — create an account', 'none', 'JOIN');
?>
  <div class="form-block">
    <h2>Join <span class="tag">Write for the community section</span></h2>
<?php if ($err): ?>
    <div class="notice err"><?= e($err) ?></div>
<?php endif; ?>
    <form method="post" action="register.php">
      <?= csrf_field() ?>
      <label for="username">Username — this signs your posts</label>
      <input type="text" id="username" name="username" required maxlength="40"
             value="<?= e($_POST['username'] ?? '') ?>">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required maxlength="190"
             value="<?= e($_POST['email'] ?? '') ?>">
      <label for="password">Password — 8 characters minimum</label>
      <input type="password" id="password" name="password" required minlength="8">
      <p style="margin-top:18px;"><button type="submit" class="btn">Create account</button></p>
    </form>
    <p class="form-hint">Already have an account? <a href="login.php">Log in</a>. Posts are reviewed by the site's author before they appear.</p>
  </div>
<?php
page_foot();
