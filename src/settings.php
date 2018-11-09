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
            "host" => "192.168.50.20",
            "dbname" => "webservice",
            "user" => "webservice",
            "password" => "xp5555"
        ],
        
        'db_webservice_dev' => [
            "host" => "127.0.0.1",
            "dbname" => "webservice",
            "user" => "root",
            "password" => "root_bsc"
        ],
        
        'db_report_panel' => [
            "host" => "192.168.50.21",
            "dbname" => "report_panel",
            "user" => "admin",
            "password" => "dalema22"
        ],        
        
        'db_report_panel_dev' => [
            "host" => "127.0.0.1",            
            "dbname" => "report_panel",
            "user" => "root",
            "password" => "root_bsc"
        ],        
        
        'db_crmti' => [
            "host" => "192.168.50.109",
            "dbname" => "crmti",
            "user" => "crmti",
            "password" => "xp2222"
        ],        
        
        'db_crmti_dev' => [
            "host" => "127.0.0.1",
            "dbname" => "crmti",
            "user" => "root",
            "password" => "root_bsc"
        ],    

        'sou_id_test' => 15,
    ],
];
