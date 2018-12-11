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


$app->post('/testconexion', function (Request $request, Response $response, array $args){
    
    $salida = array();
    
//    $mysqli = $this->db_crmti_dev;
    $mysqli = $this->db_crmti;
    
    $sql = "select * from crmti.lea_leads where lea_id = 266189;";
    $r = $mysqli->Query($sql);
    $res = $r->fetch_assoc();
    array_push($salida,$res);

    
//    $mysqli2 = $this->db_webservice_dev;
    $mysqli2 = $this->db_webservice;


    $sql2 = "select * from webservice.leads where lea_id = 119854;";
    $r2 = $mysqli2->Query($sql2);
    $res2 = $r2->fetch_assoc();
    array_push($salida,$res2);
    
    $aa = $this->settings_db_crmti;
    $a = new \App\Libraries\Connection($aa);   

    $sql3 = "select * from crmti.lea_leads where lea_id = 265336;";
    $rr = $a->Query($sql3);
    $res3 = $rr->fetch_assoc();
    array_push($salida,$res3);
    
    return $response->withJson($salida);
});


$app->post('/testconexionIntensivo', function (Request $request, Response $response, array $args){
    
    $salida = array();
    
   
    $sou_id = $this->sou_id_test;
//    $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice_dev);
    $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);
    
    array_push($salida,$sou_idcrm);

    
//    $params_crmti_rp = $this->settings_db_crmti_dev;
    $params_crmti_rp = $this->settings_db_crmti;

    $db_crmti_rp = new \App\Libraries\Connection($params_crmti_rp);    
    $datosAsnef = $request->getParsedBody();
    $rAsnef = $this->funciones->checkAsnefCreditea($datosAsnef, $db_crmti_rp);
    $r = json_decode($rAsnef);

    array_push($salida,$r);
   
    $datos = [
        "sou_id" => $sou_id,
        "leatype_id" => 1,
        "lea_phone" => $data["phone"],
        "lea_aux1" => $data["documento"]
    ];
        
    $datos["lea_destiny"] = 'LEONTEL';
    
//    $set = $this->settings_db_webservice_dev;
    $set = $this->settings_db_webservice;
    $mysqli2 = new \App\Libraries\Connection($set);   

    $z = $this->funciones->prepareAndSendLeadLeontel($datos,$mysqli2, null, false);
    
    array_push($salida,json_decode($z));
    
    return $response->withJson($salida);
});


