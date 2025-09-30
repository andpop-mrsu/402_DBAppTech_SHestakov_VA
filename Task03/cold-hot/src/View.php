<?php
namespace Baklaniso\ColdHot\View;

/**
 * View — отвечает за вывод и ввод данных пользователя.
 */
class View
{
    /**
     * Показываем стартовое меню и получаем выбор пользователя
     */
    public function renderMenuAndGetChoice(): int
    {
        echo "=== Игра 'Холодно-Горячо' ===\n";
        echo "Меню:\n";
        echo "1) Начать новую игру\n";
        echo "2) Таблица рекордов\n";
        echo "3) Настройки сложности\n";
        echo "4) Правила игры\n";
        echo "5) Выход\n";
        echo "Выберите пункт меню (1-5): ";

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return (int)trim($line);
    }

    public function promptForGuess(int $attemptNumber): string
    {
        echo "Ход #{$attemptNumber}. Введите 3-значное число: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return $line === false ? '' : trim($line);
    }

    public function displayError(string $message): void
    {
        echo "Ошибка: {$message}\n\n";
    }

    public function displayHints(array $hints): void
    {
        echo implode(' ', $hints) . "\n\n";
    }

    public function displayWin(int $attempts, string $secret): void
    {
        echo "Поздравляем! Вы угадали число {$secret} за {$attempts} попыток.\n";
    }

    public function displayScoreTable(): void
    {
        echo "\n[Таблица рекордов пока не реализована]\n";
    }

    public function displaySettings(): void
    {
        echo "\n[Настройки сложности пока не реализованы]\n";
    }

    public function displayRules(): void
    {
        echo "\nПравила игры:\n";
        echo "Угадайте случайное трехзначное число без повторяющихся цифр.\n";
        echo "Подсказки:\n";
        echo " - Горячо: цифра и её позиция совпадают\n";
        echo " - Тепло: цифра есть, но в другой позиции\n";
        echo " - Холодно: цифры нет\n";
        echo "Пример: секрет 456, ваш ввод 546 -> 'Горячо Тепло Тепло'\n";
    }

    public function displayExit(): void
    {
        echo "Выход из игры. До встречи!\n";
    }
}
