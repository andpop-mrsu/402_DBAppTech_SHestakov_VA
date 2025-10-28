<?php

namespace ColdHot;

class GameModel
{
    private string $secretNumber;
    private array $attempts = [];
    private bool $isWon = false;
    private string $playerName;
    private Database $database;
    private ?int $gameId = null;

    public function __construct()
    {
        $this->database = new Database();
    }

    public function startNewGame(string $playerName): void
    {
        $this->playerName = $playerName;
        $this->secretNumber = $this->generateSecretNumber();
        $this->attempts = [];
        $this->isWon = false;

        $this->gameId = $this->database->saveGame([
            'player_name' => $this->playerName,
            'secret_number' => $this->secretNumber,
            'outcome' => 'in_progress',
            'attempts' => []
        ]);
    }

    private function generateSecretNumber(): string
    {
        $digits = range(0, 9);
        shuffle($digits);
        if ($digits[0] == 0) {
            $temp = $digits[0];
            $digits[0] = $digits[1];
            $digits[1] = $temp;
        }
        return implode('', array_slice($digits, 0, 3));
    }

    public function makeGuess(string $guess): array
    {
        if (!preg_match('/^[0-9]{3}$/', $guess) || count(array_unique(str_split($guess))) != 3) {
            return ['error' => 'Invalid guess. Please enter a 3-digit number with unique digits.'];
        }

        $hints = $this->getHints($guess);
        $attemptNumber = count($this->attempts) + 1;

        $attempt = [
            'number' => $attemptNumber,
            'guess' => $guess,
            'result' => implode(' ', $hints)
        ];

        $this->attempts[] = $attempt;

        if ($guess === $this->secretNumber) {
            $this->isWon = true;
        }

        $this->database->updateGame($this->gameId, [
            'outcome' => $this->isWon ? 'won' : 'in_progress',
            'attempts' => $this->attempts
        ]);

        return [
            'hints' => $hints,
            'won' => $this->isWon,
            'attempt' => $attempt
        ];
    }

    private function getHints(string $guess): array
    {
        $hints = [];
        $secretDigits = str_split($this->secretNumber);
        $guessDigits = str_split($guess);

        for ($i = 0; $i < 3; $i++) {
            if ($guessDigits[$i] === $secretDigits[$i]) {
                $hints[] = 'Горячо';
            } elseif (in_array($guessDigits[$i], $secretDigits)) {
                $hints[] = 'Тепло';
            } else {
                $hints[] = 'Холодно';
            }
        }

        sort($hints);
        return $hints;
    }

    public function getSecretNumber(): string
    {
        return $this->secretNumber;
    }

    public function getAttempts(): array
    {
        return $this->attempts;
    }

    public function isGameWon(): bool
    {
        return $this->isWon;
    }

    public function getAllGames(): array
    {
        $games = $this->database->getAllGames();
        $result = [];

        foreach ($games as $game) {
            $attempts = isset($game['attempts']) ? json_decode($game['attempts'], true) : [];
            $result[] = [
                'id' => $game['id'],
                'date' => $game['date'],
                'player_name' => $game['player_name'],
                'secret_number' => $game['secret_number'],
                'outcome' => $game['outcome'],
                'attempts' => $attempts ?: []
            ];
        }

        return $result;
    }

    public function loadGame(int $id): ?array
    {
        $game = $this->database->getGameById($id);

        if (!$game) {
            return null;
        }

        $attempts = isset($game['attempts']) ? json_decode($game['attempts'], true) : [];

        return [
            'id' => $game['id'],
            'date' => $game['date'],
            'player_name' => $game['player_name'],
            'secret_number' => $game['secret_number'],
            'outcome' => $game['outcome'],
            'attempts' => $attempts ?: []
        ];
    }
}