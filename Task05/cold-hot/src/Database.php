<?php

namespace ColdHot;

use RedBeanPHP\R as R;

class Database
{
    public function __construct()
    {
        if (!R::testConnection()) {
            R::setup('sqlite:' . __DIR__ . '/../bin/cold-hot.db');
            R::useFeatureSet('novice/latest');
        }
    }

    public function saveGame(array $gameData): int
    {
        $game = R::dispense('game');
        $game->date = date('Y-m-d H:i:s');
        $game->player_name = $gameData['player_name'];
        $game->secret_number = $gameData['secret_number'];
        $game->outcome = $gameData['outcome'];
        $game->attempts = json_encode($gameData['attempts']);

        return R::store($game);
    }

    public function getAllGames(): array
    {
        return R::getAll('SELECT * FROM game ORDER BY date DESC');
    }

    public function getGameById(int $id): ?array
    {
        $game = R::load('game', $id);
        if (!$game->id) {
            return null;
        }
        return $game->export();
    }

    public function updateGame(int $id, array $gameData): void
    {
        $game = R::load('game', $id);
        if ($game->id) {
            $game->outcome = $gameData['outcome'];
            $game->attempts = json_encode($gameData['attempts']);
            R::store($game);
        }
    }
}