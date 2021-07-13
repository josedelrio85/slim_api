<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Functions;

use App\Libraries\UtilitiesConnection;

class Functions {

  private $dev = null;
  private $container = null;

  public function __construct($dev, $container){
    $this->dev = $dev;
    $this->container = $container;
  }

  /**
    * Lead insert logic. Previous checks to decide if it is an allowed lead.
    * @array lead: lead model
    * @object db: [optional] database instance
    * @bool leontel: [optional] if smartcenter insert is not needed
    * @return array with success result (bool) and a descriptive message (string)
   */
  public function prepareAndSendLeadLeontel($lead, $dbinput = null, $leontel = false){
    if(!empty($lead)){

      $db = is_null($dbinput) ? $this->container->db_webservice : $dbinput;
      $error = 'KO';

      if(empty($lead->getLeaPhone()) || is_null($lead->getLeaPhone())){
        return json_encode(['success'=> false, 'message'=> $error]);
      }

      $sqlone = "
        SELECT count(*) as phoneExists 
        FROM {$this->container->leads_table} 
        where lea_phone = ? 
        and sou_id = ? 
        and leatype_id = ? 
        and date(lea_ts) = curdate()
        and TIME_TO_SEC(timediff(CURRENT_TIME(), time(lea_ts))) < 180;";
      $rone = $db->selectPrepared($sqlone, $lead->getQueryParams(), true);

      if($rone[0]['phoneExists'] == 0) {
        // not duplicated in webservice => if not exists in Leontel => create and send Leontel
        $open = $this->isLeadOpen($lead);
        if(!$open){
          // store in webservice and send to Leontel
          $resp = $this->createLead($lead);
          if ($resp->success) {
            $lead->setLeaId($resp->message);
            $smartcenter = LeadLeontel::sendLead($lead, $db, $this->container);
            $message = $resp->message;
            return json_encode(['success'=> true, 'message'=> $message]);
          }
        } else {
          $error = "Not allowed, lead already open ".$lead->getSouId()." -- ".$lead->getLeaType()." -- ".$lead->getLeaPhone();
        }
      } else {
        $error = "Max attempts limit reached ".$lead->getSouId()." -- ".$lead->getLeaType()." -- ".$lead->getLeaPhone();
      }
    }
    $this->sendAlarm($error);
    return json_encode(['success'=> false, 'message'=> $error]);
  }

  /**
   * createLead formats the array passed as param and creates an insert prepared statement
   * @array $lead: data lead
   * @return array with success result (bool) and a descriptive message (string)
   */
  public function createLead($lead) {
    $db = $this->container->db_webservice;
    $params = UtilitiesConnection::getParametros($lead, null);
    return json_decode($db->insertPrepared($this->container->leads_table, $params));
  }

  /*******************   EVO BANCO ******************************************/
  /*
    * Encapsulación de la lógica de inserción de lead Evo Banco (inserción en webservice.evo_events_sf_v2_pro
    * + envío a la cola de Leontel a través de WS SOAP de Leontel con credenciales).
    * params:
    * @datos: array con conjunto de datos a insertar en bd
    * @db: instancia bd
    * @tabla: por si hay que hacer la inserción en otra tabla
   */
  public function prepareAndSendLeadEvoBancoLeontel($datos,$db,$tabla = null){
    if($tabla == null){
      $tabla = "evo_events_sf_v2_pro";
    }

    if(is_array($datos) && !is_null($db)){
      $parametros = UtilitiesConnection::getParametros($datos,null);
      $result = $db->insertPrepared($tabla, $parametros);

      $r = json_decode($result);

      if($r->success){
        LeadLeontel::sendLeadEvo($datos,$db, $this->dev);
        exit(json_encode(['success'=> true, 'message'=> $r->message]));
      }else{
        exit(json_encode(['success'=> false, 'message'=> $r->message]));
      }
    }else{
      return json_encode(['success'=> false, 'message'=> '??']);
    }
  }

  /*
    * Invocación tarea recovery Evo Banco
   */
  public function sendLeadToLeontelRecovery($db){
    LeadLeontel::recoveryEvoBancoLeontel($db, $this->dev);
  }

  /*
    * Invocación tarea C2C Leontel (usada mayormente para cron Evo Banco)
   */
  public function sendC2CToLeontel($data){
    if(!empty($data)){
      $db = $this->container->db_webservice;
      return LeadLeontel::sendLead($data, $db, $this->container);
    }
    return null;
  }

  /***************************************************************************/

  /**
    * Returns smartcenter sou_id for the provided input
    * @params:
    *  @sou_id: webservice sou_id
    * @return
    *  sou_idcrm: smartcenter source id | 0 if error
   */
  public function getSouIdcrm($sou_id){
    if(!empty($sou_id)){
      $data = [ 0 => $sou_id];
      $db = $this->container->db_webservice;
      $sql = "SELECT sou_idcrm FROM webservice.sources WHERE sou_id = ?;";

      $r = $db->selectPrepared($sql, $data);

      if(!is_null($r)){
        return $r[0]->sou_idcrm;
      }
    }
    $this->sendAlarm("Can't retrieve Leontel source for {$sou_id} value.");
    return 0;
  }

