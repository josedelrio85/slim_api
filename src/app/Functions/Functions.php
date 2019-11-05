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
  



  /** REMOVE CREDITEA 8/11/2019 09:00 AM */

  /*
    *  Se evalúa en la tabla crmti.lea_leads y crmti.his_history la existencia de algún registro 
    * 	que cumpla con las siguiente condiciones:
    * 		- lea_source perteneciente al sou_id recibido por parametro
    * 		- lea_ts periodo inferior a 1 mes
    * 		- lea asnef = SI ó his_sub dentro del rango indicado
    * 		- que existe algún lead para el dni o telefono
    * 	@parametros:
    * 		- sou_id del origen a evaluar
    * 		- dni valido
    * 		- telefono valido
    * 	@returns:
    *  	- En caso de que exista algún registro que cumpla las condiciones, devuelve objeto JSON:
    * 			{
    *                      "result":true,
    *                      "data": "KO-notValid"
    * 			}
    * 		- En caso contrario de que no haya resultados, devuelve objeto JSON:
    * 			{
    *                      "result":false,
    *                      "data": true
    * 			}
  */
  public function checkAsnefCreditea($data){
    $a = $data['sou_id'];
    $b = $data['documento'];
    $c = $data['phone'];
    $settings = $this->container->settings_db_crmti;
    $db = new \App\Libraries\Connection($settings);

    if(!empty($a) && !empty($b) && !empty($c)){
      //db tiene que ser report panel        
      $previa = [
        0 => $data['sou_id']
      ];
      $sqlPrevia = "select distinct(left(sou_description,5)) 'categoria' from crmti.sou_sources where sou_id = ?;";      
      $previaSC = $db->selectPrepared($sqlPrevia, $previa);
      $categ = $previaSC[0]->categoria;

      if(!empty($categ)){
        $sqlPrevSources = "select sou_id from crmti.sou_sources where sou_description like ? ;";
        $datosPrevSources = [
          0 => "%".$categ."%"
        ];
        
        $resultSC = $db->selectPrepared($sqlPrevSources, $datosPrevSources, true);
        $paramPrevSources = UtilitiesConnection::arrayToPreparedParam($resultSC, "sou_id");

        $datosPrevIds = [
          0 => "%".$data['documento']."%",
          1 => $data['phone']
        ];

        $sqlPrevIds = "SELECT lea_id FROM crmti.lea_leads where dninie like ? OR TELEFONO = ? ;";
        $resultSI = $db->selectPrepared($sqlPrevIds, $datosPrevIds, true);
        $paramPrevIds = UtilitiesConnection::arrayToPreparedParam($resultSI, "lea_id");

        $fecha = new \DateTime();
        $fecha->sub(new \DateInterval('P1M')); // 1 mes
        $dateMySql  = $fecha->format('Y-m-d');

        $arrSubid = array(383,385,386,387,388,389,390,391,393,394,400,402,403,404,405,407,411,499,501,502,503,504,505,507,508,510,511,512,513,514,515,516,519,521,522,523,524,525,526,527,528,530,531,532,533,536,537,542,543,544,549,550,553,556,557,616,617,618,620,621,622,623,624,625,646,647,648,649,650,674,675,676,677,678,679,680,681,682,683,684,686,687);
        
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
      }  
    }else{
      return json_encode(['success'=> true, 'message' => 'KO-paramsNeeded']);
    }
    return null;
  
  }   
  
  /*
    *  Se evalúa que no haya registros en webservice.leads_oldschool, para el par DNI-telefono, que tengan
    *  asnef/yacliente dentro del periodo inferior a 1 mes para el día actual. 
    *  @parametros:
    *  - sou_id del origen a evaluar
    *  - dni valido
    *  - telefono valido
    *  @returns:
    *  - En caso de que exista algún registro que cumpla las condiciones, devuelve objeto JSON:
    *  {
    *      "result":true,
    *      "data": "KO-notValid"
    *  }
    *  - En caso contrario de que no haya resultados, devuelve objeto JSON:
    *  {
    *      "result":false,
    *      "data": true
    *  }
  */
  public function checkAsnefCrediteaPrev($data){
    $a = $data['sou_id'];
    $b = $data['documento'];
    $c = $data['phone'];
    $db = $this->container->db_webservice;

    if(!empty($a) && !empty($b) && !empty($c)){  
      $fecha = new \DateTime();
      $fecha->sub(new \DateInterval('P1M')); // 1 mes
      $dateMySql = $fecha->format('Y-m-d');
          
      $datos = [
        0 => $a,
        1 => '',
        2 => $dateMySql,
        3 => "%".$b."%",
        4 => $c,
        5 => 'Check Cliente marcado.',
        6 => 'Check Asnef marcado.',
      ];
      
      $sql = " SELECT ll.lea_id "
        ."FROM ".$this->container->leads_table." ll "
        ."WHERE "
        ."ll.sou_id = ? "
        ."AND (ll.lea_destiny is null OR ll.lea_destiny = ?) "
        ."AND DATE(ll.lea_ts) > ? "
        ."AND (ll.lea_aux1 LIKE ? OR ll.lea_phone = ?) "
        ."AND (ll.lea_aux3 = ? OR ll.lea_aux3 = ?);";
      
      $result = $db->selectPrepared($sql, $datos);

      if(!is_null($result)){
        return json_encode(['success'=> true, 'message' => 'KO-notValid_pre']);
      }else{
        return json_encode(['success'=> false, 'message' => true]);
      }                
    }else{
      return json_encode(['success'=> true, 'message' => 'KO-paramsNeeded']);
    }
  }
  
  /**
    * Sends a lead to webservice.leads_oldschool table throw wsInsertLead stored procedure
    * @params
    *  - params (array) => array with the data to insert. One of the params must be phone.
    * @return (array) => success (bool); message (string)
  */
  public function sendLeadToWebservice($lead){
    if(!empty($lead)){
      // $phone = $lead["datos"]["lea_phone"];

      $this->prepareAndSendLeadLeontel($lead, null, true);
    }
    return json_encode(['success'=> false, 'message'=> 'Error in params.']);
  }
  /** */


  

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
      return json_encode(['success'=> false, 'message'=> $error]);
    }
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