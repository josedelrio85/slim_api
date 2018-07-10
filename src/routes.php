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


/*
 * Función para gestionar la info asociada a un evento C2C para LP de RCable. Devuelve un array JSON {result:boolean, message:objConexion}
 * -> JSON entrada:
 *  {
 *    "phone": "XXXXXX",
 *    "url": "XXXX",
 * }
 *   */
$app->post('/incoming_C2C_RCable', function (Request $request, Response $response, array $args){
    
    $this->logger->info("WS incoming C2C RCable");
    
    if($request->isPost()){
        $data = $request->getParsedBody();
        $phone = $data['phone'];
        
        $serverParams = $request->getServerParams();
        $url = "????";
        if(array_key_exists("HTTP_REFERER", $serverParams)){
            $url = $serverParams["HTTP_REFERER"];            
        }
        $ip = $serverParams["REMOTE_ADDR"];
//        $url = $request->getServerParams('HTTP_REFERER');

        $conn = $this->db_webservice;
        
        $diaSemana = intval(date('N'));
        $horaActual = date('H:i');
        $datos = ["sou_id" => 6, "hora" => $horaActual, "num_dia" => $diaSemana];
        $consTimeTable = $this->funciones->consultaTimeTableC2C($datos,$conn);
        $type = 9;        
        if(is_array($consTimeTable)){
            $type = 1;
        }
        
        $datos = ["lea_phone" => $phone,
            "lea_url" => $url,
            "lea_ip" => $ip,
            "lea_destiny" => "TEST",
            "sou_id" => 5,
            "leatype_id" => $type,
            "lea_status" => "TEST"];
        
        $formato = $this->utilities->get_format_prepared_sql($datos);
        $conn->insert("leads",$datos,$formato);
        
        
//      if(array_key_exists('test', $data)){
//            $query = "INSERT INTO leads (lea_phone, lea_url, lea_ip, lea_destiny, sou_id, leatype_id, lea_status) VALUES ('{$phone}', '{$url}', '{$ip}', 'TEST', 5, {$type}, 'TEST');";
//	}else{
//            $query = "INSERT INTO leads (lea_phone, lea_url, lea_ip, lea_destiny, sou_id, leatype_id) VALUES ('{$phone}', '{$url}', '{$ip}', 'LEONTEL', 5, {$type});";
//	}
//	
//	$sp = 'CALL wsInsertLead("'.$phone.'", "'.$query.'");';
//
//	$result = $conn->Query($sp);
//
//        if($conn->AffectedRows() > 0){
//            //sustituir llamada
//            exec("php /var/www/html/Leontel/RCable/sendLeadToLeontel.php >/dev/null 2>&1 &");
//
//            exit(json_encode(['success'=> true, 'message'=> $result->fetch_assoc()]));
//
//        }else{
//            exit(json_encode(['success'=> false, 'message'=> 'KO-'.$conn->LastError()]));       
//        }
    }
});

/*
 * Función para obtener el horario de atención en C2C. Devuelve un array JSON {result:boolean, data:JSONArray}
 * -> JSON entrada:
 *  {
 *    "sou_id": "6",
 *    "num_dia": "2",
 *    "hora": "16:00"
 * }
 *   */
$app->post('/consultaTimetableC2C', function(Request $request, Response $response, array $args){
   
    if($request->isPost()){
        $data = $request->getParsedBody();
        
        $db = $this->db_webservice;
        $elements = $this->funciones->consultaTimeTableC2C($data,$db);
        if(is_array($elements)){
	    exit(json_encode(['success'=> true, 'data' => $elements]));	
	}else{
	    exit(json_encode(['success'=> false, 'data' => null]));	
	}
    } 
});


