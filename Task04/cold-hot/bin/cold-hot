#!/usr/bin/env php
<?php
$loadPath1 = __DIR__ . '/../../../autoload.php';
$loadPath2 = __DIR__ . '/../vendor/autoload.php';

if (file_exists($loadPath1)) {
    include_once $loadPath1;
} else {
    include_once $loadPath2;
}

use ColdHot\GameController;

try {
    $controller = new GameController();
    $controller->run($argv);
} catch (Exception $e) {
    echo "Критическая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}