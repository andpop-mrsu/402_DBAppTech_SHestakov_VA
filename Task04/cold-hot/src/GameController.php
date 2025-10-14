<?php

namespace ColdHot;

use ColdHot\ConsoleApplication;
use ColdHot\Database;
use ColdHot\GameModel;
use ColdHot\GameView;

class GameController
{
    public function run(array $argv): void
    {
        $database = new Database(__DIR__ . '"/../bin/cold-hot.db');
        $model = new GameModel($database);
        $view = new GameView();
        $app = new ConsoleApplication($model, $view);

        $option = $argv[1] ?? '-n';

        switch ($option) {
            case '--new':
            case '-n':
                $app->runNewGame();
                break;
            case '--list':
            case '-l':
                $app->showGamesList();
                break;
            case '--replay':
            case '-r':
                $gameId = $argv[2] ?? null;
                if ($gameId && is_numeric($gameId)) {
                    $app->replayGame((int)$gameId);
                } else {
                    $view->showError("Please specify valid game ID");
                }
                break;
            case '--help':
            case '-h':
                $app->showHelp();
                break;
            default:
                $app->runNewGame();
                break;
        }
    }
}