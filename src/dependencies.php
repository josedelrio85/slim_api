<?php

// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};


//prueba db -> llamar con $this->db_webservice
$container['db_webservice'] = function($c){
    $dbSettings = $c->get('settings')['db_webservice'];
    $server = $dbSettings['host'];
    $database = $dbSettings['dbname'];
    $username = $dbSettings['user'];
    $password = $dbSettings['password'];
    return new \App\Libraries\Connection($server, $username, $password, $database);    
};

$container['funciones'] = function($c){
    return new \App\Functions\Functions();
};
