<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user_id() !== null) {
    redirect('/');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $user = find_user_by_email($email);
    if ($user && password_verify($password, $user['password_hash'])) {
        login_user((int) $user['id'], $user['username'], $user['accent_color'] ?? null);
        $callback = $_POST['callback'] ?? '/';
        redirect(str_starts_with($callback, '/') ? $callback : '/');
    } else {
        $error = 'Incorrect login credentials.';
    }
}

$callback = $_GET['callback'] ?? '/';
$pageTitle = 'Log in - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1>Log in</h1>
  <form method="post" class="form-stack">
    <input type="hidden" name="callback" value="<?= h($callback) ?>">
    <input type="email" name="email" placeholder="Email address" required autofocus>
    <input type="password" name="password" placeholder="Password" required>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <?php if (isset($_GET['registered'])): ?><p class="hint">Account created, you can now log in.</p><?php endif; ?>
    <button type="submit" class="btn btn-accent btn-block">Log in</button>
  </form>
  <p class="hint" style="margin-top:1rem;">Don't have an account? <a href="/register.php" style="color:var(--accent)">Register with an invite code</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
