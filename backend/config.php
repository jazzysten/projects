<?php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

return [
    'db_dsn' => getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../database/projects.sqlite',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'some_default_secret'
];