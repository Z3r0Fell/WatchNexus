<?php
declare(strict_types=1);
$u = current_user();
$shows = demo_shows();
demo_user_state_init();
$tracked = $_SESSION['demo']['tracked'] ?? [];
?>
<div class="grid2">
  <div class="card">
    <div class="hd"><h2>Browse</h2><div class="spacer"></div><span class="small">Catalog (demo)</span></div>
    <div class="bd">
      <p class="small m0">This will become the provider-backed catalog browser (TVMaze/AniList/etc). For now it shows a tiny demo catalog.</p>
      <div class="mt16">
        <input class="input" id="browseSearch" placeholder="Search catalog…">
      </div>
      <div class="mt16" id="browseList">
        <?php foreach ($shows as $s): $isTracked = in_array($s['id'], $tracked, true); ?>
          <div class="item" style="grid-template-columns: 1fr 120px;">
            <div class="main">
              <div class="title"><?= htmlspecialchars($s['title']) ?></div>
              <div class="sub">Type: <?= htmlspecialchars($s['type']) ?> • ID: <?= (int)$s['id'] ?></div>
            </div>
            <div style="justify-self:end">
              <?php if ($u): ?>
                <button class="btn <?= $isTracked ? '' : 'primary' ?> trackBtn" data-id="<?= (int)$s['id'] ?>" type="button">
                  <?= $isTracked ? 'Tracked' : 'Track' ?>
                </button>
              <?php else: ?>
                <a class="btn primary" href="?page=login">Login to track</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="hd"><h2>Why Browse exists</h2><div class="spacer"></div></div>
    <div class="bd">
      <p class="m0">Browse is the <strong>catalog</strong>. My Shows is the <strong>personal tracking list</strong>.</p>
      <p class="small mt12">This separation prevents the v1 mapping madness where tracked shows drifted because catalog IDs changed.</p>

      <div class="mt16">
        <div class="label">Planned tools</div>
        <ul class="small" style="margin:0; padding-left:18px; line-height:1.5">
          <li>Search/filter by provider, country, status, genre</li>
          <li>Stable provider IDs (tvmaze_id / anilist_id / trakt_id / thetvdb_id)</li>
          <li>“Add to My Shows” uses stable provider mapping, not auto-increment IDs</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  window.WNX = window.WNX || {};
  window.WNX.enableTrackButtons = true;
</script>