$app->group('/test', function(){
    
    $this->post('/testException', function (Request $request, Response $response, array $args){
   
        try {
    //        $o = new \App\Libraries\ProbarExcepcion(ProbarExcepcion::THROW_CUSTOM);
            $o = new ProbarExcepcion(ProbarExcepcion::THROW_DEFAULT);
        } catch (MiExcepción $e) {      // Será atrapada
            echo "Atrapada mi excepción\n", $e;
            $e->funcionPersonalizada();
        } catch (Exception $e) {        // Skipped
            echo "Atrapada la Excepción Predeterminada\n", $e;
        }

        // Continuar la ejecución
        var_dump($o); // Null
        echo "\n\n";
    });
    
    $this->post('/testWS_dev', function (Request $request, Response $response, array $args){
        $salida = array();
        $db = $this->db_webservice;

        $datos = [ "lea_id" => "119707" ];
        $query = "SELECT * FROM webservice.leads where lea_id = ? ;";
        $resultado = $db->selectPrepared($query, $datos);
        array_push($salida, array("select" => $resultado));

        $datosWs ['lea_phone'] = "666666666";
        $query = "INSERT INTO webservice.leads(lea_phone) values (".$datosWs["lea_phone"].");";
        $sp = 'CALL wsInsertLead("'.$datos["lea_phone"].'", "'.$query.'");';

        $result = $db->Query($sp);           

        if($db->AffectedRows() > 0){
            $r = $result->fetch_assoc();   
            $db->NextResult();
            $result->close();
        }else{
            $r = array(["result" => false, "message" => "puta mirda"]);
        }    
        array_push($salida, array("insert" => $r));

        $where = ["lea_id" => $datos["lea_id"]];
        $datosUpd = [
            "lea_extracted" => date("Y-m-d H:i:s"),
            "lea_crmid" => 9999,
            "lea_status" => "PRUEBA"
        ];
        $parametros = UtilitiesConnection::getParametros($datosUpd, $where);

        $tabla = "webservice.leads";
        $result = $db->updatePrepared($tabla, $parametros);

        $ru = json_decode($result);
        array_push($salida, array("update" => $ru));

        return $response->withJson($salida);
    });

    $this->post('/testRP_dev', function (Request $request, Response $response, array $args){
        $salida = array();
    
        $db = $this->db_report_panel;


        $datos = [ "sou_id" => "23" ];
        $query = "SELECT * FROM report_panel.c2c_timetable where sou_id = ? ;";
        $resultado = $db->selectPrepared($query, $datos);
        array_push($salida, array("select" => $resultado));

        $datos = [
            "sou_id" => 99,
            "num_dia" => 2,
            "laborable" => 1,
            "h_ini" => "09:00",
            "h_fin" => "15:00",
            "tmt_activo" => 1
        ];

        $parametros = UtilitiesConnection::getParametros($datos,null);
        $z = $db->insertStatementPrepared("c2c_timetable", $parametros);
        $result = $db->insertPrepared("c2c_timetable", $parametros);    

        array_push($salida, array("insert" => $z));
        array_push($salida, array("insert2" => $result));
    
        $where = ["sou_id" => $datos["sou_id"]];
        $datosUpd = [
            "h_ini" => "09:30",
            "tmt_activo" => 0
        ];
        $parametros = UtilitiesConnection::getParametros($datosUpd, $where);

        $tabla = "report_panel.c2c_timetable";
        $result = $db->updatePrepared($tabla, $parametros);
        $ru = json_decode($result);
        array_push($salida, array("update" => $ru));

        return $response->withJson($salida);
    });

    $this->post('/testCRMTI_dev', function (Request $request, Response $response, array $args){
        $salida = array();

        $db = $this->db_crmti;

        $datos = [ "lea_id" => "119707" ];
        $query = "SELECT * FROM crmti.lea_leads where lea_id = ? ;";
        $resultado = $db->selectPrepared($query, $datos);
        array_push($salida, array("select" => $resultado));

        $where = ["lea_id" => $datos["lea_id"]];
        $datosUpd = [
            "lea_scheduled" => date("Y-m-d H:i:s"),
            "wsid" => 9999
        ];
        $parametros = UtilitiesConnection::getParametros($datosUpd, $where);

        $tabla = "crmti.lea_leads";
        $result = $db->updatePrepared($tabla, $parametros);
        $ru = json_decode($result);
        array_push($salida, array("update" => $ru));

        return $response->withJson($salida); 
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
    $this->post('/consultaTimeTableC2C', function(Request $request, Response $response, array $args){
        
        if($request->isPost()){
        
            $data = $request->getParsedBody();

            $elements = $this->funciones->consultaTimeTableC2C($data,$this->db_report_panel);
            if(is_array($elements)){
                exit(json_encode(['success'=> true, 'data' => $elements]));	
            }else{
                exit(json_encode(['success'=> false, 'data' => null]));	
            }
        } 
    });
    
    $this->post('/getSouIdcrm', function(Request $request, Response $response, array $args){
        if($request->isPost()){
            
            $sou_id = $this->sou_id_test;
            $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);
           
            $body = $response->getBody();
            $body->write($sou_idcrm);
            return $response;   //Debe devolver 23 para sou_id 15
        }
    });
            
    $this->post('/horarioEntradaLeads', function(Request $request, Response $response, array $args){
        
        if($request->isPost()){

            $sou_id = $this->sou_id_test;
            $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);

            //si está en horario, devuelve true, si no false
            $salida = $this->funciones->horarioEntradaLeads($sou_idcrm, $this->db_report_panel);

            
            return $response->withJson($salida);
        }
    });
    
    $this->post('/checkAsnefCreditea', function(Request $request, Response $response, array $args){
        
        $data = $request->getParsedBody();
        
        $datosAsnef = [
            "sou_id" => $data["sou_id"],
            "documento" => $data["documento"],
            "phone" => $data["phone"]                
        ];
        
        // Devuelve array ['success'=> true, 'message' => 'KO-notValid'] si no pasa validacion, ['success'=> false, 'message' => true] si pasa validacion
        $rAsnef = $this->funciones->checkAsnefCreditea($datosAsnef, $this->db_crmti);
        return $response->withJson(json_decode($rAsnef));
    });
    
    $this->post('/checkAsnefDoctorDinero', function(Request $request, Response $response, array $args){
        
        $data = $request->getParsedBody();
        
        $datosAsnef = [
            "sou_id" => $data["sou_id"],
            "documento" => $data["documento"],
            "phone" => $data["phone"]                
        ];
        
        // Devuelve array ['success'=> true, 'message' => 'KO-notValid'] si no pasa validacion, 
        // ['success'=> true, 'message' => true] si pasa validacion
        // ['result'=> true, 'data' => 'KO-paramsNeeded'] si faltan parámetros
        $rAsnef = $this->funciones->checkAsnefCreditea($datosAsnef, $this->db_crmti);
        return $response->withJson(json_decode($rAsnef));
    });
    
    $this->post('/sendLeadToLeontel', function(Request $request, Response $response, array $args){
        
        $data = $request->getParsedBody();
        if(!empty($data["sou_id"])){
            $sou_id = $data["sou_id"];
        }else{
            $sou_id = $this->sou_id_test;
        }
        $datos = [
            "lea_phone" => "666666664",
            "lea_url" => "http://test.bysidecar.gal",
            "lea_ip" => "127.0.0.1",
            "lea_destiny" => "LEONTEL",
            "sou_id" => $sou_id,
            "leatype_id" => 1
        ];

        $parametros = UtilitiesConnection::getParametros($datos,null);

        $db = $this->db_webservice;
        $z = $db->insertStatementPrepared("leads", $parametros);
        $result = $db->insertPrepared("leads", $parametros);
        $r = json_decode($result);
    
        if($r->success){
            
            $data = ["sou_id" => $datos["sou_id"], "leatype_id" => $datos["leatype_id"]];
            
            //Devuelve ['success'=> $res->success, 'message'=> $res->message] si hay resultados para la consulta
            // ['success'=> $res->success, 'message'=> 'No results'] => si no hay resultados para la consulta
            $res = App\Functions\LeadLeontel::sendLead($data, $db);  
            
            $rs = json_decode($res,true);
            
            return $response->withJson($rs);
        }else{
            //Devuelve el error en la inserción
            return  $response->withJson(array("succes" => $r->succes, "message" => $r->message));
        }        
    });
    
    $this->post('/sendLeadToLeontelIntesivo', function(Request $request, Response $response, array $args){
        
//        $db = $this->db_webservice_dev;
        $db = $this->db_webservice;

        $sqlSources = "SELECT * FROM webservice.sources;";
        $datosSource = [];
        $rsou = $db->selectPrepared($sqlSources, $datosSource);    
        $salida = array();
        
        if(is_array($rsou)){
            foreach($rsou as $k => $source){
                
                $datos = [
                    "lea_phone" => "666666664",
                    "lea_url" => "http://test.bysidecar.gal",
                    "lea_ip" => "127.0.0.1",
                    "lea_destiny" => "LEONTEL",
                    "sou_id" => $source->sou_id,
                    "leatype_id" => 1
                ];
                
                $parametros = UtilitiesConnection::getParametros($datos,null);

                $z = $db->insertStatementPrepared("leads", $parametros);
                $result = $db->insertPrepared("leads", $parametros);
                $r = json_decode($result);

                if($r->success){

                    $data = ["sou_id" => $datos["sou_id"], "leatype_id" => $datos["leatype_id"]];
                    $res = App\Functions\LeadLeontel::sendLead($data, $db);  

                    $rs = json_decode($res,true);
                    $rs['query'] = $z;

                    array_push($salida,$rs);
                }else{
                    array_push($salida,array("succes" => $r->succes, "message" => $r->message, 'query' => $z));
                }
            }
        }
        return $response->withJson($salida);
    });
    
    $this->post('/sendLeadLeontelPagoRecurrente', function(Request $request, Response $response, array $args){
        
        $db = $this->db_crmti;

        
//        $salida = App\Functions\LeadLeontel::sendLeadLeontelPagoRecurrente("leads", $db);  
        $salida = App\Functions\LeadLeontel::sendLeadLeontelPagoRecurrente("cliente", $db);  

                
        return $response->withJson(json_decode($salida, true));
    });
    
    $this->post('/testTest', function(Request $request, Response $response, array $args){
        
//        $db = $this->db_crmti_dev;
        $db = $this->db_crmti;
        $a = $this->funciones->test($db);
        
        var_dump($a);
    });
    
    $this->post('/testLeadStatusProd', function(Request $request, Response $response, array $args){
               
        $a = App\Functions\LeadLeontel::testLeadStatus();
        
        return $response->withJson($a);
    });
    
});


