<?php

namespace ColdHot;

use cli;

class GameView
{
    public function showWelcome(): void
    {
        cli\line("========================================");
        cli\line("       Welcome to Cold-Hot Game!        ");
        cli\line("========================================");
        cli\line("Try to guess a 3-digit number with unique digits.");
        cli\line("Hints: Холодно - digit not in number");
        cli\line("       Тепло - digit in wrong position");
        cli\line("       Горячо - digit in correct position");
        cli\line("");
    }

    public function askPlayerName(): string
    {
        return cli\prompt("Enter your name");
    }

    public function askForGuess(): string
    {
        return cli\prompt("Enter your 3-digit guess");
    }

    public function showHints(array $hints): void
    {
        cli\line("Hints: " . implode(' ', $hints));
    }

    public function showError(string $error): void
    {
        cli\err($error);
    }

    public function showWin(int $attempts): void
    {
        cli\line("");
        cli\line("🎉 Congratulations! You won in $attempts attempts!");
    }

    public function showLoss(string $secretNumber): void
    {
        cli\line("");
        cli\line("Game over! The secret number was: $secretNumber");
    }

    public function showGamesList(array $games): void
    {
        if (empty($games)) {
            cli\line("No games found.");
            return;
        }

        cli\line("========================================");
        cli\line("           Saved Games List             ");
        cli\line("========================================");

        $headers = ['ID', 'Date', 'Player', 'Number', 'Outcome', 'Attempts'];
        $rows = [];

        foreach ($games as $game) {
            $rows[] = [
                $game['id'],
                $game['date'],
                $game['player_name'],
                $game['secret_number'],
                $game['outcome'],
                count($game['attempts'])
            ];
        }

        $table = new \cli\Table();
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->display();
    }

    public function showReplay(array $gameData): void
    {
        cli\line("========================================");
        cli\line("           Game Replay                  ");
        cli\line("========================================");
        cli\line("Date: " . $gameData['date']);
        cli\line("Player: " . $gameData['player_name']);
        cli\line("Secret number: " . $gameData['secret_number']);
        cli\line("Outcome: " . $gameData['outcome']);
        cli\line("");
        cli\line("Attempts:");

        foreach ($gameData['attempts'] as $attempt) {
            cli\line(sprintf(
                "  %d. Guess: %s | Result: %s",
                $attempt['number'],
                $attempt['guess'],
                $attempt['result']
            ));
        }
    }

    public function askToContinue(): bool
    {
        $answer = cli\prompt("Do you want to continue? (yes/no)", 'yes');
        return strtolower($answer) === 'yes' || strtolower($answer) === 'y';
    }

    public function showGameNotFound(): void
    {
        cli\err("Game not found!");
    }
}