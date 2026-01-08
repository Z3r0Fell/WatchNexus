<div class="card">
  <div class="hd">
    <h2>Browse Shows</h2>
    <div class="spacer"></div>
    <span class="small muted" id="show-count">Loading...</span>
  </div>
  <div class="bd">
    
    <!-- Search & Filters -->
    <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;align-items:center;">
      <input 
        type="text" 
        id="search-input" 
        placeholder="Search shows..." 
        style="flex:1;min-width:250px;padding:10px 16px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;font-size:0.95rem;"
      >
      
      <select id="status-filter" style="padding:10px 16px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;cursor:pointer;">
        <option value="">All Status</option>
        <option value="running">Running</option>
        <option value="ended">Ended</option>
        <option value="in development">In Development</option>
      </select>
      
      <select id="sort-by" style="padding:10px 16px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;cursor:pointer;">
        <option value="title_asc">Title A-Z</option>
        <option value="title_desc">Title Z-A</option>
        <option value="premiered_desc">Newest First</option>
        <option value="premiered_asc">Oldest First</option>
      </select>
      
      <select id="view-mode" style="padding:10px 16px;border-radius:8px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:inherit;cursor:pointer;">
        <option value="grid">🔲 Grid</option>
        <option value="list">📋 List</option>
      </select>
      
      <button id="clear-filters" class="btn small">Clear Filters</button>
    </div>
    
    <!-- Results Container -->
    <div id="browse-results">
      <p class="small muted">Loading shows...</p>
    </div>
    
    <!-- Pagination -->
    <div id="pagination" style="margin-top:24px;display:flex;justify-content:center;align-items:center;gap:12px;"></div>
    
  </div>
</div>

<style>
.show-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
}

.show-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.show-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.3);
  border-color: rgba(255,255,255,0.2);
}

.show-poster {
  width: 100%;
  aspect-ratio: 2/3;
  object-fit: cover;
  background: rgba(255,255,255,0.05);
}

.show-info {
  padding: 12px;
}

