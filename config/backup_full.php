<?php

$config = require __DIR__.'/backup.php';
$mediaPath = storage_path('app/public');
$mediaPaths = array_values(array_unique(array_filter([$mediaPath, realpath($mediaPath)])));

$config['backup']['source']['files']['include'] = array_values(array_unique(array_merge(
    [base_path()],
    $mediaPaths,
)));

$config['backup']['source']['files']['exclude'] = [
    base_path('.env'),
    base_path('.env.backup'),
    base_path('.git'),
    base_path('vendor'),
    base_path('node_modules'),
    base_path('public/build'),
    base_path('public/hot'),
    base_path('bootstrap/cache'),
    base_path('storage/app/backups'),
    base_path('storage/app/backup-temp'),
    base_path('storage/framework'),
    base_path('storage/logs'),
    base_path('storage/media-library/temp'),
    base_path('storage/pail'),
    base_path('releases'),
    base_path('.phpunit.result.cache'),
    base_path('.phpunit.cache'),
    base_path('auth.json'),
];

$config['backup']['source']['files']['relative_path'] = base_path();
$config['backup']['verify_backup'] = false;

return $config;
