<?php

namespace App\Functions;

use SoapClient;
use App\Libraries\UtilitiesConnection;

/**
 * Description of LeadLeontel
 *
 * @author Jose
 */
class LeadLeontel {

  private static $locationWs = "http://192.168.50.102/webservice/index.php";
  private static $uriWs = "http://wsLeads";
  private $ws = null;
  private $wsCred = null;
  
  function __construct($params){
    if(is_array($params) && array_key_exists("location", $params) && array_key_exists("uri", $params)){
      $this->ws = self::invokeWSLeontel($params);
    }else{
      $this->ws = self::invokeWSLeontel();
    }
  }
  
  public static function sendLead($lead, $db, $container){
    $dev = $container->dev;
    $leads_table = $container->leads_table;

    if(!empty($lead->getSouId()) && !is_null($lead->getSouId())){
      $id_origen_leontel =$container->funciones->getSouIdcrm($lead->getSouId());
      $id_tipo_leontel = $container->funciones->getTypeIdcrm($lead->getLeaType());

      $leontel = [
        'TELEFONO' => $lead->getLeaPhone(),
        'url' => $lead->getLeaUrl(),
        'wsid' => $lead->getLeaId(),
        'ip' => $lead->getLeaIP(),
        'observaciones2' => $lead->getObservations(),
        'Email' => $lead->getLeaMail(),
        'nombre' => $lead->getLeaName(),
        'url' => $lead->getLeaUrl(),
        'ip' => $lead->getLeaIP(),
      ];      
      
      if(!$dev){
        $ws = self::invokeWSLeontel();
        $container->utilities->infoLog('send Lead Leontel '.$lead->getLeaPhone());
        $response = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $leontel);
        $lea_status = "SENT";
      }else{
        $response["success"] = true;
        $response["id"] = random_int((PHP_INT_MAX-(random_int(0,1000))), PHP_INT_MAX);
        $lea_status = "PRUEBA";
      }

      $datos = $response['success'] ? 
        ["lea_extracted" => date("Y-m-d H:i:s"), "lea_crmid" => $response["id"], "lea_status" => $lea_status]
        :
        ["lea_crmid" => "ERROR", "lea_status" => "ERROR"];

      $where = ["lea_id" => $lead->getLeaId()];
      $parametros = UtilitiesConnection::getParametros($datos, $where);
      $container->utilities->infoLog('update Lead CRMID =>'.$datos['lea_crmid']);
      $result = json_decode($db->updatePrepared($leads_table, $parametros));

      return json_encode(['success'=> $result->success, 'message'=> $result->message]);      
    }
    return json_encode(['success'=> false, 'message'=> 'No results']);
  }
  
  private static function invokeWSLeontel($params = null){
    $params = $params ? $params : ["location" => self::$locationWs, "uri" => self::$uriWs];

    $ws = new SoapClient(null,[
      "location" => $params["location"],
      "uri" => $params["uri"],
      "compression" => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP]
    );
    return $ws;
  }
  
  private static function invokeWSLeontelWithCredentials(){
    $credentials = array(
      'login'=> "login",
      'password'=> "pass",
      'location'=>"http://192.168.50.102/webservice",
      'uri'=>"LeonTel",
      'soap_version'=>SOAP_1_2
    );
    $webservice = new SoapClient(null,$credentials);
    return $webservice;
  }

  /***********************************/
  /************ EVO BANCO ************/
  /***********************************/
  public static function sendLeadEvo($data, $db, $dev){
                        
    $datos = [
      0 => "LEONTEL",
      1 => NULL,
      2 => NULL,
      3 => ''
    ];

    $query = "SELECT "
      . "even_id,"
      . "PERSONMOBILEPHONE,"
      . "CLIENT_ESTADO__C,"
      . "URL_SALESFORCE";
      //    . "LOGALTY_ESTADO__C,"
      //    . "STEPID";

    $queryFromWhere = " FROM evo_events_sf_v2_pro "
      . "WHERE "
      . "even_destiny = ? "
      . "AND even_extracted <=> ? "
      . "AND even_status <=> ? "
      . "AND PERSONMOBILEPHONE <> ? ";

    $query .= $queryFromWhere;
    $query .= " ORDER BY even_id LIMIT 1;";

    $r = $db->selectPrepared($query, $datos);

    if(!is_null($r)){

      $id_origen_leontel = 4;

      $lea_id = $r[0]->even_id;
      $phone = $r[0]->PERSONMOBILEPHONE;
      $client_estado_c = $r[0]->CLIENT_ESTADO__C;
      $url_salesforce = $r[0]->URL_SALESFORCE;

      //    $stepid = $r[0]->STEPID;   
      //    $logalty_estado_c = $r[0]->LOGALTY_ESTADO__C;

      $array_tipo_leontel = self::getIdTipoLeontel($data["LOGALTY_ESTADO__C"], $data["CLIENT_ESTADO__C"], $data["STEPID"], $data["CONTRACTSTATUS"]);
      $id_tipo_leontel = $array_tipo_leontel["idTipoLeontel"];

      $lead = [
        'TELEFONO' => $phone,
        // 'observaciones' => $stepid,
        'observaciones' => $client_estado_c,
        'url' => $url_salesforce,
        'wsid' => $lea_id
      ];

      $wsCred  = self::invokeWSLeontelWithCredentials();                

      if(!$dev){
        $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel,$id_tipo_leontel,$phone);
      }else{
        $dataCred["success"] = true;
      }

      if($dataCred["success"]){
        $datosDup = ["even_status" => "DUPLICATED"];
        $whereDup = ["even_id" => $lea_id];
        $parametrosDup = UtilitiesConnection::getParametros($datosDup, $whereDup);

        $resultDup = $db->updatePrepared("webservice.evo_events_sf_v2_pro", $parametrosDup);
        $res = json_decode($resultDup);
      }else{
        $ws = self::invokeWSLeontel();
        if(!$dev){
          $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
          $even_status = "SENT";
        }else{
          $retorno["success"] = true;                
          $retorno["id"] = 9999;
          $even_status = "PRUEBA";
        }  

        if($retorno["success"]){
          $datos = [
            "even_extracted" => date("Y-m-d H:i:s"),
            "even_crmid" => $retorno["id"],
            "even_status" => $even_status
          ];
        }else{
          $datos = [
            "even_crmid" => "ERROR",
            "even_status" => "ERROR"
          ];
        }

        $result = self::updateDuplicatedEvo($db, $lea_id, $datos);
        $res = json_decode($result);
        return json_encode(['success'=> $res->success, 'message'=> $res->message]);
      }
      return json_encode(['success'=> false, 'message'=> 'Last status false.']);      
    }
    return json_encode(['success'=> false, 'message'=> 'No results']);
  }

  /*
    * Proceso para generar leads en Leontel que cumplan los requisitos del Pago Recurrente.
    * Hay dos tipos:leads y clientes. Una consulta para cada uno. Se recuperan leads que cumplan
    * condiciones, se crean unos nuevos a partir de estos, y en el campo observaciones2 de los 
    * primeros se actualiza con el valor del idlead generado.
    * @params:
    *      tipo: cliente/lead
    *      db: instancia de crmti
    * @return:
    *      @boolean
    *      @string: tipo volcado (cliente/lead)
    *      @array: array id's tratados
    */
  public static function sendLeadLeontelPagoRecurrente($tipo, $db, $dev){
    $test = "SET NAMES 'utf8';";
    $db->Query($test);

    $salida = array();
    $nombreFunc = "";

    if($tipo == "cliente"){
      $arrSources = array(2,9,10,13,29,30);
      $datos = [
        0 => $arrSources,
        1 => 732,
        2 => NULL,
        3 => '2018-06-01'
      ];
      $questionsA = UtilitiesConnection::generaQuestions($arrSources);
      
      $sql = "
        SELECT 
            hh.his_id, 
            hh.his_ts, 
            hh.his_user, 
            hh.his_code,
            hh.his_cdrid, 
            ll.lea_id, 
            ll.lea_source,
            ll.lea_type,
            ll.lea_ts, 
            ll.TELEFONO, 
            ll.nombre, 
            ll.apellido1,
            ll.apellido2, 
            ll.dninie, 
            c.numerocliente,
            CONCAT('Cliente que llama a recepción el día ',date(hh.his_ts),' para solicitar activación pago recurrente') as obsalt
        FROM his_history hh 
        INNER JOIN lea_leads ll ON hh.his_lead = ll.lea_id
        INNER JOIN ord_orders oo ON hh.his_order = oo.ord_id
        INNER JOIN cli_clients c ON oo.ord_client = c.cli_id 
        WHERE 
        ll.lea_source IN ($questionsA)
        AND hh.his_sub = ?
        AND ll.observaciones2 <=> ?                    
        AND date(ll.lea_ts) >= ?;
      ";
      $nombreFunc = "sendLeadLeontelPagoRecurrente_cliente";
    }else if($tipo == "leads"){
      $arrSources = array(9,10);
      
      $datos = [
        0 => $arrSources,
        1 => 732,
        2 => NULL,
        3 => '2018-06-01'
      ];

      $questionsA = UtilitiesConnection::generaQuestions($arrSources);
      
      $sql = "
        SELECT 
          hh.his_id, 
          hh.his_ts, 
          hh.his_user, 
          hh.his_code,
          hh.his_cdrid, 
          ll.lea_id, 
          ll.lea_source,
          ll.lea_type,
          ll.lea_ts, 
          ll.TELEFONO, 
          ll.nombre, 
          ll.apellido1,
          ll.apellido2, 
          CONCAT('Cliente que llama a recepción el día ',date(hh.his_ts),' para solicitar activación pago recurrente') as obsalt
        FROM his_history hh 
        INNER JOIN lea_leads ll on hh.his_lead = ll.lea_id
        WHERE 
        ll.lea_source IN ($questionsA)
        AND hh.his_sub = ?
        AND ll.observaciones2 <=> ?                    
        AND date(ll.lea_ts) >= ?;
      ";
    
      $nombreFunc = "sendLeadLeontelPagoRecurrente_cliente";            
    }
    
    $result = $db->selectPrepared($sql, $datos);
    
    if(!is_null($result)){
      foreach($result as $k => $r){
        $id_origen_leontel = 31;
        $id_tipo_leontel = 8;
        $lea_id = $r->lea_id;
        $phone = $r->TELEFONO;
        $nombre = $r->nombre;
        $apellido1 = $r->apellido1;
        $apellido2 = $r->apellido2;
        $obsalt = $r->obsalt;

        $lead = [
          'TELEFONO' => $phone,
          'nombre' => $nombre,
          'apellido1' => $apellido1,
          'apellido2' => $apellido2,
          'observaciones' => $obsalt
        ];
        
        if($tipo == "cliente"){
          $dninie = $r->dninie;
          $numerocliente = $r->numerocliente;
          
          $lead['dninie'] = $dninie;
          $lead['observaciones'] = $obsalt ."//". $numerocliente;
        }

        $ws = self::invokeWSLeontel();
        if(!$dev){
          $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
        }else{
          $retorno["success"] = true;
          $retorno["id"] = 9999;
        }

        if($retorno["success"] == true){
          $datos = [
            "observaciones2" => $retorno["id"]
          ];

          $where = ["lea_id" => $lea_id];
          $parametros = UtilitiesConnection::getParametros($datos, $where);
          $result = $db->updatePrepared("crmti.lea_leads", $parametros);               
          array_push($salida, $lea_id);
        }
      }
      $success = true;
    }else{
      $success = false;
    }
    return json_encode(["success" => $success, "message" => $nombreFunc, "salida" => $salida ]);
  }

  /* 
    * Se implementa lógica de sendLeadToLeontelRecoveryV2_Pro, simplificando y
    * reutilizando código.
   */
  public static function recoveryEvoBancoLeontel($db, $dev){
    $ws = self::invokeWSLeontel();                
    $wsCred = self::invokeWSLeontelWithCredentials();
    
    $datos = [
      "even_destiny" => 'LEONTEL',
      "even_extracted" => NULL,
      "even_status" => NULL,
      "PERSONMOBILEPHONE" => ''
    ];
    
    $selectLeads = "SELECT "
      . "even_id,"
      . "PERSONMOBILEPHONE,"
      . "CLIENT_ESTADO__C,"
      . "URL_SALESFORCE,"
      . "LOGALTY_ESTADO__C,"
      . "STEPID,"
      . "CONTRACTSTATUS "; 
    
    $where = "FROM "
      . "evo_events_sf_v2_pro "
      . "WHERE "
      . "even_destiny = ? "
      . "AND even_extracted <=> ? "
      . "AND even_status <=> ? "
      . "AND PERSONMOBILEPHONE <> ? "; 
            
    $queryLeads = $selectLeads. $where . ";";
            
    $r = $db->selectPrepared($queryLeads, $datos);
    
    $salida = array();
    
    if(!empty($r)){
      foreach($r as $k => $v){
        $id_origen_leontel = 4;

        $lea_id = $v->even_id;
        $phone = $v->PERSONMOBILEPHONE;
        $estado_cliente = $v->CLIENT_ESTADO__C;
        $url_salesforce = $v->URL_SALESFORCE;
        $stepid = $v->STEPID;
        $estado_logalty = $v->LOGALTY_ESTADO__C;
        $estado_contrato = $v->CONTRACTSTATUS;

        $destinyF = self::getIdTipoLeontel($estado_logalty, $estado_cliente, $stepid, $estado_contrato);

        $lead = [
          'TELEFONO' => $phone,
          'observaciones' => $estado_cliente,
          'url' => $url_salesforce,
          'wsid' => $lea_id
        ];

        $tipo_pdte_firma = 18;
        $tipo_eid = 19;
        $tipo_iban = 20;
        $tipo_incompleto = 22;
        $tipo_c2c = 2;

        if(!$dev){
          $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_pdte_firma, $phone);    
        }else{
          $dataCred["success"] = false;    
        }                

        if($dataCred["success"]){
          $result = self::updateDuplicatedEvo($db, $lea_id);
          array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => "tipo_pdte_firma"));
        }else{
            $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_eid, $phone);
            if($dev)
              $dataCred["success"] = false;
            if($dataCred["success"]){
              $result = self::updateDuplicatedEvo($db, $lea_id);
              array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => "tipo_eid"));
            }else{
              $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_iban, $phone);             
              if($dev)
                $dataCred["success"] = false;
              if($dataCred["success"]){
                $result = self::updateDuplicatedEvo($db, $lea_id);
                array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => "tipo_iban"));
              }else{
                $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_incompleto, $phone);

                if($dev)
                  $dataCred["success"] = false;
                
                if($dataCred["success"]){
                  $result = self::updateDuplicatedEvo($db, $lea_id);
                  array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => "tipo_incompleto"));
                }else{
                  $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_c2c, $phone);

                  if($dev)
                    $dataCred["success"] = false;
                  if($dataCred["success"]){
                    $result = self::updateDuplicatedEvo($db, $lea_id);
                    array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => "tipo_c2c"));
                  }else{
                    if($destinyF != null){
                      $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
                      $even_status = "SENT";

                      if($dev){
                        $retorno["success"] = true;                
                        $retorno["id"] = 9999;
                        $even_status = "PRUEBA";
                      }

                      if($retorno["success"]){
                        $datos = [
                          "even_extracted" => date("Y-m-d H:i:s"),
                          "even_crmid" => $retorno["id"],
                          "even_status" => $even_status
                        ];
                      }else{
                        $datos = [
                          "even_crmid" => "ERROR",
                          "even_status" => "ERROR"
                        ];
                      }

                      $result = self::updateDuplicatedEvo($db, $lea_id, $datos);              
                      array_push($salida, array("success" => $result->success, "lea_id" => $lea_id, "paso" => $id_tipo_leontel));
                    }
                  }
                }
              }
            }
        }
      }       
    }
    return $salida;
  }
  
  /*
    * Update en evo_events_sf_v2_pro. Si $datos es null, se pone eve_status a DUPLICATED.
    * Creada debido al uso reiterativo de esta instrucción.
    * @params: 
    *  $db->instancia BD
    *  $lea_id => int
    *  $datos => array o null
    * @return => resultado update
   */
  private function updateDuplicatedEvo($db, $lea_id, $datos = null){     
    if(is_null($datos)){
      $datos = [
        "even_status" => "DUPLICATED"
      ];
    }
    
    $where = ["even_id" => $lea_id];
    $parametros = UtilitiesConnection::getParametros($datos, $where);

    $result = $db->updatePrepared("webservice.evo_events_sf_v2_pro", $parametros);
    
    return json_decode($result);
  }
  
  /*
    * Devuelve en función de los parámetros de entrada el destino y el id_tipo_leontel correspondiente.
    */
  public static function getIdTipoLeontel($LOGALTY_ESTADO__C, $CLIENT_ESTADO__C, $STEPID, $CONTRACTSTATUS){

    /*
    - SI EL PASO ES metodo-validacion y el estado cliente es potencial o pendiente revisión captación -> lo enviamos a INCOMPLETOS. si el estado cliente es activo u otro NO lo enviamos
    - SI EL PASO ES identificacion-video y el estado cliente es potencial o pendiente revisión captación -> lo enviamos A PENDIENTE EID. EN CASO DE OTRO ESTADO NADA
    - SI EL PASO ES identificacion-iban y el estado cliente es potencial o pendiente revisión captación -> lo enviamos A PENDIENTE CONFIRMA. EN CASO DE OTRO ESTADO NADA
    - SI EL PASO ES previa-firma, proceso-firma, casi-lo-tenemos, contratacion-ci, confirmacion-datos  y el estado cliente es activo, pendiente revision captacion, potencial o PENDIENTE DE ELECTRONICA ID si estado logalty es '', tiempo expirado, Pendiente Firma OTP, Cancela Cliente
    o Error de Validación OTP Logalty lo enviamos a PENDIENTE DE FIRMA
    */

    switch($STEPID){
      case 'metodo-validacion':
        switch($CLIENT_ESTADO__C) {
          case 'Potencial':
          case 'Pendiente revisión Captación':
            return [
              "destiny"=> "LEONTEL",
              // "filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelIncompletosV2.php",
              "idTipoLeontel" => 22
            ];
            break;
          default:
            return null;
        }
        break;
      case 'identificacion-video':
        switch($CLIENT_ESTADO__C) {
          case 'Potencial':
          case 'Pendiente revisión Captación':
            return [
              "destiny"=> "LEONTEL",
              // "filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteElectronicaIDV2.php"
              "idTipoLeontel" => 19
            ];
            break;
          default:
            return null; 
            break;
        }
        break;
      case 'identificacion-iban':
        switch($CLIENT_ESTADO__C) {
          case 'Potencial':
          case 'Pendiente revisión Captación':
            return [
              "destiny"=> "LEONTEL",
              //"filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteConfirmaV2.php"
              "idTipoLeontel" => 20
            ];
            break;
          default:
            return null;
            break;
        }
        break;
      case 'confirmacion-datos':
      case 'previa-firma':
      case 'proceso-firma':
      case 'casi-lo-tenemos':
      case 'contratacion-ci':
        switch($CLIENT_ESTADO__C) {
          case 'Potencial':
          case 'Pendiente revisión Captación':
          case 'Activo':
          case 'Pendiente de Electronica ID':
            switch($LOGALTY_ESTADO__C) {
              case '':
              case 'Tiempo Expirado':
              case 'Cancela Cliente':
              case 'Error de Validación OTP Logalty':
              case 'Pendiente Firma OTP':
                switch ($CONTRACTSTATUS){
                  case 'Pre-firma':
                    return [
                      "destiny" => "LEONTEL",
                      //"filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteOTPV2.php"
                      "idTipoLeontel" => 18
                    ];
                    break;
                  case 'Firma':
                    return [
                      "destiny" => "LEONTEL",
                      //"filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteOTPV2.php"
                      "idTipoLeontel" => 18
                    ];
                    break;
                  default:
                    return NULL;
                    break;
                }
                break;
              default:
                return NULL;
                break;
            }
            break;
          default:
            return null;                
            break;
        }
        break;
      default:
        return NULL;
    }
  }
  
  /*
    * Emula consulta realizada por método Leontel WS SOAP getLeadStatus. 
    * Se implementa para no depender del método de Leontel. Tiempos de respuesta similares
    *  @params
    *   @sou_id: origen en crmti
    *   @type:  tipo en crmti
    *   @telefono: telefono  
    *   @db: instancia de crmti
    * @returns
    *   array[success: boolean, data: array/null/string]
    */
  public static function getLeadLastStatus($sou_id, $type, $telefono, $db){   
      
    if(!empty($sou_id) && !empty($type) && !empty($telefono)){
        
      $datos = [
        "lea_source" => $sou_id,
        "lea_type" => $type,
        "TELEFONO" => $telefono,
        "lea_closed" => 0
      ];
      
      $query = "SELECT "
        . "lea_leads.lea_id,"
        . "lea_leads.lea_ts,"
        . "lea_leads.lea_closed,"
        . "sub_subcategories.sub_id,"
        . "sub_subcategories.sub_description "
        . "FROM "
        . "crmti.lea_leads "
        . "INNER JOIN crmti.act_activity ON lea_leads.lea_id = act_activity.act_id "
        . "INNER JOIN crmti.sub_subcategories ON act_activity.act_last_cat = sub_subcategories.sub_id "
        . "where lea_source = ? "
        . "and lea_type = ? "
        . "and TELEFONO = ? "
        . "and lea_closed = ? "
        . "order by lea_id desc "
        . "limit 1;";
      
      $result = $db->selectPrepared($query, $datos);
      
      if(!is_null($result)){
        return array('success' => true, 'data' => $result);
      }else{
        return array('success' => false, 'data' => null);
      }
    }
    return array('success' => false, 'data' => 'paramError');
  }
  
  public static function testLeadStatus(){
              
      $id_origen_leontel = 4;
      $phone = "600633058";

      $tipo_pdte_firma = 18;
      $tipo_eid = 19;
      $tipo_iban = 20;
      $tipo_incompleto = 22;
      $tipo_c2c = 2;
      
      
      $wsCred = self::invokeWSLeontelWithCredentials();
      
      sleep(2);
      $dataCred = $wsCred->getLeadLastStatus($id_origen_leontel, $tipo_c2c, $phone);
      
      return $dataCred;
  }
}
