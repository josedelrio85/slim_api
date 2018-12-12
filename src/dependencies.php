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

$container['errorHandler'] = function($c){
    return new \App\Libraries\CustomError($c['logger']);
};

/* Settings DB Producción */
$container['settings_db_report_panel'] = function($c){
    $dbSettings = $c->get('settings')['db_report_panel'];
    return $dbSettings;
};

$container['settings_db_webservice'] = function($c){
    $dbSettings = $c->get('settings')['db_webservice'];   
    return $dbSettings;
};

$container['settings_db_crmti'] = function($c){
    $dbSettings = $c->get('settings')['db_crmti'];
    return $dbSettings;
};


/* Settings DB Desarrollo */
//$container['settings_db_report_panel_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_report_panel_dev'];
//    return $dbSettings;
//};
//
//$container['settings_db_webservice_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_webservice_dev'];   
//    return $dbSettings;
//};
//
//$container['settings_db_crmti_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_crmti_dev'];
//    return $dbSettings;
//};


/* Conexión DB Producción */
$container['db_report_panel'] = function($c){
    $dbSettings = $c->get('settings')['db_report_panel'];
    $a = new \App\Libraries\Connection($dbSettings);   
    return $a;
    
};

$container['db_webservice'] = function($c){
    $dbSettings = $c->get('settings')['db_webservice']; 
    $a = new \App\Libraries\Connection($dbSettings);    
    return $a;
};

$container['db_crmti'] = function($c){
    $dbSettings = $c->get('settings')['db_crmti'];
    $a = new \App\Libraries\Connection($dbSettings);    
    return $a;
};


/* Conexión DB Desarrollo */
//$container['db_report_panel_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_report_panel_dev'];
//    $a = new \App\Libraries\Connection($dbSettings);
//    return $a;
//};
//
//$container['db_webservice_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_webservice_dev'];
//    $a = new \App\Libraries\Connection($dbSettings);     
//    return $a;
//};
//
//$container['db_crmti_dev'] = function($c){
//    $dbSettings = $c->get('settings')['db_crmti_dev'];
//    $a = new \App\Libraries\Connection($dbSettings);
//    return $a;
//};



$container['funciones'] = function($c){
    return new \App\Functions\Functions($c->get('settings')['dev']);
};

$container['utilities'] = function(){
    return new \App\Functions\Utilities();
};

$container['sou_id_test'] = function($c){
    return $c->get('settings')['sou_id_test'];
};


$container['dev'] = function($c){
    return $c->get('settings')['dev'];
};
