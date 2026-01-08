<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end = $_GET['end'] ?? date('Y-m-d', strtotime('+30 days'));

$u = current_user();
$uid = $u ? (int)$u['id'] : null;
$trackedOnly = !empty($_GET['tracked_only']) && $uid;

$pdo = db();

try {
    if ($trackedOnly) {
        $sql = "
            SELECT e.id, e.event_type, e.start_utc, e.season, e.episode, e.episode_title, e.platform,
                   s.id AS show_id, s.title AS show_title, s.poster_url, s.show_type
            FROM events e
            JOIN shows s ON s.id = e.show_id
            JOIN user_tracked_shows uts ON uts.show_id = s.id AND uts.user_id = ?
            WHERE e.start_utc BETWEEN ? AND ?
            ORDER BY e.start_utc ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$uid, $start . ' 00:00:00', $end . ' 23:59:59']);
    } else {
        $sql = "
            SELECT e.id, e.event_type, e.start_utc, e.season, e.episode, e.episode_title, e.platform,
                   s.id AS show_id, s.title AS show_title, s.poster_url, s.show_type
            FROM events e
            JOIN shows s ON s.id = e.show_id
            WHERE e.start_utc BETWEEN ? AND ?
            ORDER BY e.start_utc ASC
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
    }

    $events = [];
    while ($row = $st->fetch()) {
        $events[] = [
            'id' => (int)$row['id'],
            'type' => $row['event_type'],
            'start' => $row['start_utc'],
            'season' => $row['season'] ? (int)$row['season'] : null,
            'episode' => $row['episode'] ? (int)$row['episode'] : null,
            'title' => $row['episode_title'],
            'platform' => $row['platform'],
            'show' => [
                'id' => (int)$row['show_id'],
                'title' => $row['show_title'],
                'poster' => $row['poster_url'],
                'type' => $row['show_type'],
            ],
        ];
    }

    echo json_encode(['ok' => true, 'events' => $events], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
