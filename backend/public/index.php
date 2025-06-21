<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/mikecao/flight/flight/Flight.php';

$config = require __DIR__ . '/../config.php';

Flight::register('db', 'PDO', [$config['db_dsn']]);

Flight::before('start', function () {
    header('Content-Type: application/json');
});

require_once __DIR__ . '/../routes/projects.php';
require_once __DIR__ . '/../routes/auth.php';

Flight::start();