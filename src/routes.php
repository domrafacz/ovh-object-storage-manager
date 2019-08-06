<?php

use Slim\App;

return function (App $app) {

    $app->get('/', 'SwiftManager\Controllers\MainController:index');

    $app->any('/list-containers/{region}', 'SwiftManager\Controllers\MainController:listContainers');
    $app->any('/list-objects/{region}/{container}', 'SwiftManager\Controllers\MainController:listObjects');
    $app->any('/list-objects/{region}/{container}/allow-cors', 'SwiftManager\Controllers\MainController:allowCors');

    $app->any('/tempurl/{region}/{container}/{object}[/{expires}]', 'SwiftManager\Controllers\MainController:tempUrl');

    $app->any('/upload-object/{region}/{container}', 'SwiftManager\Controllers\MainController:uploadObject');
    $app->any('/upload-object-success', 'SwiftManager\Controllers\MainController:uploadObjectSuccess');

    $app->any('/temp-url-settings/{region}', 'SwiftManager\Controllers\MainController:tempUrlSettings');

    $app->get('/test', 'SwiftManager\Controllers\MainController:test');
};
