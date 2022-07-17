<?php
 
return [
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'authUrl' => 'https://auth.cloud.ovh.net/v3',
        'username' => '',
        'password' => '',
        'tenantId' => '',
        'tenantName' => '',
        'regions' => ['BHS', 'DE', 'GRA', 'SBG', 'UK', 'WAW']
    ],
    'view' => new \Slim\Views\Twig('../templates/')
];