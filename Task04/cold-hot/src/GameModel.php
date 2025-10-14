<?php

namespace ColdHot;

use ColdHot\Database;

class GameModel
{
    private $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function generateSecretNumber(): string
    {
        $digits = range(0, 9);
        shuffle($digits);
        return implode('', array_slice($digits, 0, 3));
    }

    public function startNewGame(string $playerName, string $secretNumber): int
    {
        $stmt = $this->db->getPdo()->prepare("
            INSERT INTO games (player_name, secret_number) 
            VALUES (?, ?)
        ");
        $stmt->execute([$playerName, $secretNumber]);
        return $this->db->getPdo()->lastInsertId();
    }

    public function checkGuess(string $secret, string $guess): array
    {
        $hints = [];
        $isCorrect = ($secret === $guess);

        // Check for exact matches (hot)
        for ($i = 0; $i < 3; $i++) {
            if ($secret[$i] === $guess[$i]) {
                $hints[] = 'Горячо';
            }
        }

        // Check for correct digits in wrong positions (warm)
        for ($i = 0; $i < 3; $i++) {
            if ($secret[$i] !== $guess[$i] && strpos($secret, $guess[$i]) !== false) {
                $hints[] = 'Тепло';
            }
        }

        // Add cold if no matches
        if (empty($hints)) {
            $hints[] = 'Холодно';
        }

        // Sort hints alphabetically
        sort($hints);

        return [
            'hints' => $hints,
            'is_correct' => $isCorrect
        ];
    }

    public function saveAttempt(int $gameId, int $attemptNumber, string $guess, array $hints): void
    {
        $stmt = $this->db->getPdo()->prepare("
            INSERT INTO attempts (game_id, attempt_number, guess, hints) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$gameId, $attemptNumber, $guess, json_encode($hints, JSON_UNESCAPED_UNICODE)]);
    }

    public function finishGame(int $gameId, bool $isWon): void
    {
        $stmt = $this->db->getPdo()->prepare("
            UPDATE games 
            SET is_completed = 1, is_won = ? 
            WHERE id = ?
        ");
        $stmt->execute([$isWon ? 1 : 0, $gameId]);
    }

    public function getAllGames(): array
    {
        $stmt = $this->db->getPdo()->query("
            SELECT id, player_name, secret_number, is_won, created_at 
            FROM games 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getGameById(int $gameId): ?array
    {
        $stmt = $this->db->getPdo()->prepare("
            SELECT id, player_name, secret_number, is_won, created_at 
            FROM games 
            WHERE id = ?
        ");
        $stmt->execute([$gameId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getGameAttempts(int $gameId): array
    {
        $stmt = $this->db->getPdo()->prepare("
            SELECT attempt_number, guess, hints 
            FROM attempts 
            WHERE game_id = ? 
            ORDER BY attempt_number
        ");
        $stmt->execute([$gameId]);

        $attempts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decode JSON hints
        foreach ($attempts as &$attempt) {
            $attempt['hints'] = json_decode($attempt['hints'], true);
        }

        return $attempts;
    }
}