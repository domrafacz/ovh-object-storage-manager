<?php
 
return [
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'authUrl' => 'https://auth.cloud.ovh.net/v3',
        'username' => '',
        'password' => '',
        'tenantId' => '',
        'domainName' => '',
        'regions' => ['BHS1', 'DE1', 'GRA1', 'SBG1', 'UK1', 'WAW1']
    ],
    'view' => new \Slim\Views\Twig('../templates/')
];