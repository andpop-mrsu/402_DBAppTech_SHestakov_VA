<?php
// Определяем, это API-запрос или просто загрузка SPA
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Убираем query string и нормализуем
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

// Если запрашивается корень — отдаём SPA
if ($method === 'GET' && ($path === '/' || $path === '')) {
    // Отдаём статический HTML
    readfile(__DIR__ . '/index.html');
    exit;
}

// Если запрашивается статический ресурс — отдаём его
$staticPaths = ['/css/', '/js/', '/favicon.ico'];
foreach ($staticPaths as $prefix) {
    if (strpos($path, $prefix) === 0) {
        $filepath = __DIR__ . $path;
        if (is_file($filepath)) {
            // Устанавливаем MIME-тип (упрощённо)
            if (substr($path, -3) === '.js') {
                header('Content-Type: application/javascript');
            } elseif (substr($path, -4) === '.css') {
                header('Content-Type: text/css');
            } elseif (substr($path, -5) === '.html') {
                header('Content-Type: text/html; charset=utf-8');
            }
            readfile($filepath);
            exit;
        }
    }
}

// --- ДАЛЕЕ — ЛОГИКА API (Front Controller) ---

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$dbPath = __DIR__ . '/../db/games.db';
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) mkdir($dbDir, 0777, true);

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        player_name TEXT NOT NULL,
        secret_number TEXT NOT NULL,
        outcome TEXT NOT NULL DEFAULT 'in_progress',
        date TEXT NOT NULL DEFAULT (datetime('now')),
        attempts TEXT NOT NULL DEFAULT '[]'
    )");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка БД: ' . $e->getMessage()]);
    exit;
}

$segments = array_values(array_filter(explode('/', trim($path, '/'))));

// --- Маршрутизация ---
if ($method === 'GET' && count($segments) === 1 && $segments[0] === 'games') {
    $stmt = $pdo->query("SELECT * FROM games ORDER BY date DESC");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($games as &$g) $g['attempts'] = json_decode($g['attempts'], true) ?: [];
    echo json_encode($games, JSON_UNESCAPED_UNICODE);

} elseif ($method === 'GET' && count($segments) === 2 && $segments[0] === 'games') {
    $id = (int)$segments[1];
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game) { http_response_code(404); echo json_encode(['error' => 'Игра не найдена']); exit; }
    $game['attempts'] = json_decode($game['attempts'], true) ?: [];
    echo json_encode($game, JSON_UNESCAPED_UNICODE);

} elseif ($method === 'POST' && count($segments) === 1 && $segments[0] === 'games') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['player_name'])) { http_response_code(400); echo json_encode(['message' => 'Требуется player_name']); exit; }

    function generateNumber() {
        $digits = range(0, 9); shuffle($digits);
        if ($digits[0] === 0) [$digits[0], $digits[1]] = [$digits[1], $digits[0]];
        return implode('', array_slice($digits, 0, 3));
    }

    $secret = generateNumber();
    $stmt = $pdo->prepare("INSERT INTO games (player_name, secret_number) VALUES (?, ?)");
    $stmt->execute([$input['player_name'], $secret]);
    echo json_encode(['id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);

} elseif ($method === 'POST' && count($segments) === 2 && $segments[0] === 'step') {
    $id = (int)$segments[1];
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['guess']) || !preg_match('/^\d{3}$/', $input['guess']) || count(array_unique(str_split($input['guess']))) !== 3) {
        http_response_code(400); echo json_encode(['message' => 'Некорректный guess']); exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game || $game['outcome'] !== 'in_progress') { http_response_code(400); echo json_encode(['message' => 'Игра не найдена или завершена']); exit; }

    $secret = str_split($game['secret_number']);
    $guess = str_split($input['guess']);
    $hints = [];
    for ($i = 0; $i < 3; $i++) {
        if ($guess[$i] === $secret[$i]) $hints[] = 'hot';
        elseif (in_array($guess[$i], $secret)) $hints[] = 'warm';
        else $hints[] = 'cold';
    }
    sort($hints);

    $attempts = json_decode($game['attempts'], true) ?: [];
    $attempts[] = ['number' => count($attempts) + 1, 'guess' => $input['guess'], 'result' => $hints];
    $outcome = ($input['guess'] === $game['secret_number']) ? 'won' : (count($attempts) >= 10 ? 'lost' : 'in_progress');

    $pdo->prepare("UPDATE games SET attempts = ?, outcome = ? WHERE id = ?")
        ->execute([json_encode($attempts, JSON_UNESCAPED_UNICODE), $outcome, $id]);

    echo json_encode(['attempts' => $attempts, 'outcome' => $outcome], JSON_UNESCAPED_UNICODE);

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Маршрут не найден']);
}