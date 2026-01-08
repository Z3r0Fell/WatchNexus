<?php
declare(strict_types=1);

function demo_users(): array {
  // NOTE: For the scaffold we keep a few pre-canned demo accounts.
  // Password: DemoPass!1234  (13 chars, passes 12-16 policy)
  $hash = password_hash('DemoPass!1234', PASSWORD_ARGON2ID);
  return [
    'user@demo.local'  => ['id' => 101, 'email' => 'user@demo.local',  'roles' => ['user'],  'hash' => $hash, 'display' => 'Demo User'],
    'mod@demo.local'   => ['id' => 102, 'email' => 'mod@demo.local',   'roles' => ['mod'],   'hash' => $hash, 'display' => 'Demo Mod'],
    'admin@demo.local' => ['id' => 103, 'email' => 'admin@demo.local', 'roles' => ['admin'], 'hash' => $hash, 'display' => 'Demo Admin'],
  ];
}

function demo_shows(): array {
  // "Catalog" for demo events. In production this comes from DB/importers.
  return [
    ['id' => 1, 'title' => 'Neon Raiders',       'type' => 'tv'],
    ['id' => 2, 'title' => 'Chrono Bureau',      'type' => 'tv'],
    ['id' => 3, 'title' => 'Mainframe Mayhem',   'type' => 'anime'],
    ['id' => 4, 'title' => 'Grid Noir: Season',  'type' => 'tv'],
    ['id' => 5, 'title' => 'Signal Knights',     'type' => 'tv'],
    ['id' => 6, 'title' => 'Taelon Glassfiles',  'type' => 'tv'],
  ];
}

function demo_seed_events(string $monthYYYYMM): array {
  // Deterministic-ish demo events per month, to populate the calendar.
  // Produces a mix of airings + drops + specials.
  $shows = demo_shows();
  $out = [];

  $year = intval(substr($monthYYYYMM, 0, 4));
  $month = intval(substr($monthYYYYMM, 5, 2));
  $base = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), new DateTimeZone('UTC'));

  $pick = function(int $i) use ($shows) { return $shows[$i % count($shows)]; };

  for ($i=0; $i<18; $i++) {
    $dayOffset = ($i * 2 + 1) % 27;
    $show = $pick($i);
    $isDrop = ($i % 7 === 0);
    $isSpecial = ($i % 11 === 0);

    $type = $isDrop ? 'drop' : ($isSpecial ? 'special' : 'airing');
    $hour = $isDrop ? 0 : (18 + ($i % 5)); // drops at midnight
    $min = $isDrop ? 0 : ( ($i * 7) % 60 );

    $dt = $base->modify('+' . $dayOffset . ' days')->setTime($hour, $min);

    $season = 1 + intdiv($i, 10);
    $ep = 1 + ($i % 10);

    $out[] = [
      'id' => 2000 + $i,
      'type' => $type,
      'showId' => $show['id'],
      'showTitle' => $show['title'],
      'season' => $season,
      'episode' => $ep,
      'episodeTitle' => $type === 'drop' ? 'Full Batch Release' : ('Episode ' . $ep),
      'startUtc' => $dt->format('Y-m-d\TH:i:s\Z'),
      'platform' => $type === 'drop' ? 'Streaming' : null
    ];
  }
  return $out;
}

function demo_user_state_init(): void {
  if (!isset($_SESSION['demo'])) {
    $_SESSION['demo'] = [
      'tracked' => [1,3], // default tracked show IDs
      'integrations' => [
        'trakt' => ['enabled' => 0],
        'seedr' => ['enabled' => 0, 'variant' => 'auto'],
        'jackett' => ['enabled' => 0],
        'prowlarr' => ['enabled' => 0],
      ]
    ];
  }
}
