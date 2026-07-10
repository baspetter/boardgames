<?php
require_once __DIR__ . '/includes/functions.php';

$userId = require_login();
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address';
        } elseif (mb_strlen($username) < 3) {
            $error = 'Username must be at least 3 characters';
        } else {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = 'Email address is already in use';
            } else {
                $stmt = db()->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $stmt->execute([$username, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Username is already in use';
                } else {
                    db()->prepare('UPDATE users SET email = ?, username = ? WHERE id = ?')
                        ->execute([$email, $username, $userId]);
                    $_SESSION['username'] = $username;
                    $user['email'] = $email;
                    $user['username'] = $username;
                    $success = 'Profile updated.';
                }
            }
        }
    } elseif ($action === 'update_appearance') {
        if (!empty($_POST['reset_color'])) {
            db()->prepare('UPDATE users SET accent_color = NULL WHERE id = ?')->execute([$userId]);
            $_SESSION['accent_color'] = null;
            $user['accent_color'] = null;
            $success = 'Accent color reset to default.';
        } else {
            $accentColor = trim($_POST['accent_color'] ?? '');
            if (!is_valid_hex_color($accentColor)) {
                $error = 'Invalid color';
            } else {
                db()->prepare('UPDATE users SET accent_color = ? WHERE id = ?')->execute([$accentColor, $userId]);
                $_SESSION['accent_color'] = $accentColor;
                $user['accent_color'] = $accentColor;
                $success = 'Accent color updated.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect';
        } elseif (mb_strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            $success = 'Password changed.';
        }
    }
}

$pageTitle = 'Edit profile - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<h1>Edit profile</h1>

<div class="form-card" style="margin-left:0;">
  <h2 style="margin-top:0;">Account details</h2>
  <form method="post" class="form-stack">
    <input type="hidden" name="action" value="update_profile">
    <input type="email" name="email" placeholder="Email address" value="<?= h($user['email']) ?>" required>
    <input type="text" name="username" placeholder="Username" minlength="3" value="<?= h($user['username']) ?>" required>
    <button type="submit" class="btn btn-accent">Save changes</button>
  </form>

  <hr style="border-color:var(--border);margin:1.5rem 0;">

  <h2>Appearance</h2>
  <form method="post" class="form-stack">
    <input type="hidden" name="action" value="update_appearance">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <input type="color" name="accent_color" value="<?= h($user['accent_color'] ?? '#e63946') ?>" style="width:3rem;height:2.5rem;padding:0.25rem;flex-shrink:0;">
      <span class="hint">Accent color used throughout the site (buttons, links, highlights)</span>
    </div>
    <div class="field-row">
      <button type="submit" class="btn btn-accent">Save color</button>
      <button type="submit" name="reset_color" value="1" class="btn btn-secondary">Reset to default</button>
    </div>
  </form>

  <hr style="border-color:var(--border);margin:1.5rem 0;">

  <h2>Change password</h2>
  <form method="post" class="form-stack">
    <input type="hidden" name="action" value="change_password">
    <input type="password" name="current_password" placeholder="Current password" required>
    <input type="password" name="new_password" placeholder="New password (min. 8 characters)" minlength="8" required>
    <button type="submit" class="btn btn-secondary">Change password</button>
  </form>

  <?php if ($error): ?><p class="error" style="margin-top:1rem;"><?= h($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="hint" style="margin-top:1rem;color:#4ade80;"><?= h($success) ?></p><?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
