<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware(); // парсит JSON-тело POST-запросов

// Вспомогательная функция для JSON-ответа
function json_response(Response $response, array $data, int $status = 200): Response
{
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus($status);
}

// Роут: GET / → перенаправление на SPA
$app->get('/', function (Request $request, Response $response) {
    return $response->withStatus(302)->withHeader('Location', '/index.html');
});

// Роут: GET /games — список всех игр
$app->get('/games', function (Request $request, Response $response) {
    $dbPath = __DIR__ . '/../db/games.db';
    if (!file_exists($dbPath)) {
        return json_response($response, ['error' => 'База данных не создана'], 500);
    }

    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query("SELECT * FROM games ORDER BY date DESC");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($games as &$game) {
            $game['attempts'] = json_decode($game['attempts'], true) ?: [];
        }
        return json_response($response, $games);
    } catch (Exception $e) {
        return json_response($response, ['error' => 'Ошибка при загрузке игр'], 500);
    }
});

// Роут: GET /games/{id} — конкретная игра
$app->get('/games/{id}', function (Request $request, Response $response, array $args) {
    $id = (int) $args['id'];
    $dbPath = __DIR__ . '/../db/games.db';
    if (!file_exists($dbPath)) {
        return json_response($response, ['error' => 'Игра не найдена'], 404);
    }

    try {
        $pdo = new PDO("sqlite:$dbPath");
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) {
            return json_response($response, ['error' => 'Игра не найдена'], 404);
        }
        $game['attempts'] = json_decode($game['attempts'], true) ?: [];
        return json_response($response, $game);
    } catch (Exception $e) {
        return json_response($response, ['error' => 'Ошибка при загрузке игры'], 500);
    }
});

// Роут: POST /games — создать новую игру
$app->post('/games', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    if (empty($data['player_name']) || !is_string($data['player_name'])) {
        return json_response($response, ['message' => 'Требуется поле player_name'], 400);
    }

    // Генерация трёхзначного числа с уникальными цифрами
    $digits = range(0, 9);
    shuffle($digits);
    if ($digits[0] === 0) {
        [$digits[0], $digits[1]] = [$digits[1], $digits[0]];
    }
    $secret = implode('', array_slice($digits, 0, 3));

    $dbPath = __DIR__ . '/../db/games.db';
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }

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

        $stmt = $pdo->prepare("INSERT INTO games (player_name, secret_number) VALUES (?, ?)");
        $stmt->execute([$data['player_name'], $secret]);
        $id = (int) $pdo->lastInsertId();

        return json_response($response, ['id' => $id], 201);
    } catch (Exception $e) {
        return json_response($response, ['error' => 'Ошибка создания игры'], 500);
    }
});

// Роут: POST /step/{id} — сделать ход
$app->post('/step/{id}', function (Request $request, Response $response, array $args) {
    $id = (int) $args['id'];
    $data = $request->getParsedBody();
    $guess = $data['guess'] ?? '';

    if (!is_string($guess) || !preg_match('/^\d{3}$/', $guess) || count(array_unique(str_split($guess))) !== 3) {
        return json_response($response, ['message' => 'Некорректный ход: требуется 3 уникальные цифры'], 400);
    }

    $dbPath = __DIR__ . '/../db/games.db';
    if (!file_exists($dbPath)) {
        return json_response($response, ['message' => 'Игра не найдена'], 404);
    }

    try {
        $pdo = new PDO("sqlite:$dbPath");
        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game || $game['outcome'] !== 'in_progress') {
            return json_response($response, ['message' => 'Игра завершена или не найдена'], 400);
        }

        $secretDigits = str_split($game['secret_number']);
        $guessDigits = str_split($guess);
        $hints = [];

        for ($i = 0; $i < 3; $i++) {
            if ($guessDigits[$i] === $secretDigits[$i]) {
                $hints[] = 'hot';
            } elseif (in_array($guessDigits[$i], $secretDigits)) {
                $hints[] = 'warm';
            } else {
                $hints[] = 'cold';
            }
        }
        sort($hints);

        $attempts = json_decode($game['attempts'], true) ?: [];
        $attempts[] = [
            'number' => count($attempts) + 1,
            'guess' => $guess,
            'result' => $hints
        ];

        $outcome = 'in_progress';
        if ($guess === $game['secret_number']) {
            $outcome = 'won';
        } elseif (count($attempts) >= 10) {
            $outcome = 'lost';
        }

        $pdo->prepare("UPDATE games SET attempts = ?, outcome = ? WHERE id = ?")
            ->execute([json_encode($attempts, JSON_UNESCAPED_UNICODE), $outcome, $id]);

        return json_response($response, [
            'attempts' => $attempts,
            'outcome' => $outcome
        ]);
    } catch (Exception $e) {
        return json_response($response, ['error' => 'Ошибка выполнения хода'], 500);
    }
});

// Запуск приложения
$app->run();