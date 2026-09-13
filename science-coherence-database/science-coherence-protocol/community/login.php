<?php
require_once __DIR__ . '/inc/layout.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ident = trim($_POST['ident'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? OR username = ?');
    $st->execute([$ident, $ident]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['user_id'] = (int) $u['id'];
        session_regenerate_id(true);
        header('Location: index.php');
        exit;
    }
    sleep(1);
    $err = 'No match for that account and password.';
}

page_head('Log in', 'Community — log in', 'none', 'LOG IN');
?>
  <div class="form-block">
    <h2>Log in <span class="tag">Community access</span></h2>
<?php if ($err): ?>
    <div class="notice err"><?= e($err) ?></div>
<?php endif; ?>
    <form method="post" action="login.php">
      <?= csrf_field() ?>
      <label for="ident">Username or email</label>
      <input type="text" id="ident" name="ident" required maxlength="190"
             value="<?= e($_POST['ident'] ?? '') ?>">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <p style="margin-top:18px;"><button type="submit" class="btn">Log in</button></p>
    </form>
    <p class="form-hint">No account yet? <a href="register.php">Join</a>.</p>
  </div>
<?php
page_foot();
