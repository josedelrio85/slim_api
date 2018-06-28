<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

//$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
//    // Sample log message
//    $this->logger->info("Slim-Skeleton '/' route");
//
//    // Render index view
//    return $this->renderer->render($response, 'index.phtml', $args);
//});

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});


$app->get('/prueba', function (Request $request, Response $response, array $args){
    
    //prueba mensaje log
    $this->logger->info("Primera ruta creada con Slim => '/prueba' ");
    
    $sql = 'SELECT * FROM webservice.c2c_timetable WHERE sou_id=?;';
    
//    $result = $this->db_webservice->Query($sql);
//    
//    while($r = $result->fetch_assoc()){
//        var_dump($r);
//    }
    $stmt = $this->db_webservice->Prepare($sql);
    $sou_id = 6;
    $stmt->bind_param("s", $sou_id);
    
    $stmt->execute();
    
    $result = $stmt->get_result();
       
    $data = $result->fetch_all(MYSQLI_ASSOC);
    var_dump(json_encode($data));
   
    die();
    //echo json_encode($result->fetch_assoc());
});