/* Tests funciones interacción BD */
$app->get('/pruebaSelect/{sou_id}', function (Request $request, Response $response, array $args){
       
    //prueba mensaje log
    $this->logger->info("Prueba consulta select libreria mysql prepared' ");
    
    //$data = $request->getParsedBody();
//    $data = $request->getQueryParams();
     $data = $args;
//    $headers = $request->getHeaders();
//    $headerValueArray = $request->getHeader('Accept');
    
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

    $db = $this->db_webservice;
    $r = $db->selectPrepared($query, $datos);    
    
    return $response->withJson($r);
}); 
$app->post('/pruebaUpdate', function(Request $request, Response $response, array $args){

    $datos = [
        "lea_extracted" => date("Y-m-d H:i:s"),
        "lea_crmid" => 9999,
        "lea_status" => "PRUEBA"
    ];
    
    $where = ["lea_id" => 119735];
    $parametros = UtilitiesConnection::getParametros($datos, $where);

    $tabla = "webservice.leads";
//    $db = $this->db_webservice_dev;
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

    $parametros = UtilitiesConnection::getParametros($datos,null);
    
//    $db = $this->db_webservice_dev;
    $db = $this->db_webservice;
    $z = $db->insertStatementPrepared("leads", $parametros);

    $result = $db->insertPrepared("leads", $parametros);
    
    $r = json_decode($result);
    
    if($r->success){
        $response->withJson(['success'=> true, 'message'=> $r->message]);
    }else{
        $response->withJson(['success'=> false, 'message'=> $r->message]);
    }
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

            list($url, $ip) = $this->funciones->getServerParams($request);
            
            $sou_id = 5;
            $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);

            $leatype_id = $this->funciones->horarioEntradaLeads($sou_idcrm, $this->db_report_panel) ? 1 : 9;
                        
            $datos = [
                "lea_phone" => $phone,
                "lea_url" => $url,
                "lea_ip" => $ip,
                "lea_destiny" => "LEONTEL",
                "sou_id" => $sou_id,
                "leatype_id" => $leatype_id];
            
            $resultLeontel = json_decode($this->funciones->prepareAndSendLeadLeontel($datos, $this->db_webservice), true);

            return $response->withJson($resultLeontel);
        }
    });
});





