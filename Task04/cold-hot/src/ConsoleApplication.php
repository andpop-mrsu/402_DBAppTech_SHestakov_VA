<?php

namespace ColdHot;

use ColdHot\GameModel;
use ColdHot\GameView;

class ConsoleApplication
{
    private $model;
    private $view;

    public function __construct(GameModel $model, GameView $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function runNewGame(): void
    {
        $this->view->showWelcome();

        $playerName = $this->view->getPlayerName();
        $secretNumber = $this->model->generateSecretNumber();
        $gameId = $this->model->startNewGame($playerName, $secretNumber);

        $this->view->showGameStarted();

        $attempts = 0;
        $maxAttempts = 10;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $guess = $this->view->getPlayerGuess($attempts, $maxAttempts);

            if ($guess === 'quit') {
                $this->model->finishGame($gameId, false);
                $this->view->showGameQuit();
                return;
            }

            if (!preg_match('/^\d{3}$/', $guess) || count(array_unique(str_split($guess))) !== 3) {
                $this->view->showInvalidInput();
                $attempts--;
                continue;
            }

            $result = $this->model->checkGuess($secretNumber, $guess);
            $this->model->saveAttempt($gameId, $attempts, $guess, $result['hints']);

            $this->view->showHints($result['hints']);

            if ($result['is_correct']) {
                $this->model->finishGame($gameId, true);
                $this->view->showWinMessage($attempts);
                return;
            }
        }

        $this->model->finishGame($gameId, false);
        $this->view->showLoseMessage($secretNumber);
    }

    public function showGamesList(): void
    {
        $games = $this->model->getAllGames();
        $this->view->showGamesList($games);
    }

    public function replayGame(int $gameId): void
    {
        $gameData = $this->model->getGameById($gameId);

        if (!$gameData) {
            $this->view->showGameNotFound();
            return;
        }

        $attempts = $this->model->getGameAttempts($gameId);
        $this->view->showReplay($gameData, $attempts);
    }

    public function showHelp(): void
    {
        $this->view->showHelp();
    }
}