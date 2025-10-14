<?php

namespace ColdHot;

class GameView
{
    public function showWelcome(): void
    {
        echo "=== Игра 'Холодно-Горячо' ===\n";
        echo "Угадайте трехзначное число с неповторяющимися цифрами!\n\n";
    }

    public function getPlayerName(): string
    {
        echo "Введите ваше имя: ";
        return trim(fgets(STDIN));
    }

    public function showGameStarted(): void
    {
        echo "Игра началась! Загадано трехзначное число.\n";
        echo "Введите 'quit' для выхода из игры.\n\n";
    }

    public function getPlayerGuess(int $attempt, int $maxAttempts): string
    {
        echo "Попытка {$attempt}/{$maxAttempts}. Введите вашу догадку: ";
        return trim(fgets(STDIN));
    }

    public function showHints(array $hints): void
    {
        echo "Подсказки: " . implode(' ', $hints) . "\n\n";
    }

    public function showInvalidInput(): void
    {
        echo "Ошибка! Введите трехзначное число с неповторяющимися цифрами.\n\n";
    }

    public function showWinMessage(int $attempts): void
    {
        echo "Поздравляем! Вы угадали число за {$attempts} попыток!\n";
    }

    public function showLoseMessage(string $secretNumber): void
    {
        echo "К сожалению, вы не угадали число. Загаданное число: {$secretNumber}\n";
    }

    public function showGameQuit(): void
    {
        echo "Игра прервана.\n";
    }

    public function showGamesList(array $games): void
    {
        if (empty($games)) {
            echo "Нет сохраненных игр.\n";
            return;
        }

        echo "=== Список всех игр ===\n";
        foreach ($games as $game) {
            $status = $game['is_won'] ? 'Победа' : 'Поражение';
            echo "ID: {$game['id']} | Игрок: {$game['player_name']} | ";
            echo "Число: {$game['secret_number']} | Результат: {$status} | ";
            echo "Дата: {$game['created_at']}\n";
        }
    }

    public function showReplay(array $gameData, array $attempts): void
    {
        echo "=== Повтор игры #{$gameData['id']} ===\n";
        echo "Игрок: {$gameData['player_name']}\n";
        echo "Загаданное число: {$gameData['secret_number']}\n";
        echo "Результат: " . ($gameData['is_won'] ? 'Победа' : 'Поражение') . "\n";
        echo "Дата: {$gameData['created_at']}\n\n";

        if (empty($attempts)) {
            echo "Нет попыток для этой игры.\n";
            return;
        }

        echo "Ход игры:\n";
        foreach ($attempts as $attempt) {
            echo "Попытка {$attempt['attempt_number']}: ";
            echo "Число: {$attempt['guess']} | ";
            echo "Подсказки: " . implode(' ', $attempt['hints']) . "\n";
        }
    }

    public function showGameNotFound(): void
    {
        echo "Ошибка: игра с указанным ID не найдена.\n";
    }

    public function showError(string $message): void
    {
        echo "Ошибка: {$message}\n";
    }

    public function showHelp(): void
    {
        echo "\nПравила игры:\n";
        echo "Угадайте случайное трехзначное число без повторяющихся цифр.\n";
        echo "Подсказки:\n";
        echo " - Горячо: цифра и её позиция совпадают\n";
        echo " - Тепло: цифра есть, но в другой позиции\n";
        echo " - Холодно: цифры нет\n";
        echo "Пример: секрет 456, ваш ввод 546 -> 'Горячо Тепло Тепло'\n";
    }
}