  /**
    * Returns smartcenter leatype_id for the provided input
    * @params:
    *  @leatype_id: webservice leatype_id
    * @return
    *  leatype_idcrm: smartcenter type id | 0 if error
   */
  public function getTypeIdcrm($leatype_id){
    if(!empty($leatype_id)){
      $data = [ 0 => $leatype_id];
      $db = $this->container->db_webservice;
      $sql = "SELECT leatype_idcrm FROM webservice.leadtypes WHERE leatype_id = ?;";

      $r = $db->selectPrepared($sql, $data);

      if(!is_null($r)){
        return $r[0]->leatype_idcrm;
      }
    }
    $this->sendAlarm("Can't retrieve Leontel type for {$leatype_id} value.");
    return 0;
  }

  /*
    * Valida si el formato de un número de teléfono es válido.
    * Criterios: 9 números, empezando por 5,6,7,8 o 9
    * param:
    * @valor: nº telefono a validar
    * return:
    * @boolean
   */
  public function phoneFormatValidator($valor){
    $expresion = '/^[9|6|7|8|5][0-9]{8}$/';

    if(preg_match($expresion, $valor)){
      return true;
    }else{
      return false;
    }
  }

  /*
    * Devuelve parametros url e ip. Ampliable a más parámetros en un futuro
    * @params => objeto Request
    * @returns => array con parametros url e ip
    *  o null si objeto Request está vacío
   */
  public function getServerParams($request){
    if(!empty($request)){
      $serverParams = $request->getServerParams();
      $url = null;
      if(array_key_exists("HTTP_REFERER", $serverParams)){
        $url = $serverParams["HTTP_REFERER"];
      }
      $ip = $serverParams["REMOTE_ADDR"];

      $device = $serverParams["HTTP_USER_AGENT"];

      return array($url, $ip, $device);
    }
    return null;
  }

  public function checkGclid($data) {
    if(is_object($data)){
      $gclid = array_key_exists("gclid", $data) ? $data->gclid : null;
      if(!empty($gclid)){
        return true;
      }
    } else if (is_array($data)) {
      $gclid = array_key_exists("gclid", $data) ? $data['gclid'] : null;
      if(!empty($gclid)){
        return true;
      }
    }
    return false;
  }

  /**
   * isCampaignOnTime checks if the campaign is on time for the actual day
   * @param
   *  @int => sou_id of the campaign (webservice)
   * @return
   *  @array => result => bool
   */
  public function isCampaignOnTime($sou_id) {
    $url = $this->container->dev ?
    "https://ws.josedelrio85.es/smartcenter/timetable/isCampaignOnTime"
    :
    "http://127.0.0.1:80/report-panel-api/timetable/isCampaignOnTime";
    // this is the URL for prod environment!

    $data = [
      "sou_id" => $this->getSouIdcrm($sou_id),
    ];
    $response = json_decode($this->curlRequest($data, $url));
    if(is_object($response)){
      return $response->result;
    }
    return false;
  }

  /**
   * isLeadOpen checks if there is an open lead in smartcenter
   * @param
   *  @object => Lead model
   * @return
   *  @array => success => bool
   */
  public function isLeadOpen($lead) {
    $url = $this->container->dev ?
    "https://ws.josedelrio85.es/lead/smartcenter/isopen"
    :
    "http://127.0.0.1:80/send-lead-leontel-api/smartcenter/isopen";
    // this is the URL for prod environment!

    $data = [
      "lea_source" => $this->getSouIdcrm($lead->getSouId()),
      "lea_type" => $this->getTypeIdcrm($lead->getLeaType()),
      "TELEFONO" => $lead->getLeaPhone(),
    ];
    
    $response = $this->curlRequest($data, $url);
    if(!is_null($response)){
      $resp = json_decode($response);
      return $resp->success;
    }
    return false;
  }

  /**
   * curlRequest makes a POST request to an external endpoint
   * @param
   *  @data => array with the input data
   *  @url => URL endpoint
   * @return
   *   @string => response of the endpoint
   */
  public function curlRequest($data, $url) {
    $params = json_encode($data);
    $headers = array("Content-Type: application/json;");
    $options = array(
      CURLOPT_RETURNTRANSFER => true,   // return web page
      CURLOPT_HEADER         => false,  // don't return headers
      CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
      CURLOPT_CONNECTTIMEOUT => 10,    // time-out on connect
      CURLOPT_TIMEOUT        => 10,    // time-out on response
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $params,
      CURLOPT_HTTPHEADER      => array('Content-Type:application/json'),
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $content  = curl_exec($ch);
    curl_close($ch);

    return $content;
  }

  /**
   * sendAlarm send an alarm to VictorOps plattform
   * @param
   *  @data => array with the alarm data
   */
  public function sendAlarm($message) {
    $url = "https://alert.victorops.com/integrations/generic/20131114/alert/2f616629-de63-4162-bb6f-11966bbb538d/test";

    $state = "INFO";

    $params = [
      "message_type" => $state,
      "entity_state" => $state,
      "entity_id" => "API_webservice_exception",
      "entity_display_name" => "API_webservice_exception",
      "state_message" => $message,
      "state_start_time" => time(),
    ];

    $this->curlRequest($params, $url);
  }
}