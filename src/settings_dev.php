<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        'db_webservice' => [
            "host" => "127.0.0.1",
            "dbname" => "webservice",
            "user" => "root",
            "password" => "root_bsc"
        ],
        
        'db_report_panel' => [
            "host" => "127.0.0.1",            
            "dbname" => "report_panel",
            "user" => "root",
            "password" => "root_bsc"
        ],             
        
        'db_crmti' => [
            "host" => "127.0.0.1",
            "dbname" => "crmti",
            "user" => "root",
            "password" => "root_bsc"
        ],         

        'sou_id_test' => 15,
    ],
];
