<?php

if (!file_exists(__DIR__ . '/../src/config.php')) {
    if (is_writable(__DIR__ . '/../src/')) {
        copy(__DIR__ . '/../src/config.example.php', __DIR__ . '/../src/config.php');
    } else {
        die('Permission denied when creating config file: ' . realpath(__DIR__ . '/../src') . '/config.php');
    }
}

session_start();

require '../vendor/autoload.php';

$config = require '../src/config.php';

$app = new \Slim\App($config);
$container = $app->getContainer();

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

$container['view']->addExtension(new Slim\Twig\FlashMessages(
    $container['flash']
));

//debug delete later
$container['view']->addExtension(new \Twig\Extension\DebugExtension());

$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

$app->run();
