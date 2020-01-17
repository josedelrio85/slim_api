<?php

use Slim\Http\Request;
use Slim\Http\Response;

use App\Libraries\UtilitiesConnection;


$app->group('/rcable', function(){

  /*
    * Función para gestionar la info asociada a un evento C2C para LP de RCable. Devuelve un array JSON {result:boolean, message:objConexion}
    * -> JSON entrada:
    *  {
    *    "phone": "XXXXXX",
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

      $sou_id = (int)$this->dev ? $this->sou_id_test: array_key_exists("sou_id", $data) ? $data->sou_id : 5;
      $valid_sou_id = [5, 15, 14, 71];
      if (!in_array($sou_id, $valid_sou_id)){
        return $response->withJson(['success' => false, 'message' => 'Not valid source'])->withStatus(422);
      }

      list($urlserver, $ipserver) = $this->funciones->getServerParams($request);

      $leatype_id = $this->funciones->isCampaignOnTime($sou_id) ? 1 : 8;
      $destiny = $this->dev ? 'TEST' : 'LEONTEL';
      
      $datos = [
        "lea_phone" => $phone,
        "lea_mail" => array_key_exists("mail", $data) ? $data->mail : null,
        "lea_name" => array_key_exists("name", $data) ? $data->name : null,
        "observations" => array_key_exists("observations", $data) ? $data->observations : null,
        "lea_url" =>  array_key_exists("url", $data) ? $data->url : $urlserver,
        "lea_ip" => array_key_exists("ip", $data) ? $data->ip : $ipserver,
        "lea_destiny" => $destiny,
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id,
        "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
        "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
        "lea_aux4" => array_key_exists("gclid", $data) ? $data->gclid : null,
      ];

      $lead = $this->utilities->arrayToClass($datos, "\\App\\Model\\Lead");

      $resultLeontel = json_decode($this->funciones->prepareAndSendLeadLeontel($lead, null, !$this->dev));
      
      if($resultLeontel->success){
        return $response->withJson($resultLeontel);
      } else {
        return $response->withJson($resultLeontel)->withStatus(422);
      }
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
    $this->utilities->infoLog('/clients/status/'.$args['provider'].' method requested ');
    $this->utilities->infoLog('params[client_id] ' . $request->getParam('client_id'));

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


// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});
