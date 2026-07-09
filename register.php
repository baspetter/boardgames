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
        $error = 'Ongeldig e-mailadres';
    } elseif (mb_strlen($username) < 3) {
        $error = 'Gebruikersnaam moet minimaal 3 tekens zijn';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Wachtwoord moet minimaal 8 tekens zijn';
    } elseif ($inviteCode === '') {
        $error = 'Uitnodigingscode is verplicht';
    } else {
        $inviteId = validate_invite_code($inviteCode, $error);
        if ($inviteId !== null) {
            if (find_user_by_email($email)) {
                $error = 'E-mailadres is al in gebruik';
            } else {
                $stmt = db()->prepare('SELECT id FROM users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Gebruikersnaam is al in gebruik';
                } else {
                    create_user($email, $username, $password, $inviteId);
                    redirect('/login.php?registered=1');
                }
            }
        }
    }
}

$pageTitle = 'Registreren - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1>Account aanmaken</h1>
  <form method="post" class="form-stack">
    <input type="email" name="email" placeholder="E-mailadres" value="<?= h($_POST['email'] ?? '') ?>" required>
    <input type="text" name="username" placeholder="Gebruikersnaam" minlength="3" value="<?= h($_POST['username'] ?? '') ?>" required>
    <input type="password" name="password" placeholder="Wachtwoord (min. 8 tekens)" minlength="8" required>
    <input type="text" name="invite_code" placeholder="Uitnodigingscode" value="<?= h($_POST['invite_code'] ?? '') ?>" required>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <button type="submit" class="btn btn-accent btn-block">Registreren</button>
  </form>
  <p class="hint" style="margin-top:1rem;">Al een account? <a href="/login.php" style="color:var(--accent)">Inloggen</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