$app->group('/creditea', function(){
    /*
     * Función para almacenar la información de aquellos leads considerados como no válidos en la LP
     * (marcaron check Cliente y/o Asnef)
     * * @JSON entrada:
     *  {
     *    "utm_source": "XXXXXX",
     *    "phone": "XXXX",
     *    "documento": "XXXXXXX",
     * 	  "cantidadsolicitada": XXXXXXX,
     *    "motivo": "XXXXXXX"
     * }
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/almacenaLeadNoValido', function(Request $request, Response $response, array $args){
        
        $this->logger->info("WS C2C Creditea E2E almacena lead no válido.");

        if($request->isPost()){

            $data = $request->getParsedBody();
            
            list($url, $ip) = $this->funciones->getServerParams($request);
            
            $sou_id = 9;

            $leatype_id = 1;
            
            $datos = [
                "lea_destiny" => 'LEONTEL',
                "sou_id" => $sou_id,
                "leatype_id" => $leatype_id,
                "utm_source" => $data["utm_source"],
                "lea_phone" => $data["phone"],
                "lea_url" => $url,
                "lea_ip" => $ip,
                "lea_aux1" => $data["documento"],
                "lea_aux2" => $data["cantidadsolicitada"],
                "lea_aux3" => $data["motivo"]                
            ];
            
            $db = $this->db_webservice;            
            $parametros = UtilitiesConnection::getParametros($datos,null); 
            $salida = json_decode($db->insertPrepared("leads", $parametros),true);;

            return $response->withJson($salida);
        }        
    });
    
    /*
     * Proceso de validación de lead valido cuando en la LP se marcan como "NO" casillas "Cliente" y "Asnef"
     * params:
     * @JSON entrada:
     *  {
     *    "utm_source": "XXXXXX",
     *    "phone": "XXXX",
     *    "documento": "XXXXXXX",
     * 	  "cantidadsolicitada": XXXXXXX,
     *    "motivo": "XXXXXXX"
     * }
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/validaDniTelf', function(Request $request, Response $response, array $args){
        
        $this->logger->info("WS para validacion datos LP Creditea.");
        
        if($request->isPost()){
            $data = $request->getParsedBody();
            
            list($url, $ip) = $this->funciones->getServerParams($request);
            
            $sou_id = 9;
            $leatype_id = 1;
            
            $datosAsnef = [
                "sou_id" => $sou_id,
                "documento" => $data["documento"],
                "phone" => $data["phone"]                
            ];
            
            $db_crmti = $this->db_crmti;

            $rAsnef = json_decode($this->funciones->checkAsnefCreditea($datosAsnef, $db_crmti));
            
            if(!$rAsnef->success){
                
                $datos = [
                    "lea_destiny" => 'LEONTEL',
                    "sou_id" => $sou_id,
                    "leatype_id" => $leatype_id,
                    "utm_source" => $data["utm_source"],
                    "lea_phone" => $data["phone"],
                    "lea_url" => $url,
                    "lea_ip" => $ip,
                    "lea_aux1" => $data["documento"],
                    "lea_aux2" => $data["cantidadsolicitada"]
                ];
                
                $db = $this->db_webservice;
                $salida = $this->funciones->prepareAndSendLeadLeontel($datos,$db);
                
            }else{
                $salida = json_encode(['result' => false, 'message' => $rAsnef->message]);
            }            
        }
        return $response->withJson(json_decode($salida, true));

    });    
    
});





$app->group('/evobanco', function(){
    
    /* Inserción en tabla evo_user_tracking
     * params: hashid, stepid, bsdCookie
     * @JSON salida:
     *      success:boolean
     *      message:string
    */
    $this->post('/userTracking', function (Request $request, Response $response, array $args){
        $this->logger->info("WS user tracking Evo Banco");
        
        if($request->isPost()){
            $data = $request->getParsedBody();
            
            list($url, $ip, $device) = $this->funciones->getServerParams($request);
            
            $datos = [
                "hashid" => strtoupper($data["hashid"]), 
                "stepid" => $data["stepid"], 
                "device" => $device, 
                "url_source" => $url, 
                "track_ip" => $ip, 
                "track_cookie" => $data["bsdCookie"]
            ];
            
            $db = $this->db_webservice;            
            $parametros = UtilitiesConnection::getParametros($datos,null); 
            $salida = json_decode($db->insertPrepared("evo_user_tracking", $parametros),true);
            
            return $response->withJson($salida);
        }
        return null;
    });
    
    /* 
     * evo_events_sf_v2 => 
     * params:
     * @entrada => montón de parámetros proceso de entrada Evo
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/eventSF_v2', function (Request $request, Response $response, array $args){
        $this->logger->info("WS EventSF_V2 Evo Banco");
        
        if($request->isPost()){
            $data = $request->getParsedBody();

            $datos = [
                "CLIENTID" => $data["clientId"],                
                "PERSONMOBILEPHONE" => $data["personMobilePhone"],
                "PERSONEMAIL" => $data["personEmail"],
                "FIRSTNAME" => $data["firstName"],
                "LASTNAME" => $data["lastName"],
                "PERSONHASOPTEDOUTOFEMAIL" => $data["personHasOptedOutOfEmail"],
                "QUIERE_PUBLICIDAD__C" => $data["quierePublicidad"],
                "PERSONDONOTCALL" => $data["personDoNotCall"],
                "CONFIRMA_OK__C" => $data["confirmaOk"],
                "PERSONLEADSOURCE" => $data["personLeadSource"],
                "ELECTRONICAID_OK__C" => $data["electronicaIdOk"],
                "CREATEDDATE" => $data["createdDate"],
                "LASTMODIFIEDDATE" => $data["lastModifiedDate"],
                "RATING" => $data["rating"],
                "CLIENT_ESTADO__C" => $data["clientEstado"],
                "TIPO_DE_IDENTIFICACION__C" => $data["tipoDeIdentificacion"],
                "CLIENTTYPE" => $data["clientType"],
                "STEPID" => $data["stepId"],
                "URL_SALESFORCE" => $data["urlSalesforce"],
                "URL_SOURCE" => $data["urlSource"],
                "ESTADO_CONFIRMA__C" => $data["estadoConfirma"],
                "GESTION__C" => $data["gestion"],
                "SUBGESTION__C" => $data["subgestion"],
                "BLOQUEO_CLIENTE__C" => $data["bloqueoCliente"],
                "ELECTRONICID_ESTADO__C" => $data["electronicIdEstado"],
                "GESTION_BACKOFFICE__C" => $data["gestionBackOffice"],
                "EVENT__C" => $data["event"],
                "REJECTIONMESSAGE__C" => $data["rejectionMessage"],
                "LOGALTYID" => $data["logaltyId"],
                "LOGALTY_ESTADO__C" => $data["logaltyEstado"],
                "DESCARGA_DE_CONTRATO__C" => $data["descargaDeContrato"],
                "DOCUMENTACION_SUBIDA__C" => $data["documentacionSubida"],
                "DESCARGA_DE_CERTIFICADO__C" => $data["descargaDeCertificado"],
                "RECORDNUMBER" => $data["recordNumber"],
                "IDPERSONIRIS" => $data["idPersonIris"],
                "CONTRACTSTATUS" => $data["contractStatus"],
                "LOGALTYDATE" => $data["logaltyDate"],
                "FECHA_FORMALIZACION" => $data["fechaFormalizacion"],
                "PRODUCT_CODE" => $data["productCode"],
                "IDCONTRACT" => $data["idContract"],
                "CLIENT" => $data["client"],
                "METODO_ENTRADA" => $data["metodoEntrada"],
                "MOTIVO_DESESTIMACION" => $data["motivoDesestimacion"]
            ];
                                    
            $db = $this->db_webservice;

            $parametros = UtilitiesConnection::getParametros($datos,null); 
            $salida = json_decode($db->insertPrepared("evo_events_sf_v2", $parametros),true);
            
            return $response->withJson($salida);
        }
        return null;
    });
    
    /*
     * Captura de distintos eventos producidos en la web de EVO Banco, no se envía a Leontal 
     * de la forma habitual, ya que se instancia un ws SOAP disinto al habitual, por lo que se 
     * utilizará una llamada a la invocación de este WS distinta.
     * params:
     * @JSON entrada:
     *  {
     *    "clientId": "XXXXXX",
     *    "personMobilePhone": "XXXX",
     *    "personEmail": "XXXXXXX",
     * 	  "firstName": XXXXXXX,
     *    "lastName": "XXXXXXX"
     *      .
     *      .
     *      .
     * }
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/event_sf_v2_pro', function(Request $request, Response $response, array $args){

        $this->logger->info("WS EVO Banco event_sf_v2_pro.");

        if($request->isPost()){
            $data = $request->getParsedBody();

            $datos = [
                "CLIENTID" => $data["clientId"],                
                "PERSONMOBILEPHONE" => $data["personMobilePhone"],
                "PERSONEMAIL" => $data["personEmail"],
                "FIRSTNAME" => $data["firstName"],
                "LASTNAME" => $data["lastName"],
                "PERSONHASOPTEDOUTOFEMAIL" => $data["personHasOptedOutOfEmail"],
                "QUIERE_PUBLICIDAD__C" => $data["quierePublicidad"],
                "PERSONDONOTCALL" => $data["personDoNotCall"],
                "CONFIRMA_OK__C" => $data["confirmaOk"],
                "PERSONLEADSOURCE" => $data["personLeadSource"],
                "ELECTRONICAID_OK__C" => $data["electronicaIdOk"],
                "CREATEDDATE" => $data["createdDate"],
                "LASTMODIFIEDDATE" => $data["lastModifiedDate"],
                "RATING" => $data["rating"],
                "CLIENT_ESTADO__C" => $data["clientEstado"],
                "TIPO_DE_IDENTIFICACION__C" => $data["tipoDeIdentificacion"],
                "CLIENTTYPE" => $data["clientType"],
                "STEPID" => $data["stepId"],
                "URL_SALESFORCE" => $data["urlSalesforce"],
                "URL_SOURCE" => $data["urlSource"],
                "ESTADO_CONFIRMA__C" => $data["estadoConfirma"],
                "GESTION__C" => $data["gestion"],
                "SUBGESTION__C" => $data["subgestion"],
                "BLOQUEO_CLIENTE__C" => $data["bloqueoCliente"],
                "ELECTRONICID_ESTADO__C" => $data["electronicIdEstado"],
                "GESTION_BACKOFFICE__C" => $data["gestionBackOffice"],
                "EVENT__C" => $data["event"],
                "REJECTIONMESSAGE__C" => $data["rejectionMessage"],
                "LOGALTYID" => $data["logaltyId"],
                "LOGALTY_ESTADO__C" => $data["logaltyEstado"],
                "DESCARGA_DE_CONTRATO__C" => $data["descargaDeContrato"],
                "DOCUMENTACION_SUBIDA__C" => $data["documentacionSubida"],
                "DESCARGA_DE_CERTIFICADO__C" => $data["descargaDeCertificado"],
                "RECORDNUMBER" => $data["recordNumber"],
                "IDPERSONIRIS" => $data["idPersonIris"],
                "CONTRACTSTATUS" => $data["contractStatus"],
                "LOGALTYDATE" => $data["logaltyDate"],
                "FECHA_FORMALIZACION" => $data["fechaFormalizacion"],
                "PRODUCT_CODE" => $data["productCode"],
                "IDCONTRACT" => $data["idContract"],
                "CLIENT" => $data["client"],
                "METODO_ENTRADA" => $data["metodoEntrada"],
                "MOTIVO_DESESTIMACION" => $data["motivoDesestimacion"]
            ];
            
            $array_tipo_leontel = App\Functions\LeadLeontel::getIdTipoLeontel($datos["LOGALTY_ESTADO__C"], $datos["CLIENT_ESTADO__C"], $datos["STEPID"], $datos["CONTRACTSTATUS"]);
            $destinyF = $array_tipo_leontel["destiny"];
		
            $destiny = $destinyF === NULL || $datos["STEPID"] == "registro" || $datos["STEPID"] == "confirmacion-otp-primer-paso" 
                    || $datos["STEPID"] == "confirmacion-otp" || $datos["STEPID"] == "cliente-existente" || $datos["STEPID"] == "datos-personal" 
                    || $datos["STEPID"] == "datos-contacto" || $datos["STEPID"] == "datos-laboral" 
                    ? NULL : $destinyF;
                        
            if($destiny !== NULL && $data["clientId"] != "IDE-00009683" && $data["clientId"] != "IDE-00027350" && $data["personMobilePhone"] != ""){
                $datos["even_destiny"] = $destiny;
            }

            $db = $this->db_webservice;            
            $result = $this->funciones->prepareAndSendLeadEvoBancoLeontel($datos, $db);
            $r = json_decode($result);
            
            return json_encode(['success'=> $r->success, 'message'=> $r->message]);
        }
    });
    
    /*
     * Tarea cron para C2C Evo Banco Leontel
     */
    $this->post('/sendC2CToLeontel', function (Request $request, Response $response, array $args){
        
        $this->logger->info("WS sendC2CToLeontel Evo Banco");
        
        if($request->isPost()){
            $data = $request->getParsedBody();
            $data["sou_id"] = 3;
            
            $db = $this->db_webservice;
            $result = json_decode($this->funciones->sendC2CToLeontel($data, $db));
            
            return $response->withJson($result);
        }
    });
    
    /*
     * Proceso C2C para EVO Banco, se almacena lead en BD a través de stored procedure
     * params:
     * @JSON entrada:
     *  {
     *    "lea_phone": "XXXXXX",
     *    "stepId": "XXXX",
     *    "type": "XXXXXXX",
     * 	  "codRecommendation": XXXXXXX,
     *    "test": "XXXXXXX"
     * }
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/incomingC2C', function (Request $request, Response $response, array $args){

        $this->logger->info("WS incoming C2C EVO Banco");

        if($request->isPost()){
            $data = $request->getParsedBody();
            $typ = $data["type"];
            $type = ($typ == 2 || $typ == "2") ? 3 : 1;
            $codRecommendation = array_key_exists("codRecommendation", $data) ? $data["codRecommendation"] : "";
            $lea_destiny = array_key_exists("test", $data) ? 'TEST' : 'LEONTEL';
            list($url, $ip) = $this->funciones->getServerParams($request);

            
            $datos = [
                "lea_phone" => $data["phone"],
                "lea_url" => $url,
                "lea_ip" => $ip,
                "lea_aux1" => $data["stepId"],
                "lea_aux2" =>  $codRecommendation,
                "lea_destiny" => $lea_destiny,
                "sou_id" => 3,
                "leatype_id" => $type                
            ];
            
            $datosDuplicates = [
                "lea_phone" => $data["phone"],
                "lea_url" => $url,
                "lea_ip" => $ip,
                "stepid" => $data["stepId"],
                "sou_id" => 3,
                "leatype_id" => $type,
                "codRecommendation" =>  $codRecommendation
            ];

            $db = $this->db_webservice;
            $parametros = UtilitiesConnection::getParametros($datos,null);    
            $query = $db->insertStatementPrepared("leads", $parametros);            
            
            if($type == 1){  
                $sp = 'CALL wsInsertLead("'.$datos["lea_phone"].'", "'.$query.'");';                
            }else{
                $parametrosDuplicates = UtilitiesConnection::getParametros($datosDuplicates,null);
                $queryDuplicates = $db->insertStatementPrepared("leads_evo_duplicados", $parametrosDuplicates);               
                $sp = 'CALL wsInsertLead_EvoBanco("'.$datos["lea_phone"].'", "'.$query.'", "'.$queryDuplicates.'");';
            }         

            $result = $db->Query($sp);           
            
            if($db->AffectedRows() > 0){
                $resultSP = $result->fetch_assoc();
                $lastid = $resultSP["@result"];
                $db->NextResult();
                $result->close();

                if($type != 3){
                    \App\Functions\LeadLeontel::sendLead($datos,$db);
                }
                
                $db->close();
                return json_encode(['success'=> true, 'message'=> $lastid]);
            }else{
                return json_encode(['success'=> false, 'message'=> $db->LastError()]);
            }            
        }        
    });
    
    /*
     * Proceso cambio a FullOnline leads Evo Banco
     * params:
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/setFullOnline', function (Request $request, Response $response, array $args){
       $this->logger->info("WS Set Full Online Evo Banco");
       
       if($request->isPost()){
           $data->request->getParsedBody();
           
            $sql = "UPDATE
                    webservice.leads wl
                    INNER JOIN (
                        SELECT
                            l.lea_id
			FROM
			webservice.leads l
			INNER JOIN webservice.leads_evo_duplicados d ON l.lea_phone = d.lea_phone
			WHERE
                            TIMESTAMPDIFF(MINUTE, d.lea_ts, now()) < 59
                            AND l.lea_destiny = 'LEONTEL'
                            AND l.lea_status IS NULL
			GROUP BY
			l.lea_phone) tab ON wl.lea_id = tab.lea_id
                    SET wl.lea_status = 'FULLONLINE'";
            
            $db = $this->db_webservice->Query($sql);
            
            if($db->AffectedRows() > 0){
                return json_encode(['success'=> true, 'message'=> $db->AffectedRows()]);
            }else{
                return json_encode(['success'=> false, 'message'=> $db->LastError()]);
            }
       }
    });
    
    /*
     * Tarea cron para recovery Leontel
     */
    $this->post('/sendLeadToLeontelRecovery', function (Request $request, Response $response, array $args){
        
        $this->logger->info("WS sendLeadToLeontelRecovery Evo Banco");
        
        if($request->isPost()){
            $db = $this->db_webservice;            
            $this->funciones->sendLeadToLeontelRecovery($db);
        }
    });
});




