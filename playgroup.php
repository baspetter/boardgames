<?php
require_once __DIR__ . '/includes/playgroups.php';
require_once __DIR__ . '/includes/collections.php';
require_once __DIR__ . '/includes/partials.php';

$userId = require_login();
$playGroupId = (int) ($_GET['id'] ?? 0);

$membership = get_membership($userId, $playGroupId);
if (!$membership) {
    http_response_code(404);
    exit('Group not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regenerate_code') {
    if ($membership['role'] !== 'MEMBER') {
        regenerate_playgroup_code($playGroupId);
    }
    redirect('/playgroup.php?id=' . $playGroupId);
}

$group = get_playgroup($playGroupId);
$members = get_playgroup_members($playGroupId);
$collection = get_playgroup_collection($playGroupId);
$canManage = in_array($membership['role'], ['OWNER', 'ADMIN'], true);

$pageTitle = $group['name'] . ' - ' . SITE_NAME;
$activeNav = 'playgroups';
require __DIR__ . '/includes/header.php';
?>
<h1><?= h($group['name']) ?></h1>

<div style="display:flex;flex-wrap:wrap;align-items:center;gap:1rem;background:var(--surface);border:1px solid var(--border);border-radius:0.5rem;padding:1rem;margin:1rem 0 2rem;">
  <div>
    <p class="section-label" style="margin-bottom:0.2rem;">Invite code</p>
    <p style="font-family:monospace;font-size:1.1rem;letter-spacing:0.1em;color:var(--accent);margin:0;"><?= h($group['invite_code']) ?></p>
  </div>
  <?php if ($canManage): ?>
    <form method="post" onsubmit="return confirm('Are you sure you want to generate a new code? The old one will stop working.');">
      <input type="hidden" name="action" value="regenerate_code">
      <button type="submit" class="btn btn-secondary" style="font-size:0.75rem;">Generate new code</button>
    </form>
  <?php endif; ?>
  <div style="margin-left:auto;display:flex;flex-wrap:wrap;gap:0.5rem;">
    <?php foreach ($members as $m): ?>
      <span class="pill">
        <?= h($m['username']) ?>
        <?php if ($m['role'] !== 'MEMBER'): ?><span class="hint">(<?= h(strtolower($m['role'])) ?>)</span><?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>
</div>

<h2>Shared collection</h2>
<?php render_game_grid($collection, 'No one in this group has added any games yet.'); ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
