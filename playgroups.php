<?php
require_once __DIR__ . '/includes/playgroups.php';

$userId = require_login();
$createError = null;
$joinError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 2) {
            $createError = 'Name must be at least 2 characters';
        } else {
            create_playgroup($name, $userId);
            redirect('/playgroups.php');
        }
    } elseif ($action === 'join') {
        $code = trim($_POST['invite_code'] ?? '');
        try {
            join_playgroup($code, $userId);
            redirect('/playgroups.php');
        } catch (Exception $e) {
            $joinError = $e->getMessage();
        }
    }
}

$groups = get_user_playgroups($userId);
$pageTitle = 'Playgroups - ' . SITE_NAME;
$activeNav = 'playgroups';
require __DIR__ . '/includes/header.php';
?>
<h1>Playgroups</h1>

<div class="form-card" style="margin-left:0;">
  <h2 style="margin-top:0;">Create new group</h2>
  <form method="post" class="form-stack" style="flex-direction:row;">
    <input type="hidden" name="action" value="create">
    <input type="text" name="name" placeholder="New playgroup name" minlength="2" required>
    <button type="submit" class="btn btn-accent">Create</button>
  </form>
  <?php if ($createError): ?><p class="error"><?= h($createError) ?></p><?php endif; ?>

  <hr style="border-color:var(--border);margin:1rem 0;">

  <h2>Join a group</h2>
  <form method="post" class="form-stack" style="flex-direction:row;">
    <input type="hidden" name="action" value="join">
    <input type="text" name="invite_code" placeholder="Group invite code" required>
    <button type="submit" class="btn btn-secondary">Join</button>
  </form>
  <?php if ($joinError): ?><p class="error"><?= h($joinError) ?></p><?php endif; ?>
</div>

<div style="max-width:32rem;display:flex;flex-direction:column;gap:0.5rem;margin-top:2rem;">
  <?php foreach ($groups as $group): ?>
    <a href="/playgroup.php?id=<?= (int) $group['id'] ?>" class="pill" style="display:flex;justify-content:space-between;padding:1rem;background:var(--surface);">
      <span>
        <strong><?= h($group['name']) ?></strong><br>
        <span class="hint"><?= (int) $group['member_count'] ?> members</span>
      </span>
      <span class="hint">&rarr;</span>
    </a>
  <?php endforeach; ?>
  <?php if (!$groups): ?>
    <p class="hint">You're not a member of a playgroup yet.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
