<?php

namespace ColdHot;

class GameController
{
    private GameModel $model;
    private GameView $view;
    private const MAX_ATTEMPTS = 10;

    public function __construct()
    {
        $this->model = new GameModel();
        $this->view = new GameView();
    }

    public function startNewGame(): void
    {
        $this->view->showWelcome();
        $playerName = $this->view->askPlayerName();

        $this->model->startNewGame($playerName);

        $attempts = 0;
        $won = false;

        while ($attempts < self::MAX_ATTEMPTS && !$won) {
            $guess = $this->view->askForGuess();
            $result = $this->model->makeGuess($guess);

            if (isset($result['error'])) {
                $this->view->showError($result['error']);
                continue;
            }

            $attempts++;
            $this->view->showHints($result['hints']);

            if ($result['won']) {
                $won = true;
                $this->view->showWin($attempts);
            } elseif ($attempts >= self::MAX_ATTEMPTS) {
                $this->view->showLoss($this->model->getSecretNumber());
            }
        }
    }

    public function listGames(): void
    {
        $games = $this->model->getAllGames();
        $this->view->showGamesList($games);
    }

    public function replayGame(int $id): void
    {
        $gameData = $this->model->loadGame($id);

        if (!$gameData) {
            $this->view->showGameNotFound();
            return;
        }

        $this->view->showReplay($gameData);
    }
}