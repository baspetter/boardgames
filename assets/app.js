function postJson(url, data) {
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
    body: JSON.stringify(data),
  }).then(async (r) => {
    const body = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(body.error || 'Something went wrong');
    return body;
  });
}

// Like postJson(), but sends a multipart/form-data body (for forms with a
// file input) instead of JSON.
function postForm(url, formData) {
  return fetch(url, {
    method: 'POST',
    headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
    body: formData,
  }).then(async (r) => {
    const body = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(body.error || 'Something went wrong');
    return body;
  });
}

function showError(elId, msg) {
  const el = document.getElementById(elId);
  if (el) {
    el.textContent = msg;
    el.classList.remove('hidden');
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
  // Generic modal open/close (data-open-modal="id" / data-close-modal)
  document.querySelectorAll('[data-open-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const modal = document.getElementById(btn.dataset.openModal);
      if (modal) modal.classList.remove('hidden');
    });
  });
  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-backdrop').classList.add('hidden');
    });
  });

  // User menu dropdown (top-right)
  const userMenuBtn = document.getElementById('user-menu-btn');
  const userMenuDropdown = document.getElementById('user-menu-dropdown');
  if (userMenuBtn && userMenuDropdown) {
    userMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = userMenuDropdown.classList.toggle('hidden') === false;
      userMenuBtn.setAttribute('aria-expanded', String(isOpen));
    });
    document.addEventListener('click', (e) => {
      if (!userMenuDropdown.classList.contains('hidden') && !userMenuDropdown.contains(e.target)) {
        userMenuDropdown.classList.add('hidden');
        userMenuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Tabs within the add-game modal
  document.querySelectorAll('.tab').forEach((tab) => {
    tab.addEventListener('click', () => {
      const group = tab.closest('.tabs');
      group.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const panelsRoot = group.parentElement;
      panelsRoot.querySelectorAll('.tab-panel').forEach((p) => p.classList.add('hidden'));
      const panel = document.getElementById('tab-' + tab.dataset.tab);
      if (panel) panel.classList.remove('hidden');

      // Clear any leftover error/result state from the other tab so it
      // doesn't stay visible after switching.
      panelsRoot.querySelectorAll('.tab-panel .error').forEach((el) => {
        el.textContent = '';
        el.classList.add('hidden');
      });
      const searchResults = panelsRoot.querySelector('#bgg-search-results');
      if (searchResults) searchResults.innerHTML = '';
    });
  });

  // BGG search-as-you-type
  const searchInput = document.getElementById('bgg-search-input');
  const resultsDiv = document.getElementById('bgg-search-results');
  let debounceTimer;
  if (searchInput && resultsDiv) {
    searchInput.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const q = searchInput.value.trim();
      if (q.length < 2) {
        resultsDiv.innerHTML = '';
        return;
      }
      debounceTimer = setTimeout(() => {
        resultsDiv.innerHTML = '<p class="hint">Searching...</p>';
        fetch('/api/search-bgg.php?q=' + encodeURIComponent(q))
          .then((r) => r.json())
          .then((data) => {
            if (data.error) {
              resultsDiv.innerHTML = '<p class="error">' + escapeHtml(data.error) + '</p>';
              return;
            }
            if (!data.length) {
              resultsDiv.innerHTML = '<p class="hint">No results found.</p>';
              return;
            }
            resultsDiv.innerHTML = '';
            data.forEach((item) => {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'search-result-item';
              btn.innerHTML =
                '<span>' + escapeHtml(item.name) +
                (item.yearPublished ? ' <span class="hint">(' + item.yearPublished + ')</span>' : '') +
                '</span><span style="color:var(--accent);font-size:0.75rem;">+ Add</span>';
              btn.addEventListener('click', () => {
                btn.disabled = true;
                postJson('/api/add-game.php', { bggId: item.bggId })
                  .then(() => window.location.reload())
                  .catch((err) => {
                    alert(err.message);
                    btn.disabled = false;
                  });
              });
              resultsDiv.appendChild(btn);
            });
          });
      }, 400);
    });
  }

  // Add by pasted link/ID
  const linkForm = document.getElementById('bgg-link-form');
  if (linkForm) {
    linkForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = document.getElementById('bgg-link-input');
      postJson('/api/add-game.php', { link: input.value })
        .then(() => window.location.reload())
        .catch((err) => showError('bgg-link-error', err.message));
    });
  }

  // Manual add form
  const manualForm = document.getElementById('manual-game-form');
  if (manualForm) {
    manualForm.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('/api/add-manual-game.php', new FormData(manualForm))
        .then(() => window.location.reload())
        .catch((err) => showError('manual-error', err.message));
    });
  }

  // Edit game form
  const editForm = document.getElementById('edit-game-form');
  if (editForm) {
    editForm.addEventListener('submit', (e) => {
      e.preventDefault();
      postForm('/api/edit-game.php', new FormData(editForm))
        .then(() => window.location.reload())
        .catch((err) => showError('edit-error', err.message));
    });
  }

  // Reorder categories: drag-and-drop list + save
  const categoryList = document.getElementById('category-order-list');
  if (categoryList) {
    let draggedEl = null;
    categoryList.querySelectorAll('li').forEach((li) => {
      li.addEventListener('dragstart', () => {
        draggedEl = li;
        li.classList.add('dragging');
      });
      li.addEventListener('dragend', () => {
        li.classList.remove('dragging');
        draggedEl = null;
      });
      li.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!draggedEl || draggedEl === li) return;
        const rect = li.getBoundingClientRect();
        const before = e.clientY - rect.top < rect.height / 2;
        categoryList.insertBefore(draggedEl, before ? li : li.nextSibling);
      });
    });

    const saveOrderBtn = document.getElementById('save-category-order');
    if (saveOrderBtn) {
      saveOrderBtn.addEventListener('click', () => {
        const order = [...categoryList.querySelectorAll('li')].map((li) => li.dataset.type);
        saveOrderBtn.disabled = true;
        postJson('/api/save-category-order.php', { order })
          .then(() => window.location.reload())
          .catch((err) => {
            showError('category-order-error', err.message);
            saveOrderBtn.disabled = false;
          });
      });
    }
  }

  // Simple one-off action buttons (remove from collection, add existing game
  // to my collection, refresh from BGG) via data attributes.
  document.querySelectorAll('[data-action]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.action;
      const gameId = btn.dataset.gameId;
      if (action === 'remove-game' && !confirm('Remove from your collection?')) {
        return;
      }
      const urls = {
        'remove-game': '/api/remove-game.php',
        'add-to-collection': '/api/add-game.php',
        'refresh-bgg': '/api/refresh-bgg.php',
      };
      const payloads = {
        'remove-game': { gameId },
        'add-to-collection': { gameId },
        'refresh-bgg': { gameId },
      };
      btn.disabled = true;
      postJson(urls[action], payloads[action])
        .then(() => window.location.reload())
        .catch((err) => {
          alert(err.message);
          btn.disabled = false;
        });
    });
  });
});
