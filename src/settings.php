<?php
return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::ERROR,
            // 'level' => \Monolog\Logger::DEBUG,
        ],
        
        'db_webservice' => [
            "host" => "192.168.50.20",
//            "host" => "localhost",
            "dbname" => "webservice",
//            "user" => "root",
//            "password" => "dalema22"
            "user" => "admin",
            "password" => "dalema22"
//            "user" => "webservice",
//            "password" => "xp5555"
//            en prod usar root dal y localhost
        ],
        
        'db_report_panel' => [
            "host" => "192.168.50.21",
            "dbname" => "report_panel",
            "user" => "admin",
            "password" => "dalema22"
        ],           
        
        'db_crmti' => [
            "host" => "192.168.50.109",
            "dbname" => "crmti",
            "user" => "crmti",
            "password" => "xp2222"
        ],         

        'sou_id_test' => 15,
        
        'dev' => false,
    ],
];