.show-title {
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 6px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.show-meta {
  font-size: 0.75rem;
  opacity: 0.7;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.show-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.show-row {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
  transition: background 0.2s;
  cursor: pointer;
}

.show-row:hover {
  background: rgba(255,255,255,0.06);
}

.show-row-poster {
  width: 80px;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  background: rgba(255,255,255,0.05);
}

.show-row-info {
  flex: 1;
}

.show-row-title {
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 8px;
}

.show-row-description {
  font-size: 0.85rem;
  opacity: 0.8;
  margin-bottom: 10px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.show-row-meta {
  font-size: 0.8rem;
  opacity: 0.7;
}

.status-badge {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-running { background: rgba(0,255,0,0.2); color: #0f0; }
.status-ended { background: rgba(255,0,0,0.2); color: #f44; }
.status-development { background: rgba(255,165,0,0.2); color: #fa0; }

.track-btn {
  padding: 6px 12px;
  font-size: 0.8rem;
  white-space: nowrap;
}

.tracked-badge {
  padding: 6px 12px;
  font-size: 0.8rem;
  background: rgba(0,255,0,0.2);
  color: #0f0;
  border-radius: 6px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.03);
  color: inherit;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: rgba(255,255,255,0.08);
}

.pagination-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 0.85rem;
  opacity: 0.8;
}
</style>

<script>
(async function() {
  console.log('Browse page loaded');
  
  let allShows = [];
  let filteredShows = [];
  let trackedIds = new Set();
  let currentPage = 1;
  const perPage = 24;
  
  const searchInput = document.getElementById('search-input');
  const statusFilter = document.getElementById('status-filter');
  const sortBy = document.getElementById('sort-by');
  const viewMode = document.getElementById('view-mode');
  const resultsContainer = document.getElementById('browse-results');
  const pagination = document.getElementById('pagination');
  const showCount = document.getElementById('show-count');
  const clearFiltersBtn = document.getElementById('clear-filters');
  
  // Load shows and tracked IDs
  async function init() {
    try {
      // Load tracked shows first
      <?php if (current_user()): ?>
      const trackResp = await fetch('/api/myshows.php');
      if (trackResp.ok) {
        const trackData = await trackResp.json();
        if (trackData.ok && trackData.shows) {
          trackedIds = new Set(trackData.shows.map(s => s.id));
        }
      }
      <?php endif; ?>
      
      // Load all shows
      const resp = await fetch('/api/shows_browse.php');
      const data = await resp.json();
      
      if (!data.ok) {
        resultsContainer.innerHTML = '<p class="error">Failed to load shows: ' + (data.error || 'Unknown error') + '</p>';
        return;
      }
      
      allShows = data.shows || [];
      showCount.textContent = allShows.length + ' shows';
      
      applyFilters();
      
    } catch (err) {
      console.error('Browse init error:', err);
      resultsContainer.innerHTML = '<p class="error">Error loading shows: ' + err.message + '</p>';
    }
  }
  
  // Apply filters and sorting
  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value.toLowerCase();
    const sort = sortBy.value;
    
    // Filter
    filteredShows = allShows.filter(show => {
      const titleMatch = !searchTerm || show.title.toLowerCase().includes(searchTerm);
      const statusMatch = !status || (show.status && show.status.toLowerCase() === status);
      return titleMatch && statusMatch;
    });
    
    // Sort
    filteredShows.sort((a, b) => {
      switch(sort) {
        case 'title_asc':
          return a.title.localeCompare(b.title);
        case 'title_desc':
          return b.title.localeCompare(a.title);
        case 'premiered_desc':
          return (b.premiered || '').localeCompare(a.premiered || '');
        case 'premiered_asc':
          return (a.premiered || '').localeCompare(b.premiered || '');
        default:
          return 0;
      }
    });
    
    currentPage = 1;
    renderResults();
  }
  
  // Render results
  function renderResults() {
    if (filteredShows.length === 0) {
      resultsContainer.innerHTML = '<p>No shows found. Try different filters or <a href="?page=mod">import TV schedule</a>.</p>';
      pagination.innerHTML = '';
      return;
    }
    
    const mode = viewMode.value;
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    const pageShows = filteredShows.slice(start, end);
    
    if (mode === 'grid') {
      renderGrid(pageShows);
    } else {
      renderList(pageShows);
    }
    
    renderPagination();
  }
  
  // Render grid view
  function renderGrid(shows) {
    let html = '<div class="show-grid">';
    
    shows.forEach(show => {
      const isTracked = trackedIds.has(show.id);
      const statusClass = 'status-' + (show.status || 'unknown').toLowerCase().replace(' ', '-');
      
      html += `
        <div class="show-card" data-show-id="${show.id}">
          <img src="${show.poster_url || '/assets/placeholder.png'}" class="show-poster" alt="${show.title}">
          <div class="show-info">
            <div class="show-title" title="${show.title}">${show.title}</div>
            <div class="show-meta">
              <span class="status-badge ${statusClass}">${show.status || 'Unknown'}</span>
              <span>${show.premiered ? new Date(show.premiered).getFullYear() : '—'}</span>
            </div>
            ${<?php echo current_user() ? 'true' : 'false'; ?> ? 
              (isTracked ? 
                '<span class="tracked-badge">✓ Tracked</span>' :
                '<button class="btn small track-btn" data-show-id="' + show.id + '" data-show-title="' + show.title + '">+ Track</button>'
              ) : ''
            }
          </div>
        </div>
      `;
    });
    
    html += '</div>';
    resultsContainer.innerHTML = html;
  }
  
  // Render list view
  function renderList(shows) {
    let html = '<div class="show-list">';
    
    shows.forEach(show => {
      const isTracked = trackedIds.has(show.id);
      const statusClass = 'status-' + (show.status || 'unknown').toLowerCase().replace(' ', '-');
      const description = show.description ? 
        show.description.replace(/<[^>]*>/g, '').substring(0, 200) + '...' : 
        'No description available.';
      
      html += `
        <div class="show-row" data-show-id="${show.id}">
          <img src="${show.poster_url || '/assets/placeholder.png'}" class="show-row-poster" alt="${show.title}">
          <div class="show-row-info">
            <div class="show-row-title">${show.title}</div>
            <div class="show-row-description">${description}</div>
            <div class="show-row-meta">
              <span class="status-badge ${statusClass}">${show.status || 'Unknown'}</span>
              <span style="margin-left:12px;">Premiered: ${show.premiered || 'Unknown'}</span>
            </div>
          </div>
          ${<?php echo current_user() ? 'true' : 'false'; ?> ? 
            '<div style="display:flex;align-items:center;">' +
            (isTracked ? 
              '<span class="tracked-badge">✓ Tracked</span>' :
              '<button class="btn small track-btn" data-show-id="' + show.id + '" data-show-title="' + show.title + '">+ Track Show</button>'
            ) +
            '</div>' : ''
          }
        </div>
      `;
    });
    
    html += '</div>';
    resultsContainer.innerHTML = html;
  }
  
  // Render pagination
  function renderPagination() {
    const totalPages = Math.ceil(filteredShows.length / perPage);
    
    if (totalPages <= 1) {
      pagination.innerHTML = '';
      return;
    }
    
    let html = '';
    html += `<button class="pagination-btn" onclick="browsePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>◀ Previous</button>`;
    html += `<span class="pagination-info">Page ${currentPage} of ${totalPages} (${filteredShows.length} shows)</span>`;
    html += `<button class="pagination-btn" onclick="browsePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next ▶</button>`;
    
    pagination.innerHTML = html;
  }
  
  // Change page
  window.browsePage = function(page) {
    const totalPages = Math.ceil(filteredShows.length / perPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderResults();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
  
  // Track show handler
  document.addEventListener('click', async function(e) {
    const trackBtn = e.target.closest('.track-btn');
    if (!trackBtn) return;
    
    e.stopPropagation();
    const showId = parseInt(trackBtn.dataset.showId);
    const showTitle = trackBtn.dataset.showTitle;
    
    trackBtn.disabled = true;
    trackBtn.textContent = 'Tracking...';
    
    try {
      const resp = await fetch('/api/myshows.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', show_id: showId })
      });
      
      const data = await resp.json();
      
      if (data.ok) {
        trackedIds.add(showId);
        trackBtn.outerHTML = '<span class="tracked-badge">✓ Tracked</span>';
      } else {
        trackBtn.disabled = false;
        trackBtn.textContent = '+ Track';
        alert('Failed to track show: ' + (data.error || 'Unknown error'));
      }
    } catch (err) {
      console.error('Track error:', err);
      trackBtn.disabled = false;
      trackBtn.textContent = '+ Track';
      alert('Error tracking show: ' + err.message);
    }
  });
  
  // Event listeners
  searchInput.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
  sortBy.addEventListener('change', applyFilters);
  viewMode.addEventListener('change', renderResults);
  
  clearFiltersBtn.addEventListener('click', function() {
    searchInput.value = '';
    statusFilter.value = '';
    sortBy.value = 'title_asc';
    applyFilters();
  });
  
  // Initialize
  init();
  
})();
</script>
