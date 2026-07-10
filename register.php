<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user_id() !== null) {
    redirect('/');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $inviteCode = trim($_POST['invite_code'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (mb_strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($inviteCode === '') {
        $error = 'Invite code is required';
    } else {
        $inviteId = validate_invite_code($inviteCode, $error);
        if ($inviteId !== null) {
            if (find_user_by_email($email)) {
                $error = 'Email address is already in use';
            } else {
                $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username is already in use';
                } else {
                    create_user($email, $username, $password, $inviteId);
                    redirect('/login.php?registered=1');
                }
            }
        }
    }
}

$pageTitle = 'Register - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1>Create account</h1>
  <form method="post" class="form-stack">
    <input type="email" name="email" placeholder="Email address" value="<?= h($_POST['email'] ?? '') ?>" required>
    <input type="text" name="username" placeholder="Username" minlength="3" value="<?= h($_POST['username'] ?? '') ?>" required>
    <input type="password" name="password" placeholder="Password (min. 8 characters)" minlength="8" required>
    <input type="text" name="invite_code" placeholder="Invite code" value="<?= h($_POST['invite_code'] ?? '') ?>" required>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <button type="submit" class="btn btn-accent btn-block">Register</button>
  </form>
  <p class="hint" style="margin-top:1rem;">Already have an account? <a href="/login.php" style="color:var(--accent)">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