$app->group('/yoigo', function(){
  
    $this->post('/incomingC2C', function(Request $request, Response $response, array $args){
        $this->logger->info("WS incoming C2C Yoigo");
        
        if($request->isPost()){
            $data = $request->getParsedBody();
                       
            list($url, $ip) = $this->funciones->getServerParams($request);
            
            
            /*
            Defaul, va a las colas de SEO. => si no hay utm_source
            Google, va a las colas de SEM.
            Clck, va a las colas de Emailing. 
            Kwnk, va a las colas de Emailing. 
            Tmone, va a las colas de Emailing

            18	YOIGO NEGOCIOS SEO	26
            19	YOIGO NEGOCIOS SEM	27
            20	YOIGO NEGOCIOS EMAILING	28
            */
            $utm_source_sanitized = strtolower(trim($data["utm_source"]));

            switch($utm_source_sanitized){
                case "clck":
                case "kwnk":
                case "tmone":
                    $sou_id = 20;
                    break;
                case "google":
                    $sou_id = 19;
                    break;
                default;
                    $sou_id = 18;
            }
            
            if(!empty($data["gclid"])){
            	$sou_id = 19;
            }

            //Hayq que repasar esto ya que el source 17 no está contemplado en esta logica.
//            $sou_id = 17;
            $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);
            
            $datosHorario = ["sou_id" => $sou_idcrm, "hora" => date('H:i'), "num_dia" => intval(date('N'))];            
            $consTimeTable = $this->funciones->consultaTimeTableC2C($datosHorario, $this->db_report_panel);
            $leatype_id = is_array($consTimeTable) ? 1 : 20;
            
            $lea_aux3 = $data["cobertura"]."//".$data["impuesto"];
                    
            $datos = [
                "lea_phone" => $data["phone"],
                "lea_url" => $url,
                "lea_ip" => $ip,
                "lea_aux2" =>  $data["producto"],
                "observations" => $data["producto"],
                "lea_aux3" => $lea_aux3,
                "lea_destiny" => 'LEONTEL',
                "sou_id" => $sou_id,
                "leatype_id" => $leatype_id                
            ];
            
            $salida = json_decode($this->funciones->prepareAndSendLeadLeontel($datos,$this->db_webservice));
        }       
        return $response->withJson($salida);
    });
});



