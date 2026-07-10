<?php
require_once __DIR__ . '/includes/playgroups.php';

$userId = require_login();
$groups = get_user_playgroups($userId);

$groupsData = [];
foreach ($groups as $g) {
    $groupsData[] = [
        'id' => (int) $g['id'],
        'name' => $g['name'],
        'members' => array_map(
            fn($m) => ['id' => (int) $m['user_id'], 'username' => $m['username']],
            get_playgroup_members((int) $g['id'])
        ),
    ];
}

$pageTitle = 'Gamenights - ' . SITE_NAME;
$activeNav = 'gamenights';
require __DIR__ . '/includes/header.php';
?>
<h1>Gamenights</h1>

<?php if (!$groupsData): ?>
  <p class="hint">You're not a member of a playgroup yet.</p>
<?php else: ?>
  <div id="gn-group-tabs" class="tabs" style="flex-wrap:wrap;"></div>
  <div id="gn-members-card" class="form-card" style="margin-left:0;max-width:none;"></div>
  <div id="gn-results"></div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const groups = <?= json_encode($groupsData) ?>;
  if (!groups.length) return;

  const tabsEl = document.getElementById('gn-group-tabs');
  const membersEl = document.getElementById('gn-members-card');
  const resultsEl = document.getElementById('gn-results');

  let currentGroupId = groups[0].id;
  let present = new Set();

  function renderTabs() {
    tabsEl.innerHTML = '';
    groups.forEach((g) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'tab' + (g.id === currentGroupId ? ' active' : '');
      btn.textContent = g.name;
      btn.addEventListener('click', () => {
        currentGroupId = g.id;
        present = new Set(g.members.map((m) => m.id));
        resultsEl.innerHTML = '';
        renderTabs();
        renderMembers();
      });
      tabsEl.appendChild(btn);
    });
  }

  function currentGroup() {
    return groups.find((g) => g.id === currentGroupId);
  }

  function renderMembers() {
    const group = currentGroup();
    membersEl.innerHTML = '';

    const label = document.createElement('p');
    label.className = 'hint';
    label.style.marginTop = '0';
    label.textContent = "Who's here tonight?";
    membersEl.appendChild(label);

    const pills = document.createElement('div');
    pills.className = 'tags';
    group.members.forEach((m) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pill' + (present.has(m.id) ? ' pill-accent' : '');
      btn.style.border = 'none';
      btn.style.cursor = 'pointer';
      btn.textContent = m.username;
      btn.addEventListener('click', () => {
        if (present.has(m.id)) present.delete(m.id); else present.add(m.id);
        renderMembers();
      });
      pills.appendChild(btn);
    });
    membersEl.appendChild(pills);

    const findBtn = document.createElement('button');
    findBtn.type = 'button';
    findBtn.className = 'btn btn-accent';
    findBtn.style.marginTop = '1rem';
    findBtn.textContent = 'Find games for ' + present.size + ' players';
    findBtn.disabled = present.size === 0;
    findBtn.addEventListener('click', findGames);
    membersEl.appendChild(findBtn);
  }

  function findGames() {
    resultsEl.innerHTML = '<p class="hint">Searching...</p>';
    postJson('/api/game-night.php', {
      playGroupId: currentGroupId,
      presentUserIds: Array.from(present),
    }).then((data) => {
      if (!data.suggestions.length) {
        resultsEl.innerHTML = '<p class="empty-state">No suitable games found for this many players.</p>';
        return;
      }
      const grid = document.createElement('div');
      grid.className = 'game-grid';
      data.suggestions.forEach((entry) => {
        const g = entry.game;
        const a = document.createElement('a');
        a.className = 'game-card';
        a.href = '/game.php?id=' + g.id;
        const cover = g.thumbnail || g.image;
        const owners = entry.owners.map((o) => o.username).join(', ');
        const sub = [g.year_published || '', owners].filter(Boolean).join(' · ');
        a.innerHTML =
          (cover ? '<img src="' + cover + '" alt="' + escapeHtml(g.name) + '" loading="lazy">' : '<div class="no-image">No image</div>') +
          (g.bgg_rating ? '<span class="rating-badge">' + Number(g.bgg_rating).toFixed(1) + '</span>' : '') +
          '<div class="card-overlay"><p class="card-title">' + escapeHtml(g.name) + '</p>' +
          (sub ? '<p class="card-sub">' + escapeHtml(sub) + '</p>' : '') + '</div>';
        grid.appendChild(a);
      });
      resultsEl.innerHTML = '';
      resultsEl.appendChild(grid);
    }).catch((err) => {
      resultsEl.innerHTML = '<p class="error">' + escapeHtml(err.message) + '</p>';
    });
  }

  present = new Set(currentGroup().members.map((m) => m.id));
  renderTabs();
  renderMembers();
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
