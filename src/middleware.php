<?php
// Application middleware

//Middleware
$config=[
//    'origin'=>'*.example.com', // allow all hosts ending example.com
    'origin' => '*',
    'allowMethods'  => 'GET, POST, OPTIONS',
    'allowHeaders'  => ['Accept', 'Accept-Language', 'Authorization', 'Content-Type','DNT','Keep-Alive','User-Agent','X-Requested-With','Cache-Control','Origin'],
];
//Estable criterios para permitir acceder a una request
$app->add(new \Bairwell\MiddlewareCors($config));

//$app->add(new App\Middleware\ExampleMiddleware());

//Forzar a que el content-type sea application/json
$app->add(new App\Middleware\JSONContentMiddleware());

$app->add(new App\Middleware\ParsedBodyMiddleware());
