<?php
 
return [
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'authUrl' => 'https://auth.cloud.ovh.net/v2.0',
        'username' => '',
        'password' => '',
        'tenantId' => '',
        'regions' => ['BHS1', 'DE1', 'GRA1', 'SBG1', 'UK1', 'WAW1']
    ],
    'view' => new \Slim\Views\Twig('../templates/')
];