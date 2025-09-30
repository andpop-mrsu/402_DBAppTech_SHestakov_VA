<?php
namespace Baklaniso\ColdHot\Controller;

use Baklaniso\ColdHot\View\View;
use Baklaniso\ColdHot\Game\Game;

/**
 * startGame
 *
 * Запускает программу: сначала меню, затем игру, если выбран пункт 1.
 */
function startGame(): void
{
    $view = new View();

    // Отображаем меню и ждём выбор
    $choice = $view->renderMenuAndGetChoice();

    switch ($choice) {
        case 1:
            playColdHot($view);
            break;
        case 2:
            $view->displayScoreTable();
            break;
        case 3:
            $view->displaySettings();
            break;
        case 4:
            $view->displayRules();
            break;
        case 5:
            $view->displayExit();
            exit(0);
        default:
            $view->displayError("Нет такого пункта меню.");
    }
}

/**
 * Основная игра "Холодно-Горячо".
 */
function playColdHot(View $view): void
{
    $game = new Game();
    $secret = $game->generateSecret();
    $attempts = 0;

    echo "\nНачинаем игру! Угадайте трёхзначное число.\n";

    while (true) {
        $attempts++;
        $guess = $view->promptForGuess($attempts);

        if (!preg_match('/^\d{3}$/', $guess)) {
            $view->displayError("Введите ровно 3 цифры (например: 123)");
            $attempts--;
            continue;
        }

        $hints = $game->evaluateGuess($secret, $guess);
        $sortedHints = Game::sortHintsAlphabetically($hints);

        $view->displayHints($sortedHints);

        $allHot = count(array_filter($sortedHints, fn($h) => $h === 'Горячо')) === 3;
        if ($allHot) {
            $view->displayWin($attempts, $secret);
            break;
        }
    }
}
