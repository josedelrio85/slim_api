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

      list($url, $ip) = $this->funciones->getServerParams($request);

      $sou_id = $this->dev ? $this->sou_id_test : 5;    
      $leatype_id = $this->funciones->isCampaignOnTime($sou_id) ? 1 : 9;
      $destiny = $this->dev ? 'TEST' : 'LEONTEL';

      $datos = [
        "lea_phone" => $phone,
        "lea_url" => $url,
        "lea_ip" => $ip,
        "lea_destiny" => $destiny,
        "sou_id" => $sou_id,
        "leatype_id" => $leatype_id,
        "utm_source" => array_key_exists("utm_source", $data) ? $data->utm_source : null,
        "sub_source" => array_key_exists("sub_source", $data) ? $data->sub_source : null,
        "lea_aux4" => array_key_exists("gclid", $data) ? $data->gclid : null,
      ];

      $resultLeontel = json_decode($this->funciones->prepareAndSendLeadLeontel($datos), true);

      return $response->withJson($resultLeontel);
    }
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





/** REMOVE 8/11/2019 09:00 AM */

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

/** */



// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
  $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
  return $handler($req, $res);
});
