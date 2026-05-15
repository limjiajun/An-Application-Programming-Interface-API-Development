<?php

return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 5432,
        'dbname' => 'sbe3603_assignment1',
        'user' => 'postgres',
        'password' => 'your_password_here',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'api' => [
        'max_limit' => 500,
        'default_limit' => 50,
        'storage_srid' => 29873,
        'output_srid' => 4326,
    ],
];

