<div class="card">
  <div class="hd"><h2>My Shows</h2></div>
  <div class="bd"><div id="myshows-list">Loading...</div></div>
</div>

<script>
(async function() {
  const resp = await fetch('/api/myshows.php');
  const data = await resp.json();
  const container = document.getElementById('myshows-list');

  if (!data.ok) {
    container.innerHTML = '<p class="error">Failed to load.</p>';
    return;
  }

  if (!data.shows.length) {
    container.innerHTML = '<p>No tracked shows yet.</p>';
    return;
  }

  let html = '<div class="shows-grid">';
  data.shows.forEach(s => {
    html += `<div class="show-card">
      <img src="${s.poster || '/assets/placeholder.svg'}">
      <div class="title">${s.title}</div>
      <button class="untrack" data-id="${s.id}">Remove</button>
    </div>`;
  });
  html += '</div>';
  container.innerHTML = html;

  container.addEventListener('click', async e => {
    if (!e.target.classList.contains('untrack')) return;
    const id = e.target.dataset.id;
    const r = await fetch('/api/myshows.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({action:'remove', show_id:id})
    });
    const j = await r.json();
    if (j.ok) e.target.closest('.show-card').remove();
  });
})();
</script>
