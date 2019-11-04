<?php

use Slim\Http\Request;
use Slim\Http\Response;

use App\Libraries\UtilitiesConnection;

$app->get('/', function (Request $request, Response $response, array $args) {
  // Sample log message
  $this->utilities->infoLog("Slim-Skeleton '/' route");
  
  var_dump($request->getServerParams());
  $response->getBody()->write(" Hola ");
  return $response;
});


$app->group('/test', function(){

    $this->post('/testException', function (Request $request, Response $response, array $args){
      throw new Exception("hola cara de bola!");
      try {
        throw new Exception();
      } catch (App\Libraries\CustomException $e) {
        echo "Atrapada mi excepción\n", $e;
      } catch (Exception $e) {
        //  echo "Atrapada la Excepción Predeterminada\n", $e;
      }
      echo "\n\n";
    });

    $this->post('/testErrorsForExceptions', function(Request $request, Response $response, array $args){
      
      $data = $request->getParsedBody();

      switch($data->test){
        case '1':
          // Log file blocked and can't write inside
          $this->utilities->infoLog("Testing log!");
          break;
        case '2':
          // generate a runtime error that has to be managed by CustomPHPErrorHandler class
          $var = 1;
          $var->method();
          break;
        case '3':
          // Needed param is missing, exception managed by CustomException class
          $datos = [
            0 => "LEONTEL",
            // 1 => $data->sou_id,
          ];
  
          $query = "SELECT * "
          . "FROM ".$this->leads_table." l "
          . "WHERE "
          . "l.lea_destiny = ? "
          . "AND l.sou_id = ? ";
    
          $db = $this->db_webservice;
          $r = $db->selectPrepared($query, $datos);
          break;
        case '4':
          // Needed where param is missing, exception managed by CustomException class
          $datos = [
            0 => "LEONTEL",
            1 => $data->sou_id,
          ];

          $query = "SELECT * "
          . "FROM ".$this->leads_table." l "
          . "WHERE "
          . "l.lea_destiny = ? "
          // . "AND l.sou_id = ? "
          ;
    
          $db = $this->db_webservice;
          $r = $db->selectPrepared($query, $datos);
          break;
        default:
        break;
      }
      return $response->withJson($r);        
    });

    /*
     * Función para obtener el horario de atención en C2C. Devuelve un array JSON {result:boolean, data:JSONArray}
     * -> JSON entrada:
     *  {
     *    "sou_id": "6",
     *    "num_dia": "2",
     *    "hora": "16:00"
     * } 
    */
    $this->post('/consultaTimeTableC2C', function(Request $request, Response $response, array $args){
      if($request->isPost()){
        $data = $request->getParsedBody();

        $elements = $this->funciones->consultaTimeTableC2C($data);
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
        $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);

        $body = $response->getBody();
        $body->write($sou_idcrm);
        return $response;   //Debe devolver 23 para sou_id 15
      }
    });

    $this->post('/horarioEntradaLeads', function(Request $request, Response $response, array $args){

      if($request->isPost()){
        $sou_id = $this->sou_id_test;
        $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);

        // si está en horario, devuelve true, si no false
        $salida = $this->funciones->horarioEntradaLeads($sou_idcrm);
        return $response->withJson($salida);
      }
    });

    $this->post('/sendLeadToLeontel', function(Request $request, Response $response, array $args){

      $data = $request->getParsedBody();
      if(!empty($data->sou_id)){
        $sou_id = $data->sou_id;
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
      $z = $db->insertStatementPrepared($this->leads_table, $parametros);
      $result = $db->insertPrepared($this->leads_table, $parametros);
      $r = json_decode($result);

      if($r->success){

        $data = ["sou_id" => $datos["sou_id"], "leatype_id" => $datos["leatype_id"]];
        /*
          *    Devuelve ['success'=> $res->success, 'message'=> $res->message] si hay resultados para la consulta
          *    ['success'=> $res->success, 'message'=> 'No results'] => si no hay resultados para la consulta
        */
        $res = App\Functions\LeadLeontel::sendLead($data, $db, $this);

        $rs = json_decode($res,true);

        return $response->withJson($rs);
      }else{
        // Devuelve el error en la inserción
        return  $response->withJson(array("succes" => $r->succes, "message" => $r->message));
      }
    });

    $this->post('/sendLeadToLeontelIntesivo', function(Request $request, Response $response, array $args){

      $db = $this->db_webservice_dev;
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

          $z = $db->insertStatementPrepared($this->leads_table, $parametros);
          $result = $db->insertPrepared($this->leads_table, $parametros);
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

    $this->post('/testLeadStatusProd', function(Request $request, Response $response, array $args){

      $a = App\Functions\LeadLeontel::testLeadStatus();

      return $response->withJson($a);
    });

    $this->post('/testLoggingArray', function(Request $request, Response $response, array $args){
      $this->utilities->infoLog('Testing logging array', array('hola' => 'adios'));
    });

    $this->post('/getjson', function(Request $request, Response $response, array $args){
      if($request->isPost()){
        $this->utilities->infoLog("hola!");

        $output = null;
        $db = $this->db_webservice;
        $sql = "SELECT * FROM webservice.sources;";
        // $sql = "SELECT * FROM webservice.log_error_load_weborama limit 10";

        // $r = $db->selectPrepared($sql, null);
        $result = $db->Query($sql);
        if($result->num_rows > 0){
          while($row = $result->fetch_assoc()){
            $r[] = $row;
          }
        }

        if(!is_null($r)){
          $output = $r;
        }
        return $response->withJson($output);
      }
    });

    $this->post('/testContentTypes', function(Request $request, Response $response, array $args){

      $json = file_get_contents("php://input");
      $data = json_decode($json);

      $dataalt1 = $request->getParsedBody();
      $dataalt2 = (object) $request->getParsedBody();

      /*
        * A problem was found!!
        * When a ajax request is made from a browser, I have to use this Content-Type: application/x-www-form-urlencoded; charset=UTF-8
        * because if I use Content-Type: application/json a message about CORS situation is showed in the console and the request never reachs the controller.
        * Using www-form-urlenncoded Content-Type with $request->getParsedBody() method generates a malformed array. As an example, using this array:
        * {
        *  "field1": "hola!",
        *  "field2": 123213,
        *  "field3": "2019-08-15"
        * }
        *
        * Must generate this stdClass object =>
        * stdClass object {
        *   field1 => (string) hola!
        *   field2 => (int) 123213
        *   field3 => (string) "2019-08-15"
        * }
        *
        * Instead, is generating
        * stdClass object {
        *  {"field1":"hola!","field2":"123213","field2":"2019-08-15"} => (string)
        * }
        *
        * So I have to use file_get_contents("php://input") and json_decode to obtain the desired object.
        *
        * This situation never occurs when using request client as Postman, using /x-www-form-urlencoded or json Content-Type.
        *
        * UPDATE!!!!
        * Using this snippet in Middleware =>
        *
        * # inside middleware:
        * $requestbody = $request->getBody();
        * $requestobject = json_decode($requestbody);
        * # validation and modification of $requestobject takes place here
        * $request = $request->withParsedBody($requestobject);
        * return $next($request, $response);
        *
        * The situation is fixed. Sending an ajax request with the browser + using /x-www-form-urlencoded Content-Type + $request->getParsedBody()
        * returns a well-formed stdClass object.
      */

      return $response->withJson($dataalt2);

    });

    $this->post('/testRandom', function(Request $request, Response $response, array $args){

      $db = $this->db_crmti;

      $sqlPrevSources = "select sou_id from crmti.sou_sources where sou_description like ? ;";
      $datosPrevSources = [
        0 => "%CREDI%"
      ];

      $resultSC = $db->selectPrepared($sqlPrevSources, $datosPrevSources, true);
      $paramPrevSources = UtilitiesConnection::arrayToPreparedParam($resultSC, "sou_id");

      $datosPrevIds = [
        0 => "%79317432T%",
        1 => "665932355"
      ];

      $sqlPrevIds = "SELECT lea_id FROM crmti.lea_leads where dninie like ? OR TELEFONO = ? ORDER BY lea_id desc limit 10;";
      $resultSI = $db->selectPrepared($sqlPrevIds, $datosPrevIds, true);
      $paramPrevIds = UtilitiesConnection::arrayToPreparedParam($resultSI, "lea_id");

      $fecha = new \DateTime('2018-02-18');
      $fecha->sub(new \DateInterval('P1M')); // 1 mes
      $dateMySql  = $fecha->format('Y-m-d');
      // ,389,390,391,393,394,400,402,403,404,405,407,411,499,501,502,503,504,505,507,508,510,511,512,513,514,515,516,519,521,522,523,524,525,526,527,528,530,531,532,533,536,537,542,543,544,549,550,553,556,557,616,617,618,620,621,622,623,624,625,646,647,648,649,650,674,675,676,677,678,679,680,681,682,683,684,686,687
      $arrSubid = array(383,385,386,387,388);

      $datos = [
        0 => $paramPrevSources["values"],
        1 => $dateMySql,
        2 => 'SI',
        3 => $arrSubid,
        4 => $paramPrevIds["values"]
      ];

      $questionsA = $paramPrevSources["questions"];
      $questionsB = UtilitiesConnection::generaQuestions($arrSubid);
      $questionsC = $paramPrevIds["questions"];

      $sql = "SELECT * "
      . "FROM crmti.lea_leads ll "
      . "INNER JOIN crmti.his_history hh ON ll.lea_id = hh.his_lead "
      . "WHERE "
      . "ll.lea_source IN ($questionsA)"
      . "AND date(ll.lea_ts) >=  ? "
      . "AND (ll.asnef = ? "
      . "OR "
      . "hh.his_sub in ($questionsB) "
      . ") "
      . "AND hh.his_lead in ($questionsC) "
      . " LIMIT 10;";

      $result = $db->selectPrepared($sql, $datos);

      if(!is_null($result)){
          return json_encode(['success'=> true, 'message' => 'KO-notValid']);
      }else{
          return json_encode(['success'=> false, 'message' => true]);
      }
    });

    $this->post('/almacenaLeadNoValido_test', function(Request $request, Response $response, array $args){

      $this->utilities->infoLog('WS C2C Creditea E2E almacena lead no válido TEST.');

      if($request->isPost()){

        $data = $request->getParsedBody();

        list($url, $ip) = $this->funciones->getServerParams($request);

        $sou_id = 9;
        if(property_exists($data, "sou_id")){
            $sou_id = $data->sou_id;
        }
        $leatype_id = 1;

        $datos = [
          "lea_destiny" => 'LEONTEL',
          "sou_id" => $sou_id,
          "leatype_id" => $leatype_id,
          "utm_source" => $data->utm_source,
          "lea_phone" => $data->phone,
          "lea_url" => $url,
          "lea_ip" => $ip,
          "lea_aux1" => $data->documento,
          "lea_aux2" => $data->cantidadsolicitada,
          "lea_aux3" => $data->motivo
        ];

        $db = $this->db_webservice;
        $parametros = UtilitiesConnection::getParametros($datos,null);
        $salida = json_decode($db->insertPrepared($this->leads_table, $parametros),true);

        return $response->withJson($salida);
      }
    });

    $this->post('/validaDniTelf_test', function(Request $request, Response $response, array $args){

      $this->utilities->infoLog('WS para validacion datos LP Creditea.');

      if($request->isPost()){
        $data = $request->getParsedBody();

        list($url, $ip) = $this->funciones->getServerParams($request);

        $sou_id = 9;
        if(property_exists($data, "sou_id")){
          $sou_id = $data->sou_id;
        }
        $leatype_id = 1;

        $datosAsnef = [
          "sou_id" => $sou_id,
          "documento" => $data->documento,
          "phone" => $data->phone
        ];

        $rAsnefPre = json_decode($this->funciones->checkAsnefCrediteaPrev($datosAsnef));

        if(!$rAsnefPre->success){
          $rAsnef = json_decode($this->funciones->checkAsnefCreditea($datosAsnef));

          if(!$rAsnef->success){

            $datos = [
              "lea_destiny" => 'LEONTEL',
              "sou_id" => $sou_id,
              "leatype_id" => $leatype_id,
              "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
              "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
              "lea_phone" => $data->phone,
              "lea_url" => $url,
              "lea_ip" => $ip,
              "lea_aux1" => $data->documento,
              "lea_aux2" => $data->cantidadsolicitada
            ];

            $setwebservice = $this->settings_db_webservice;
            $db = new \App\Libraries\Connection($setwebservice);
            $salida = $this->funciones->prepareAndSendLeadLeontel($datos);
            $db = null;
          }else{
            $salida = json_encode(['success' => false, 'message' => $rAsnef->message]);
          }
        }else{
          $salida = json_encode(['success' => false, 'message' => $rAsnefPre->message]);
        }

        $test = json_decode($salida);
        if(!$test->success){

          $datainsert = [
            "lea_destiny" => '',
            "sou_id" => $sou_id,
            "leatype_id" => $leatype_id,
            "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
            "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
            "lea_phone" => $data->phone,
            "lea_url" => $url,
            "lea_ip" => $ip,
            "lea_aux1" => $data->documento,
            "lea_aux2" => $data->cantidadsolicitada,
            "lea_aux3" => $test->message
          ];
          $this->funciones->sendLeadToWebservice($datainsert);

          $salida = json_encode(['success' => false, 'message' => $test->message]);
        }
      }
      return $response->withJson(json_decode($salida, true));
    });

    $this->post('/testAsnefPrev', function(Request $request, Response $response, array $args){
      if($request->isPost()){

        $db = $this->db_webservice;
        $data = $request->getParsedBody();

        $datosAsnef = [
          "sou_id" => $data->sou_id,
          "documento" => $data->documento,
          "phone" => $data->phone
        ];
        $res = $this->funciones->checkAsnefCrediteaPrev($datosAsnef);

        return $response->withJson(json_decode($res));
      }
    });

    $this->post('/checkAsnefCreditea', function(Request $request, Response $response, array $args){

      $data = $request->getParsedBody();

      $datosAsnef = [
        "sou_id" => $data->sou_id,
        "documento" => $data->documento,
        "phone" => $data->phone
      ];

      // Devuelve array ['success'=> true, 'message' => 'KO-notValid'] si no pasa validacion, ['success'=> false, 'message' => true] si pasa validacion
      $rAsnef = $this->funciones->checkAsnefCreditea($datosAsnef);
      return $response->withJson(json_decode($rAsnef));
    });

    $this->post('/checkAsnefDoctorDinero', function(Request $request, Response $response, array $args){

      $data = $request->getParsedBody();

      $datosAsnef = [
        "sou_id" => $data->sou_id,
        "documento" => $data->documento,
        "phone" => $data->phone
      ];

      /*
        *  Devuelve array ['success'=> true, 'message' => 'KO-notValid'] si no pasa validacion,
        *  ['success'=> true, 'message' => true] si pasa validacion
        *  ['result'=> true, 'data' => 'KO-paramsNeeded'] si faltan parámetros
      */
      $rAsnef = $this->funciones->checkAsnefCreditea($datosAsnef);
      return $response->withJson(json_decode($rAsnef));
    });
});



$app->group('/RCable', function(){

  /*
    * Función para gestionar la info asociada a un evento C2C para LP de RCable. Devuelve un array JSON {result:boolean, message:objConexion}
    * -> JSON entrada:
    *  {
    *    "phone": "XXXXXX",
    *    "url": "XXXX",
    * }
    *   
  */
  $this->post('/incomingC2C', function (Request $request, Response $response, array $args){

    $this->utilities->infoLog('WS incoming C2C RCable');

    if($request->isPost()){
      $data = $request->getParsedBody();
      $phone = $data->phone;

      if (!$this->funciones->phoneFormatValidator($phone)){
        return $response->withJson(['success' => false, 'message' => 'Malformed phone'])->withStatus(422);
      }

      list($url, $ip) = $this->funciones->getServerParams($request);

      $sou_id = $this->dev ? $this->sou_id_test : 5;
      $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);
      
      $leatype_id = $this->funciones->horarioEntradaLeads($sou_idcrm) ? 1 : 9;

      $destiny = $this->dev ? 'TEST' : 'LEONTEL';

      $datos = [
        "lea_phone" => $phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_destiny" => $destiny,
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id
      ];

      $resultLeontel = json_decode($this->funciones->prepareAndSendLeadLeontel($datos), true);

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

    $this->utilities->infoLog("WS C2C Creditea E2E almacena lead no válido.");

    if($request->isPost()){

      $data = $request->getParsedBody();

      list($url, $ip) = $this->funciones->getServerParams($request);

      $sou_id = 9;
      $leatype_id = 1;     
      if(property_exists($data, "sou_id")){
        $sou_id = $data->sou_id;
      }
      
      if(property_exists($data, "web_source")){
        if($data->web_source == "prestamoscreditea.com" && $sou_id == 9) {
          $leatype_id = $this->funciones->checkGclid($data) ? 25 : $leatype_id;
        }
      }

      $datos = [
        "lea_destiny" => '',
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id,
        "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
        "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux1" => $data->documento,
        "lea_aux2" => $data->cantidadsolicitada,
        "lea_aux3" => $data->motivo,
        "lea_aux4" => array_key_exists("gclid", $data) ? $data->gclid : null,
      ];

      $db = $this->db_webservice;
      $parametros = UtilitiesConnection::getParametros($datos,null);
      $salida = json_decode($db->insertPrepared($this->leads_table, $parametros),true);

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

    $this->utilities->infoLog("WS para validacion datos LP Creditea.");

    if($request->isPost()){
      $data = $request->getParsedBody();

      list($url, $ip) = $this->funciones->getServerParams($request);

      $sou_id = 9;
      $leatype_id = 1;
      if(property_exists($data, "sou_id")){
        $sou_id = $data->sou_id;
      }
      if(property_exists($data, "web_source")){
        if($data->web_source == "prestamoscreditea.com" && $sou_id == 9) {
          $leatype_id = $this->funciones->checkGclid($data) ? 25 : $leatype_id;
        }
      }

      $datosAsnef = [
        "sou_id" => $sou_id,
        "documento" => $data->documento,
        "phone" => $data->phone
      ];

      $rAsnefPre = json_decode($this->funciones->checkAsnefCrediteaPrev($datosAsnef));

      if(!$rAsnefPre->success){
        //sou_id de crmti
        $sou_id_crmti = $this->funciones->getSouIdcrm($sou_id);
        $datosAsnef["sou_id"] = $sou_id_crmti;

        $rAsnef = json_decode($this->funciones->checkAsnefCreditea($datosAsnef));

        if(!$rAsnef->success){

          $datos = [
            "lea_destiny" => 'LEONTEL',
            "sou_id" => $sou_id,
            "leatype_id" => $leatype_id,
            "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
            "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
            "lea_phone" => $data->phone,
            "lea_url" => $url,
            "lea_ip" => $ip,
            "lea_aux1" => $data->documento,
            "lea_aux2" => $data->cantidadsolicitada,
            "lea_aux4" => array_key_exists("gclid", $data) ? $data->gclid : null,
          ];

          $setwebservice = $this->settings_db_webservice;
          $db = new \App\Libraries\Connection($setwebservice);
          $salida = $this->funciones->prepareAndSendLeadLeontel($datos,$db);
          $db = null;
        }else{
          $salida = json_encode(['success' => false, 'message' => $rAsnef->message]);
        }
      }else{
        $salida = json_encode(['success' => false, 'message' => $rAsnefPre->message]);
      }

      $test = json_decode($salida);

      if(!$test->success){

        $dataInsert = [
          "lea_destiny" => '',
          "sou_id" => $sou_id,
          "leatype_id" => $leatype_id,
          "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
          "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
          "lea_phone" => $data->phone,
          "lea_url" => $url,
          "lea_ip" => $ip,
          "lea_aux1" => $data->documento,
          "lea_aux2" => $data->cantidadsolicitada,
          "lea_aux3" => $test->message,
          "lea_aux4" => array_key_exists("gclid", $data) ? $data->gclid : null,
        ];
        $this->funciones->sendLeadToWebservice($dataInsert);

        $salida = json_encode(['success' => false, 'message' => $test->message]);
      }
    }
    // $this->utilities->infoLog("salida".$salida);
    return $response->withJson(json_decode($salida, true));
  });

  /*
    * Función para volcado de leads considerados como Pago Recurrente. La idea es que se llame a través de cron.
    * Ver si es posible realizar esto.
    * @returns
    *  @ array que será escrito en log para comprobar qué ids se volcaron. (Sin implementar esto último)
  */
  $this->post('/sendLeadLeontelPagoRecurrente', function(Request $request, Response $response, array $args){

    $db = $this->db_crmti;
    $salida = App\Functions\LeadLeontel::sendLeadLeontelPagoRecurrente("leads", $db, $this->dev);

    $salida = App\Functions\LeadLeontel::sendLeadLeontelPagoRecurrente("cliente", $db, $this->dev);


    return $response->withJson(json_decode($salida, true));
  });

  /*
    * Method to receive the leads sended by IPF and redirect to Leontel system.
    * params:
    *  @ [] json:
    *  [
    *      {
    *          "clientId": "XXXXXX", 
    *	        "nameId":   "XXXXXX", 
    *	        "phoneId":  "+34XXXXXX",
    *          "alternativePhoneId": "",
    *          "lastStatusId": "Not Started",
    *	        "productAmountTaken": "1500€ CREDIT_LINE", 
    *          "channel": "Web",
    *          "type": "New application",
    *          "putLeadDate": "",		
    *	        "application": "A-7590008",
    *          "latestTaskStatus": "Not Started",
    *	        "idStatusDate": "2019-02-14T22:35:15.000Z"
    *      }
    * ]
    * 
    * output:
    * @ [] json:
    * [
    *      {
    *          "success":  boolean
    *          "message":  "XXXXXX"
    *      }
    * ]
  */
  $this->post('/ipf', function(Request $request, Response $response, array $args){

    $this->utilities->infoLog("Method for receiving array of leads from IPF.");

    $results = [];

    if($request->isPost()){
      $leads = (object) $request->getParsedBody();

      $sou_id = 53;
      $lea_type = 1;

      list($url, $ip) = $this->funciones->getServerParams($request);

      foreach($leads as $lead) {

        foreach ($lead as $key => $value) {
          $this->utilities->infoLog($key. " => ".$value);
        }
        $this->utilities->infoLog("-----------------");

        $observations = $lead->idStatusDate."---".$lead->application;
        if($this->funciones->phoneFormatValidator($lead->phoneId)){
          $phone = $lead->phoneId;
        }else{
          $phone = substr($lead->phoneId,3);
        }

        $datos = [
          "lea_destiny" => 'LEONTEL',
          "sou_id" => $sou_id,
          "leatype_id" => $lea_type,
          "lea_phone" => $phone,
          "lea_url" => $url,
          "lea_ip" => $ip,
          "lea_aux1" => $lead->nameId,
          "lea_aux2" => $lead->productAmountTaken,
          "lea_aux4" => $lead->clientId,
          "observations" => $observations
        ];
        array_push($results, json_decode($this->funciones->prepareAndSendLeadLeontel($datos)));
      }
      /*
          Info recibida						        Campo Leontel						    Campo webservice.lead
          clientId => 4169626				            Nº Cliente (ncliente)					lea_aux4
          nameId => 15861419K				            Documento (dninie)					    lea_aux1
          phoneId => +34522361413			            Telefono (telefono)					    lea_phone
          alternativePhoneId =>
          lastStatusId => Not Started
          application => A-7590009			        Observaciones    (observaciones)		observations
          productAmountTaken => 1500€ CREDIT_LINE	    Cantidad ofrecida (cantidadofrecida)	lea_aux2

          channel => Web
          type => New application	
          putLeadDate => 2019-02-10T16:00:00Z	
          latestTaskStatus => Not Started	

          idStatusDate => 2019-02-11T14:17:45.000Z    Observaciones (observaciones)			observations
      */
    }

    $this->utilities->infoLog("----------RESULTS-------");
    foreach ($results as $key => $value) {
      $this->utilities->infoLog($key. " => ".$value->success. " ". $value->message);
    }
    $this->utilities->infoLog("----------END RESULTS-------");

    return $response->withJson($results);
  });
});


$app->group('/evobanco', function(){

  /* 
   * Inserción en tabla evo_user_tracking
   * params: hashid, stepid, bsdCookie
   * @JSON salida:
   *      success:boolean
   *      message:string
  */
  $this->post('/userTracking', function (Request $request, Response $response, array $args){
    $this->utilities->infoLog('WS user tracking Evo Banco');

    if($request->isPost()){
      $data = $request->getParsedBody();

      list($url, $ip, $device) = $this->funciones->getServerParams($request);

      $datos = [
        "hashid" => strtoupper($data->hashid),
        "stepid" => $data->stepid,
        "device" => $device,
        "url_source" => $url,
        "track_ip" => $ip,
        "track_cookie" => $data->bsdCookie
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
    
    $this->utilities->infoLog('WS EventSF_V2 Evo Banco');

    if($request->isPost()){
      $data = $request->getParsedBody();

      $datos = [
        "CLIENTID" => $data->clientId,
        "PERSONMOBILEPHONE" => $data->personMobilePhone,
        "PERSONEMAIL" => $data->personEmail,
        "FIRSTNAME" => $data->firstName,
        "LASTNAME" => $data->lastName,
        "PERSONHASOPTEDOUTOFEMAIL" => $data->personHasOptedOutOfEmail,
        "QUIERE_PUBLICIDAD__C" => $data->quierePublicidad,
        "PERSONDONOTCALL" => $data->personDoNotCall,
        "CONFIRMA_OK__C" => $data->confirmaOk,
        "PERSONLEADSOURCE" => $data->personLeadSource,
        "ELECTRONICAID_OK__C" => $data->electronicaIdOk,
        "CREATEDDATE" => $data->createdDate,
        "LASTMODIFIEDDATE" => $data->lastModifiedDate,
        "RATING" => $data->rating,
        "CLIENT_ESTADO__C" => $data->clientEstado,
        "TIPO_DE_IDENTIFICACION__C" => $data->tipoDeIdentificacion,
        "CLIENTTYPE" => $data->clientType,
        "STEPID" => $data->stepId,
        "URL_SALESFORCE" => $data->urlSalesforce,
        "URL_SOURCE" => $data->urlSource,
        "ESTADO_CONFIRMA__C" => $data->estadoConfirma,
        "GESTION__C" => $data->gestion,
        "SUBGESTION__C" => $data->subgestion,
        "BLOQUEO_CLIENTE__C" => $data->bloqueoCliente,
        "ELECTRONICID_ESTADO__C" => $data->electronicIdEstado,
        "GESTION_BACKOFFICE__C" => $data->gestionBackOffice,
        "EVENT__C" => $data->event,
        "REJECTIONMESSAGE__C" => $data->rejectionMessage,
        "LOGALTYID" => $data->logaltyId,
        "LOGALTY_ESTADO__C" => $data->logaltyEstado,
        "DESCARGA_DE_CONTRATO__C" => $data->descargaDeContrato,
        "DOCUMENTACION_SUBIDA__C" => $data->documentacionSubida,
        "DESCARGA_DE_CERTIFICADO__C" => $data->descargaDeCertificado,
        "RECORDNUMBER" => $data->recordNumber,
        "IDPERSONIRIS" => $data->idPersonIris,
        "CONTRACTSTATUS" => $data->contractStatus,
        "LOGALTYDATE" => $data->logaltyDate,
        "FECHA_FORMALIZACION" => $data->fechaFormalizacion,
        "PRODUCT_CODE" => $data->productCode,
        "IDCONTRACT" => $data->idContract,
        "CLIENT" => $data->client,
        "METODO_ENTRADA" => $data->metodoEntrada,
        "MOTIVO_DESESTIMACION" => $data->motivoDesestimacion
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

    $this->utilities->infoLog('WS EVO Banco event_sf_v2_pro');

    if($request->isPost()){
      $data = $request->getParsedBody();

      $datos = [
        "CLIENTID" => $data->clientId,
        "PERSONMOBILEPHONE" => $data->personMobilePhone,
        "PERSONEMAIL" => $data->personEmail,
        "FIRSTNAME" => $data->firstName,
        "LASTNAME" => $data->lastName,
        "PERSONHASOPTEDOUTOFEMAIL" => $data->personHasOptedOutOfEmail,
        "QUIERE_PUBLICIDAD__C" => $data->quierePublicidad,
        "PERSONDONOTCALL" => $data->personDoNotCall,
        "CONFIRMA_OK__C" => $data->confirmaOk,
        "PERSONLEADSOURCE" => $data->personLeadSource,
        "ELECTRONICAID_OK__C" => $data->electronicaIdOk,
        "CREATEDDATE" => $data->createdDate,
        "LASTMODIFIEDDATE" => $data->lastModifiedDate,
        "RATING" => $data->rating,
        "CLIENT_ESTADO__C" => $data->clientEstado,
        "TIPO_DE_IDENTIFICACION__C" => $data->tipoDeIdentificacion,
        "CLIENTTYPE" => $data->clientType,
        "STEPID" => $data->stepId,
        "URL_SALESFORCE" => $data->urlSalesforce,
        "URL_SOURCE" => $data->urlSource,
        "ESTADO_CONFIRMA__C" => $data->estadoConfirma,
        "GESTION__C" => $data->gestion,
        "SUBGESTION__C" => $data->subgestion,
        "BLOQUEO_CLIENTE__C" => $data->bloqueoCliente,
        "ELECTRONICID_ESTADO__C" => $data->electronicIdEstado,
        "GESTION_BACKOFFICE__C" => $data->gestionBackOffice,
        "EVENT__C" => $data->event,
        "REJECTIONMESSAGE__C" => $data->rejectionMessage,
        "LOGALTYID" => $data->logaltyId,
        "LOGALTY_ESTADO__C" => $data->logaltyEstado,
        "DESCARGA_DE_CONTRATO__C" => $data->descargaDeContrato,
        "DOCUMENTACION_SUBIDA__C" => $data->documentacionSubida,
        "DESCARGA_DE_CERTIFICADO__C" => $data->descargaDeCertificado,
        "RECORDNUMBER" => $data->recordNumber,
        "IDPERSONIRIS" => $data->idPersonIris,
        "CONTRACTSTATUS" => $data->contractStatus,
        "LOGALTYDATE" => $data->logaltyDate,
        "FECHA_FORMALIZACION" => $data->fechaFormalizacion,
        "PRODUCT_CODE" => $data->productCode,
        "IDCONTRACT" => $data->idContract,
        "CLIENT" => $data->client,
        "METODO_ENTRADA" => $data->metodoEntrada,
        "MOTIVO_DESESTIMACION" => $data->motivoDesestimacion
      ];

      $array_tipo_leontel = App\Functions\LeadLeontel::getIdTipoLeontel($datos["LOGALTY_ESTADO__C"], $datos["CLIENT_ESTADO__C"], $datos["STEPID"], $datos["CONTRACTSTATUS"]);
      $destinyF = $array_tipo_leontel["destiny"];

      $destiny = $destinyF === NULL || $datos["STEPID"] == "registro" || $datos["STEPID"] == "confirmacion-otp-primer-paso"
      || $datos["STEPID"] == "confirmacion-otp" || $datos["STEPID"] == "cliente-existente" || $datos["STEPID"] == "datos-personal"
      || $datos["STEPID"] == "datos-contacto" || $datos["STEPID"] == "datos-laboral"
      ? NULL : $destinyF;

      if($destiny !== NULL && $data->clientId != "IDE-00009683" && $data->clientId != "IDE-00027350" && $data->personMobilePhone != ""){
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

      $this->utilities->infoLog('WS sendC2CToLeontel Evo Banco');

      if($request->isPost()){
        $data = $request->getParsedBody();
        $data["sou_id"] = 3;

        $result = json_decode($this->funciones->sendC2CToLeontel($data));

        return $response->withJson($result);
      }
  });

  /*
   * Proceso C2C para EVO Banco, se almacena lead en BD a través de stored procedure
   * params:
   * @JSON entrada:
   *  {
   *     "lea_phone": "XXXXXX",
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

    $this->utilities->infoLog('WS incoming C2C EVO Banco');

    if($request->isPost()){
      $data = $request->getParsedBody();
      $typ = $data->type;
      $type = ($typ == 2 || $typ == "2") ? 3 : 1;
      $codRecommendation = array_key_exists("codRecommendation", $data) ? $data->codRecommendation : "";
      $lea_destiny = array_key_exists("test", $data) ? 'TEST' : 'LEONTEL';
      list($url, $ip) = $this->funciones->getServerParams($request);

      $datos = [
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux1" => $data->stepId,
        "lea_aux2" =>  $codRecommendation,
        "lea_destiny" => $lea_destiny,
        "sou_id" => 3,
        "leatype_id" => $type
      ];

      $datosDuplicates = [
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "stepid" => $data->stepId,
        "sou_id" => 3,
        "leatype_id" => $type,
        "codRecommendation" =>  $codRecommendation
      ];

      $db = $this->db_webservice;
      $parametros = UtilitiesConnection::getParametros($datos,null);
      $query = $db->insertStatementPrepared($this->leads_table, $parametros);

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

    $this->utilities->infoLog('WS Set Full Online Evo Banco');

    if($request->isPost()){
      $data->request->getParsedBody();

      $sql = "UPDATE ".$this->leads_table." wl
              INNER JOIN (
                SELECT
                l.lea_id
                FROM
                ".$this->leads_table." l
                INNER JOIN webservice.leads_evo_duplicados d ON l.lea_phone = d.lea_phone
                WHERE
                TIMESTAMPDIFF(MINUTE, d.lea_ts, now()) < 59
                AND l.lea_destiny = 'LEONTEL'
                AND l.lea_status IS NULL
                GROUP BY l.lea_phone) tab ON wl.lea_id = tab.lea_id
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

    $this->utilities->infoLog('WS sendLeadToLeontelRecovery Evo Banco');

    if($request->isPost()){
      $db = $this->db_webservice;
      $this->funciones->sendLeadToLeontelRecovery($db);
    }
  });
});



$app->group('/yoigo', function(){

  $this->post('/incomingC2C', function(Request $request, Response $response, array $args){
    $this->utilities->infoLog('WS incoming C2C Yoigo');

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
      $utm_source_sanitized = strtolower(trim($data->utm_source));

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

      if(!empty($data->gclid)){
        $sou_id = 19;
      }

      // Hay q que repasar esto ya que el source 17 no está contemplado en esta logica.
      // $sou_id = 17;
      $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);

      $datosHorario = ["sou_id" => $sou_idcrm, "hora" => date('H:i'), "num_dia" => intval(date('N'))];
      $consTimeTable = $this->funciones->consultaTimeTableC2C($datosHorario);
      $leatype_id = is_array($consTimeTable) ? 1 : 20;

      $lea_aux3 = $data->cobertura."//".$data->impuesto;

      $datos = [
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux2" =>  $data->producto,
        "observations" => $data->producto,
        "lea_aux3" => $lea_aux3,
        "lea_destiny" => 'LEONTEL',
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id
      ];

      $salida = json_decode($this->funciones->prepareAndSendLeadLeontel($datos));
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

    $this->utilities->infoLog('WS incoming Doctor Dinero.');

    if($request->isPost()){
      $data = $request->getParsedBody();

      $datos = array();
      $salida = array();
      $salidaTxt = "";

      $datos['telf'] = $data->movil;
      $datos['dninie'] = $data->dni;
      $datos['importe'] = $data->importe;
      $datos['ingresosMensuales'] = $data->ingresosMensuales;
      $datos['nombre'] = $data->nombre;
      $datos['apellido1'] =$data->apellido1;
      $datos['apellido2'] =$data->apellido2;
      $datos['fechaNacimiento'] = $data->fechaNacimiento;
      $datos['email'] = $data->email;
      $datos['cp'] = $data->cp;

      foreach($datos as $key => $value){
        if(empty($value)){
          array_push($salida,"KO-notValid_".$key);
          $salidaTxt .= "KO-notValid_".$key."///";
        }
      }

      if(!App\Functions\NifNieCifValidator::isValidIdNumber($data->dni)){
        array_push($salida,"KO-notValid_dninie");
        $salidaTxt .= "KO-notValid_dninie///";
      }

      if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        array_push($salida,"KO-notValid_email");
        $salidaTxt .= "KO-notValid_email///";
      }

      $valido = empty($salida);
      $telfValido = App\Functions\Functions::phoneFormatValidator($data->movil);

      $sou_id = 9;
      $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);
      $leatype_id = 1;

      $observations = "";
      foreach($data as $v){
        $observations .= $v."--";
      }

      list($url, $ip) = $this->funciones->getServerParams($request);

      $datos = [
        "lea_destiny" => '',
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id,
        "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
        "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
        "lea_phone" => $data->movil,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux1" => $data->dni,
        "observations" => $observations
      ];

      $datosAsnef = [
        "sou_id" => $sou_id,
        "documento" => $data->dni,
        "phone" => $data->movil
      ];

      if($telfValido){

        $rAsnefPre = json_decode($this->funciones->checkAsnefCrediteaPrev($datosAsnef));

        if(!$rAsnefPre->success){               
          $datosAsnef["sou_id"] = $sou_idcrm;
          $rAsnef = json_decode($this->funciones->checkAsnefCreditea($datosAsnef));

          if(!$rAsnef->success){
            $datos["lea_destiny"] = 'LEONTEL';
            $datos["lea_aux3"] = "Ok_DoctorDinero";

            $setwebservice = $this->settings_db_webservice;
            $db = new \App\Libraries\Connection($setwebservice);

            $salida = $this->funciones->prepareAndSendLeadLeontel($datos,$db);
            if(!$valido){
              $salida = json_encode(['success' => false, 'message' => 'KO-notValid_params']);;
            }
            $db = null;
          }else{
            $salida = json_encode(['success' => false, 'message' => $rAsnef->message]);
          }
        }else{
          $salida = json_encode(['success' => false, 'message' => $rAsnefPre->message]);
        }

        $test = json_decode($salida);
        if(!$test->success){
          $datos["lea_destiny"] = '';
          $datos["lea_aux3"] = $test->message;

          $this->funciones->sendLeadToWebservice($datos);

          $salida = json_encode(['success' => false, 'message' => $test->message]);
        }
      }else{
        //telefono no valido
        $datos["lea_destiny"] = '';
        $datos["lea_aux3"] = $data->movil."_notValid";

        $this->funciones->sendLeadToWebservice($datos);

        $salida = json_encode(['success' => false, 'message' => 'KO-notValid_telf']);
      }
      return $response->withJson(json_decode($salida, true));
    }
  });
});



$app->group('/microsoft', function(){

    $this->post('/incomingC2C', function(Request $request, Response $response, array $args){

      $this->utilities->infoLog('Microsoft incomingC2C request');

      if($request->isPost()){
        $data = $request->getParsedBody();

        $domain = $data->domain;

        switch($domain){
          // Recomendador => 1
          case "microsoftbusiness.es":
            $tipo = 1;
            break;
          // Ofertas => 2
          case "microsoftprofesional.es":
            if(!$data->index){
              //FichaProducto => 3
              $tipo = 3;
            }else{
              $tipo = 2;
            }
            break;
          // mundo-r => 4
          case "ofertas.mundo-r.com":
            $tipo = 4;
            break;
          // haz_el_cambio => 5
          case "microsoftbusiness.es/hazelcambio":
            $tipo = 5;
            break;
          //Calculadora
          case "microsoftnegocios.es":
            $tipo = 6;
            break;
          default:
            $tipo = 0;
            break;
        }

        $lea_ip = $_SERVER["REMOTE_ADDR"];
        $sou_id = $this->funciones->getSouidMicrosoft($data->utm_source, $tipo, $data->gclid);

        //INCIDENCIA LEONTEL
        $lea_aux3 = $sou_id;
        // excepción R Cable office365 => mantener sou_id
        // quitar lea_aux3 de datosIni cuando incidencia finalizada
        if($tipo != 4){
          $sou_id = 52;
        }

        $datosIni = [
          "lea_destiny" => 'LEONTEL',
          "sou_id" => $sou_id,
          "leatype_id" => $data->lea_type,
          "utm_source" => $data->utm_source,
          "sub_source" => $data->sub_source,
          "lea_phone" => $data->phone,
          "lea_url" => $data->url,
          "lea_ip" => $lea_ip,
          "lea_aux3" => $lea_aux3
        ];

        if($tipo == 1 || $tipo == 5){
          $check1 = $data->check1;
          $check2 = $data->check2;
          $check3 = $data->check3;
          $pcsOk = "";
          if(is_object($data->pcsOk)){
            foreach($data->pcsOk as $key => $val){
              $pcsOk .= $val. ", ";
            }
            $pcsOk = substr_replace($pcsOk ,"",-2);
          }else{
            $pcsOk = implode(", ", $data->pcsOk);
          }

          $datos1 = [
            "lea_aux4" => $data->tipo_ordenador,
            "lea_aux5" => $data->sector,
            "lea_aux6" => $data->presupuesto,
            "lea_aux7" => $data->rendimiento,
            "lea_aux8" => $data->movilidad,
            "lea_aux9" => $data->tipouso,
            "lea_aux10" => $data->Office365,
            "observations" => $pcsOk
          ];

          $datos = array_merge($datosIni, $datos1);

        }else if($tipo == 2){
          $datos = $datosIni;
        }else if($tipo == 3){
          $name = $data->name;
          $id = $data->id;
          $originalPrice = $data->originalPrice;
          $price = $data->price;
          $brand = $data->brand;
          $discountPercentage = $data->discountPercentage;
          $discountCode = $data->discountCode;
          $typeOfProcessor = $data->typeOfProcessor;
          $hardDiskCapacity = $data->hardDiskCapacity;
          $graphics = $data->graphics;
          $wirelessInterface = $data->wirelessInterface;
          $productType = $data->productType;

          $obs = "Tipo: {$productType} -- Producto: {$name} -- idProducto: {$id} -- precioOriginal: {$originalPrice} -- Precio: {$price} -- Marca: {$brand} -- %Descuento: {$discountPercentage} ";
          $obs .= " Cod. descuento: {$discountCode} -- Tipo Procesador: {$typeOfProcessor} -- Capacidad HDD: {$hardDiskCapacity} -- Gráfica: {$graphics} -- Wireless: {$wirelessInterface}";

          $datos3 = [ "lea_aux10" => $obs ];

          $datos = array_merge($datosIni, $datos3);

        }else if($tipo == 4){
          $datos = $datosIni;
        }else if($tipo == 6){
          //calculadora
          $check1 = $data->check1;
          $check2 = $data->check2;
          $check3 = $data->check3;
          $anos_ordenadores_media = $data->anos_ordenadores_media;
          $sistema_operativo_instalado = $data->sistema_operativo_instalado;
          $frecuencia_bloqueo_ordenadores = $data->frecuencia_bloqueo_ordenadores;
          $num_dispositivos_empresa = $data->num_dispositivos_empresa;
          $reparaciones_ultimo_ano = $data->reparaciones_ultimo_ano;
          $tiempo_arrancar_dispositivos = $data->tiempo_arrancar_dispositivos;

          $lea_aux10 = "anos_ordenadores_media: {$anos_ordenadores_media} -- sistema_operativo_instalado: {$sistema_operativo_instalado} -- "
          . "frecuencia_bloqueo_ordenadores: {$frecuencia_bloqueo_ordenadores} -- num_dispositivos_empresa: {$num_dispositivos_empresa} -- "
          . "reparaciones_ultimo_ano: {$reparaciones_ultimo_ano} -- tiempo_arrancar_dispositivos: {$tiempo_arrancar_dispositivos}";

          $datos4 = [ "lea_aux10" => $lea_aux10 ];

          $datos = array_merge($datosIni, $datos4);
        }

        $result = $this->funciones->prepareAndSendLeadLeontel($datos);

        return $response->withJson(json_decode($result));
      }
    });
});



$app->group('/sanitas', function(){

  $this->post('/incomingC2C', function(Request $request, Response $response, array $args){
    
    $this->utilities->infoLog("Sanitas incomingC2C request");

    if($request->isPost()){

      $data = $request->getParsedBody();

      $sou_id = 57;
      $lea_type = 1;
      list($url, $ip) = $this->funciones->getServerParams($request);

      $datos = [
        "lea_destiny" => 'GSS',
        "sou_id" => $sou_id,
        "leatype_id" => $lea_type,
        "utm_source" => $data->utm_source,
        "sub_source" => $data->sub_source,
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux2" => $data->producto,
        "lea_name" => $data->name
        // acepCond ??
        // acepBd ??
      ];

      $db = $this->db_webservice;
      $parametros = UtilitiesConnection::getParametros($datos,null);
      $salida = json_decode($db->insertPrepared($this->leads_table, $parametros),true);

      if(!$salida['success'])
        $salida['message'] = 'KO';

      return $response->withJson($salida);
    }
  });

  $this->post('/statusLeadGSS', function(Request $request, Response $response, array $args){
    $this->utilities->infoLog('GSS status lead request');

    if($request->isPost()){
      $data = (object) $request->getParsedBody();

      $datos = [
        // "lea_id" => $data->idLead,
        // "lea_phone" => $data->Telefono,
        "__status" => $data->codEstado,
        "__resultado" => $data->codResultado,
        "__motivo" => $data->codMotivo,
      ];

      // nombre de los campos a actualizar¿¿¿????
      // HABRÁ QUE ACTUALIZAR EL ESTADO DEL LEAD, PERO COMO HACEMOS ESTO??? EL LEAD ESTÁ ALMACENADO EN webservice.leads
      // USAR COMO REFERENCIA $lea_id y $lea_phone

      $where = [
        "lea_id" => $data->lea_id,
        "lea_phone" => $data->Telefono
      ];

      $parametros = UtilitiesConnection::getParametros($datos, $where);

      $tabla = $this->leads_table;
      $salida = json_decode($db->updatePrepared($tabla, $parametros), true);

      return $response->withJson($salida);
    }
  });
});


$app->group('/clients', function(){
  // This handler returns the status from some records from the EVO Banco End
  // to End, it has been created to provide lead status information to a third
  // party agency (Nivoria).
  $this->get('/status/{provider}', function(Request $request, Response $response, array $args){
    $providers = array("EVO");
    $provider = strtoupper($args['provider']);
    if (!in_array($provider, $providers)) {
      return $response->withStatus(422)
      ->withHeader('Content-Type', 'text/html')
      ->write('Provider not found or available.');
    }

    $client_id = $request->getParam('client_id');
    if ($client_id == "") {
      return $response->withStatus(422)
      ->withHeader('Content-Type', 'text/html')
      ->write('Client ID not provided!');
    }

    $query = "";
    switch ($provider){
      case "EVO":
        $query = $this->db_webservice->Query(
          "SELECT clientid, createddate, fecha_formalizacion
           FROM evo_events_sf_v2_pro
           WHERE clientid = '" . $client_id . "'
           ORDER BY even_ts desc
           LIMIT 1;");
        break;
    }

    if ($query == "") {
      return $response->withStatus(500)
      ->withHeader('Content-Type', 'text/html')
      ->write('Error finding the proper query for the provider!');
    }

    $row = $query->fetch_assoc();
    if (!$row) {
      return $response->withStatus(404)
      ->withHeader('Content-Type', 'text/html')
      ->write('No status information found for that client_id!');
    }

    $result = [];
    $result[] = $row;

    return $response->withJson($result);
  });
});

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});
