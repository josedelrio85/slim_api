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
    * @array lead: lead data
    * @object db: [optional] database instance
    * @bool leontel: [optional] if smartcenter insert is not needed
    * @return array with success result (bool) and a descriptive message (string)  
  */
  public function prepareAndSendLeadLeontel($lead, $dbinput = null, $leontel = false){
    if(is_array($lead)){

      $db = is_null($dbinput) ? $this->container->db_webservice : $dbinput;
      $error = 'KO';

      if (empty($lead['lea_phone']) || is_null($lead['lea_phone'])){
        return json_encode(['success'=> false, 'message'=> $error]);
      }
  
      $data = [
        0 => $lead['lea_phone']
      ];
  
      $sqlone = "SELECT count(*) as phoneExists FROM {$this->container->leads_table} where lea_phone = ? and date(lea_ts) = curdate();";
      $rone = $db->selectPrepared($sqlone, $data, true);
  
      if($rone[0]['phoneExists'] > 0) {
        $sqltwo = "SELECT TIMESTAMPDIFF(SECOND, lea_ts, NOW()) as diff FROM {$this->container->leads_table} where lea_phone = ? and date(lea_ts) = curdate() ORDER BY lea_id desc limit 1;";
        $rtwo = $db->selectPrepared($sqltwo, $data, true);
  
        if($rtwo[0]['diff'] > 60) {
          // If the difference between last lead and actual interaction is > 60 seconds, insert the lead but not send to Leontel as it is a duplicated one.
          $resp = $this->createLead($lead);
          $message = 'DUPLICATED-'.$resp->message;
          return json_encode(['success'=> true, 'message'=> $message]);
        }
      } else {
        $resp = $this->createLead($lead);
        if ($resp->success) {
          $smartcenter = $leontel ? LeadLeontel::sendLead($lead, $db, $this->container) : null;
          $lastid = $resp->message;
          return json_encode(['success'=> true, 'message'=> $lastid]);
        }
      }
    }
    return json_encode(['success'=> false, 'message'=> $error]);
  }

  /**
   * createLead formats the array passed as param and creates an insert prepared statement
   * @array $lead: data lead
   * @return array with success result (bool) and a descriptive message (string)
   */
  public function createLead($lead) {
    $db = $this->container->db_webservice;
    $params = UtilitiesConnection::getParametros($lead,null);
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
    *  sou_idcrm: smartcenter source id
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
    return null;
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
   *  @array => key => result | value => bool
   */
  public function isCampaignOnTime($sou_id) {
    $url = $this->container->dev ? 
    "https://ws.bysidecar.es/smartcenter/timetable/isCampaignOnTime"
    :
    "http://127.0.0.1:80/report-panel-api/index.php/timetable/isCampaignOnTime";

    $data = [
      "sou_id" => $this->getSouIdcrm($sou_id),
    ];
    $response = $this->curlRequest($data, $url);
    return json_encode($response);
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
}  