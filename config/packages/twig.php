<?php

//use Matican\Settings;
//
//if (isset($_SERVER['SERVER_NAME'])) {
//    Settings::set('APPLICATION_DOMAIN', 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']);
//}
$container->loadFromExtension('twig', [
    // ...
    'globals' => [
        'application_domain' => \Matican\Settings::get('APPLICATION_DOMAIN')
    ],
]);