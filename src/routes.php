<?php

use Slim\Http\Request;
use Slim\Http\Response;

use App\Libraries\UtilitiesConnection;

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    //    return $this->renderer->render($response, 'index.phtml', $args);
    var_dump($request->getServerParams());
    $response->getBody()->write(" Hola ");
    return $response;
});


$app->post('/prueba', function (Request $request, Response $response, array $args){
       
    //prueba mensaje log
    $this->logger->info("Primera ruta creada con Slim => '/prueba' ");
    
    $sql = 'SELECT * FROM webservice.c2c_timetable WHERE sou_id=?;';
    
    $stmt = $this->db_webservice->Prepare($sql);
    $sou_id = 6;
    $stmt->bind_param("s", $sou_id);
    
    $stmt->execute();
    
    $result = $stmt->get_result();
       
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    return $response->withJson($data);
});     


$app->group('/RCable', function(){
    
    /*
     * Función para gestionar la info asociada a un evento C2C para LP de RCable. Devuelve un array JSON {result:boolean, message:objConexion}
     * -> JSON entrada:
     *  {
     *    "phone": "XXXXXX",
     *    "url": "XXXX",
     * }
     *   */
    $this->post('/incomingC2C', function (Request $request, Response $response, array $args){

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
                "leatype_id" => $type];

            if(array_key_exists('TEST', $data)){
                $datos["lea_status"] = "TEST";
            }

//            $formato = $this->utilities->get_format_prepared_sql($datos);
//            $query = $conn->insertStatement("leads", $datos, $formato);
            
            $format = UtilitiesConnection::getFormatPreparedSql($datos);
            $parametros = UtilitiesConnection::getArrayParametrosUpdate($datos, $format);

            $r = $db->insertStatementPrepared("leads", $parametros);
                    //selectPrepared($sql, $datos, $format);
            $sp = 'CALL wsInsertLead("'.$phone.'", "'.$query.'");';

            $result = $conn->Query($sp);

            if($conn->AffectedRows() > 0){
                //sustituir llamada
    //            exec("php /var/www/html/Leontel/RCable/sendLeadToLeontel.php >/dev/null 2>&1 &");

                exit(json_encode(['success'=> true, 'message'=> $result->fetch_assoc()]));

            }else{
                exit(json_encode(['success'=> false, 'message'=> 'KO-'.$conn->LastError()]));       
            }
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
    $this->post('/consultaTimetableC2C', function(Request $request, Response $response, array $args){

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
});


$app->post('/pruebaSelect', function (Request $request, Response $response, array $args){
       
    //prueba mensaje log
    $this->logger->info("Prueba consulta select libreria mysql prepared' ");
    
    $data = $request->getParsedBody();
    
    $datos = [
        0 => "LEONTEL",
        1 => NULL,
        2 => NULL,
        3 => $data['sou_id'],
    ];

    $query = "SELECT "
        . "l.lea_id,"
        . "l.lea_phone,"
        . "s.sou_idcrm 'source',"
        . "lt.leatype_idcrm 'type',"
        . "l.lea_name,"
        . "l.lea_mail,"
        . "l.lea_url,"
        . "l.lea_aux1,"
        . "l.lea_aux2 "
        . "FROM webservice.leads l "
        . "INNER JOIN webservice.sources s ON l.sou_id = s.sou_id "
        . "INNER JOIN webservice.leadtypes lt ON l.leatype_id = lt.leatype_id "
        . "WHERE "
        . "l.lea_destiny = ? "
        . "AND l.lea_extracted <=> ? "
        . "AND l.lea_status <=> ? "
        . "AND l.sou_id = ? "
        . "ORDER BY l.lea_id DESC LIMIT 1;";

    $format = UtilitiesConnection::getFormatPreparedSql($datos);

    $db = $this->db_webservice;
    $r = $db->selectPrepared($query, $datos, $format);    

    
    return $response->withJson($r);
}); 


$app->post('/pruebaUpdate', function(Request $request, Response $response, array $args){

    $datos = [
        "lea_extracted" => date("Y-m-d H:i:s"),
        "lea_crmid" => 9999,
        "lea_status" => "PRUEBA"
    ];
    
    $formato = UtilitiesConnection::getFormatPreparedSql($datos);
    $where = ["lea_id" => 118182];
    $formatoWhere = UtilitiesConnection::getFormatPreparedSql($where);
    $parametros = UtilitiesConnection::getArrayParametrosUpdate($datos, $formato, $where, $formatoWhere);

    $tabla = "webservice.leads";
    $db = $this->db_webservice;
    $result = $db->updatePrepared($tabla, $parametros);

    $r = json_decode($result);

    if($r->success){
        exit(json_encode(['success'=> true, 'message'=> $r->message]));
    }else{
        exit(json_encode(['success'=> false, 'message'=> $r->message]));
    }
});


$app->post('/pruebaInsert', function(Request $request, Response $response, Array $args){
    
    $data = $request->getParsedBody();
    $serverParams = $request->getServerParams();
    $url = "????";
    if(array_key_exists("HTTP_REFERER", $serverParams)){
        $url = $serverParams["HTTP_REFERER"];            
    }
    $ip = $serverParams["REMOTE_ADDR"];
    
    $datos = [
        "lea_phone" => $data["phone"],
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_destiny" => "TEST",
        "sou_id" => $data["sou_id"],
        "leatype_id" => 1
    ];

    $format = UtilitiesConnection::getFormatPreparedSql($datos);
    $parametros = UtilitiesConnection::getArrayParametrosUpdate($datos, $format);
    
    $db = $this->db_webservice;
    $z = $db->insertStatementPrepared("leads", $parametros);

    $result = $db->insertPrepared("leads", $parametros);
    
    $r = json_decode($result);
    
    if($r->success){
        exit(json_encode(['success'=> true, 'message'=> $r->message]));
    }else{
        exit(json_encode(['success'=> false, 'message'=> $r->message]));
    }
});


$app->post('/sendLeadToLeontel', function(Request $request, Response $response, array $args){
    
    if($request->isPost()){
        $data = $request->getParsedBody();
        $db = $this->db_webservice;
        App\Functions\LeadLeontel::sendLead($data, $db);
    }
    
});


// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
