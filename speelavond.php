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

$pageTitle = 'Speelavond - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<h1>Speelavond</h1>

<?php if (!$groupsData): ?>
  <p class="hint">Je bent nog geen lid van een playgroup.</p>
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
    label.textContent = 'Wie is er vanavond bij?';
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
    findBtn.textContent = 'Zoek spellen voor ' + present.size + ' spelers';
    findBtn.disabled = present.size === 0;
    findBtn.addEventListener('click', findGames);
    membersEl.appendChild(findBtn);
  }

  function findGames() {
    resultsEl.innerHTML = '<p class="hint">Zoeken...</p>';
    postJson('/api/game-night.php', {
      playGroupId: currentGroupId,
      presentUserIds: Array.from(present),
    }).then((data) => {
      if (!data.suggestions.length) {
        resultsEl.innerHTML = '<p class="empty-state">Geen geschikte spellen gevonden voor dit aantal spelers.</p>';
        return;
      }
      const grid = document.createElement('div');
      grid.className = 'game-grid';
      data.suggestions.forEach((entry) => {
        const g = entry.game;
        const a = document.createElement('a');
        a.className = 'game-card';
        a.href = '/spel.php?id=' + g.id;
        const cover = g.thumbnail || g.image;
        const owners = entry.owners.map((o) => o.username).join(', ');
        const sub = [g.year_published || '', owners].filter(Boolean).join(' · ');
        a.innerHTML =
          (cover ? '<img src="' + cover + '" alt="' + escapeHtml(g.name) + '" loading="lazy">' : '<div class="no-image">Geen afbeelding</div>') +
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
