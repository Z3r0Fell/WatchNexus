<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$u = current_user();
if (!$u) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Login required']);
    exit;
}

$uid = (int)$u['id'];
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $st = $pdo->prepare("
            SELECT s.id, s.title, s.poster_url, s.show_type
            FROM user_tracked_shows uts
            JOIN shows s ON s.id = uts.show_id
            WHERE uts.user_id = ?
            ORDER BY s.title ASC
        ");
        $st->execute([$uid]);
        $shows = [];
        while ($row = $st->fetch()) {
            $shows[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'poster' => $row['poster_url'],
                'type' => $row['show_type'],
            ];
        }
        echo json_encode(['ok' => true, 'shows' => $shows]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $showId = (int)($data['show_id'] ?? 0);

    if (!in_array($action, ['add', 'remove'], true) || $showId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid request']);
        exit;
    }

    try {
        if ($action === 'add') {
            $st = $pdo->prepare("INSERT IGNORE INTO user_tracked_shows (user_id, show_id) VALUES (?, ?)");
            $st->execute([$uid, $showId]);
        } else {
            $st = $pdo->prepare("DELETE FROM user_tracked_shows WHERE user_id = ? AND show_id = ?");
            $st->execute([$uid, $showId]);
        }
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
