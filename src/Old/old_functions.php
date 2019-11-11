<?php

namespace App\Old;

use App\Libraries\UtilitiesConnection;

class Oldfunctions {

  private $dev = null;
  private $container = null;

  public function __construct($dev, $container){
    $this->dev = $dev;
    $this->container = $container;
  }

  /**
    * Devuelve el sou_id apropiado en función del contenido recibido para
    * los parámetros gclid, domain y utm_source
  */
  public function getSouidMicrosoft($utm_source, $tipo, $gclid){
    if(!empty($gclid)){
      //Google
      switch($tipo){
        // Recomendador => 1
        case 1:
          $sou_id = 49;
        break;
        // Ofertas => 2
        case 2:
          $sou_id = 50;
        break;
        //FichaProducto => 3
        case 3:
          $sou_id = 51;
        break;
        case 4:
          //Microsoft Mundo R
          $sou_id = 25;
        case 5:
          //Microsoft Hazelcambio
          $sou_id = 46;
        break;
        case 6:
          //Calculadora
          $sou_id = 48;
        break;
      }
    }else{
      switch($tipo){
        // Recomendador
        case 1:
          $sou_id = 49;
        break;
        // Ofertas
        case 2:
          $sou_id = 50;
        break;
        // FichaProducto
        case 3:
          $sou_id = 51;
        break;
        //Microsoft Mundo R
        case 4:
          $sou_id = 25;
        break;
        //Microsoft Hazelcambio
        case 5:
          $sou_id = 46;
        break;
        //Calculadora
        case 6:
          $sou_id = 48;
        break;
      }
    }      
    return $sou_id;
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
}