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
    ],
    
    'db_webservice' => [
      "host" => "192.168.50.20",
      "dbname" => "webservice",
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
    
    'dev' => true,

    'leads_table' => 'webservice.leads',
  ],
];
