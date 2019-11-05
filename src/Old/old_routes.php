<?php

use Slim\Http\Request;
use Slim\Http\Response;

use App\Libraries\UtilitiesConnection;

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

  $this->post('/getSouIdcrm', function(Request $request, Response $response, array $args){
    if($request->isPost()){
      $sou_id = $this->sou_id_test;
      $sou_idcrm = $this->funciones->getSouIdcrm($sou_id);

      $body = $response->getBody();
      $body->write($sou_idcrm);
      return $response;   //Debe devolver 23 para sou_id 15
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
      $leatype_id = $this->funciones->isCampaignOnTime($sou_id) ? 1 : 20;

      $lea_aux3 = $data->cobertura."//".$data->impuesto;

      $datos = [
        "lea_phone" => $data->phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_aux2" =>  $data->producto,
        "observations" => $data->producto,
        "lea_aux3" => $lea_aux3,
        "lea_destiny" => $this->dev ? 'TEST' : 'LEONTEL',
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id
      ];

      $salida = json_decode($this->funciones->prepareAndSendLeadLeontel($datos));
    }
    return $response->withJson($salida);
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