$app->group('/doctordinero', function(){
    
    /*
     * Proceso de validación de lead valido cuando en la LP se marcan como "NO" casillas "Cliente" y "Asnef"
     * params:
     * @JSON entrada:
     *  {
     *    "utm_source": "XXXXXX",
     *    "phone": "XXXX",
     *    "documento": "XXXXXXX",
     * 	  "cantidadsolicitada": XXXXXXX,
     *    "motivo": "XXXXXXX"
     * }
     * @JSON salida:
     *      success:boolean
     *      message:string
     */
    $this->post('/incomingC2C', function(Request $request, Response $response, array $args){
       
        $this->logger->info("WS incoming Doctor Dinero.");
        
        if($request->isPost()){
            $data = $request->getParsedBody();
                          
            $datos = array();
            $salida = array();
            $salidaTxt = "";

            $datos['telf'] = $data["movil"];
            $datos['dninie'] = $data["dni"];
            $datos['importe'] = $data["importe"];
            $datos['ingresosMensuales'] = $data["ingresosMensuales"];
            $datos['nombre'] = $data["nombre"];
            $datos['apellido1'] =$data["apellido1"];
            $datos['apellido2'] =$data["apellido2"];
            $datos['fechaNacimiento'] = $data["fechaNacimiento"];
            $datos['email'] = $data["email"];
            $datos['cp'] = $data["cp"];
            
            foreach($datos as $key => $value){
                if(empty($value)){
                    array_push($salida,"KO-notValid_".$key);
                    $salidaTxt .= "KO-notValid_".$key."///";
                }
            }

            if(!App\Functions\NifNieCifValidator::isValidIdNumber($data['dni'])){
                array_push($salida,"KO-notValid_dninie");
                $salidaTxt .= "KO-notValid_dninie///";
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                array_push($salida,"KO-notValid_email");
                $salidaTxt .= "KO-notValid_email///";
            }

            $valido = empty($salida);
            $telfValido = App\Functions\Functions::phoneFormatValidator($data['movil']);
            
            $sou_id = 9;        
            $sou_idcrm = $this->funciones->getSouIdcrm($sou_id, $this->settings_db_webservice);
            $leatype_id = 1;

            $observations = "";
            foreach($data as $v){
                $observations .= $v."--";
            }           
            
            list($url, $ip) = $this->funciones->getServerParams($request);
            
            $datosLead = [
                "sou_id" => $sou_id,
                "leatype_id" => $leatype_id,
                "lea_phone" => $data["movil"],
                "lea_url" => $url,
                "lea_ip" => $ip,
                "utm_source" => array_key_exists("utm_source", $data) ? $data["utm_source"] : null,
                "sub_source" => array_key_exists("sub_source", $data) ? $data["sub_source"] : null,
                "lea_aux1" => $data["dni"],
                "observations" => $observations
            ];

            $todoOk = false;
            if($telfValido){
              
                $datosAsnef = array(
                    "sou_id" => $sou_idcrm,
                    "documento" => $data["dni"],
                    "phone" => $data["movil"]
                );  
                
                $rAsnef = json_decode($this->funciones->checkAsnefCreditea($datosAsnef, $this->db_crmti));
                     
                if($rAsnef->success){
                    //lead no valido asnef
                    $datosLead["lea_aux3"] = "asnef_yacliente_notValid";                    
                }else if($valido){
                    //lead valido
                    $datosLead["lea_destiny"] = 'LEONTEL';
                    $datosLead["lea_aux3"] = "Ok_DoctorDinero";
                    $todoOk = true;
                }else{ 
                    $datosLead["lea_destiny"] = 'LEONTEL';
                    $datosLead["lea_aux3"] = $salidaTxt;
                    $todoOk = true;
                }
            }else{
                $datosLead["lea_aux3"] = $datos['telf']."_notValid";
            }        
            
            $db = $this->db_webservice;
            $mensaje = "KO-notValid";
            
            if($todoOk){
                $result = json_decode($this->funciones->prepareAndSendLeadLeontel($datosLead,$db));
                if($valido)
                    $mensaje = $result->message;
                
                $salida = array(['result' => true, 'message' => $mensaje]);                       
            }else{
                $this->funciones->prepareAndSendLeadLeontel($datosLead, $db, null, false);
                $salida = array(['result' => false, 'message' => $mensaje ]);
            }
            return $response->withJson($salida); 
        }
   });
});



// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
