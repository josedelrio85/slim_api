<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Functions;

use App\Libraries\UtilitiesConnection;

class Functions {
    
    /*
     * Función para obtener horario de atención para C2C.
     * Params: 
     * @data: (1) array que puede contener sou_id + num_dia + hora => para consultar si hay atención en un momento determinado
     *  ó bien
     * (2) array que contiene sou_id => obtener horario de la semana
     * @db => instancia bd
     * return:
     * si (1)  => array
     * si (2) => array que contiene horario con indices 'primerDia' y 'ultimoDia' en caso de que exista horario para el sou_id, hora y dia recibido, 
     * o null en caso contrario
     */
    public function consultaTimeTableC2C($data, $db){
        
        $laborable = 1;

        if(array_key_exists('hora', $data)){
            
            $datos = [
                0 => $laborable, 
                1 => $data['sou_id'], 
                2 => $data['num_dia'], 
                3 => $data['hora'], 
                4 => $data['hora']];
            
            $sql = "SELECT * FROM report_panel.c2c_timetable WHERE  laborable = ? and sou_id  = ? and num_dia = ? and h_ini <= ? and h_fin >= ? ;";
        }else{                       

            $datos = [
                0 => $laborable, 
                1 => $data['sou_id'], 
                2 => $laborable, 
                3 => $data['sou_id'], 
                4 => $laborable, 
                5 => $data['sou_id']];

            $sql = "SELECT * FROM report_panel.c2c_timetable WHERE laborable = ? and sou_id  = ? and
            (num_dia = (select min(num_dia) from report_panel.c2c_timetable where laborable = ? and sou_id= ? )
            or num_dia = (select max(num_dia) from report_panel.c2c_timetable where laborable = ? and sou_id= ? ));";
        }
        
        $r = $db->selectPrepared($sql, $datos);
        
        if(!is_null($r)){
            $aux = 0;
            $i = 0;
            $indice = "primerDia";
            
            foreach($r as $obj){
                if($i == 0){
                    $elements[$indice][$i] = $obj;
                }else{
                    if($aux == $obj->num_dia){
                        $elements[$indice][$i] = $obj;
                    }else{
                        $indice = "ultimoDia";
                        $i = 0;
                        $elements[$indice][$i] = $obj;
                    }
                }
                $aux = $obj->num_dia;
                $i++;
            }
            return $elements;
        }
        return null;
    }
    
    public function horarioEntradaLeads($sou_id, $db){
        $diaSemana = intval(date('N'));
        $horaActual = date('H:i');

        //sou_id de report_panel!!!
        $data = ['sou_id'=> $sou_id, 'num_dia'=> $diaSemana, 'hora'=> $horaActual];

        $result = $this->consultaTimeTableC2C($data, $db);

        if(is_array($result)){
            return true;
        }
        return false;
    }
    
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
    public function checkAsnefCreditea($data, $db){
        $a = $data["sou_id"];
        $b = $data["documento"];
        $c = $data["phone"];
        
        if(!empty($db)){
            if(!empty($a) && !empty($b) && !empty($c)){
                //db tiene que ser report panel        
                $previa = [
                    0 => $data["sou_id"]
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
                        0 => "%".$data["documento"]."%",
                        1 => $data["phone"]        
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
                return json_encode(['result'=> false, 'data' => 'KO-paramsNeeded']);
            }
            return null;
        }
    }   
    
    /*
     * Encapsulación de la lógica de inserción de lead (inserción en webservice.leads 
     * a través de stored procedure + envío a la cola de Leontel a través de WS SOAP de Leontel).
     * params:
     * @datos: array con conjunto de datos a insertar en bd
     * @db: instancia bd
     * @tabla: por si hay que hacer la inserción en otra tabla
     */
    public function prepareAndSendLeadLeontel($datos,$paramsDb,$tabla = null, $leontel = true){
        
        if(is_array($datos) && !is_null($paramsDb)){
            
            $db = new \App\Libraries\Connection($paramsDb);
            
            if($tabla == null){
                $tabla = "leads";
            }

            $parametros = UtilitiesConnection::getParametros($datos,null);    
            $query = $db->insertStatementPrepared($tabla, $parametros);
            $sp = 'CALL wsInsertLead("'.$datos["lea_phone"].'", "'.$query.'");';

            $result = $db->Query($sp);           
            
            if($db->AffectedRows() > 0){
                $resultSP = $result->fetch_assoc();
                $lastid = $resultSP["@result"];
                $db->NextResult();
                $result->close();
                
                $leontel ? LeadLeontel::sendLead($datos,$db) : null;
                
                $db->close();
                //Hay que devolver el resultado de la inserción en webservice.leads, no el update de Leontel
                return json_encode(['success'=> true, 'message'=> $lastid]);
            }else{
                return json_encode(['success'=> false, 'message'=> $db->LastError()]);
            }            
        }
        return json_encode(['success'=> false, 'message'=> '??']);
    }
    
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
                LeadLeontel::sendLeadEvo($datos,$db);
                exit(json_encode(['success'=> true, 'message'=> $r->message]));
            }else{
                exit(json_encode(['success'=> false, 'message'=> $r->message]));
            }
        }else{
            return json_encode(['success'=> false, 'message'=> '??']);
        }
    }
    
    /*
     * Obtiene el sou_id de crmti en función del sou_id de webservice
     * params:
     * @sou_id: sou_id de webservice
     * @db: instancia bd
     * return:
     * @sou_idcrm (sou_id de crmti)
     */
    public function getSouIdcrm($sou_id, $db){
        if(!empty($sou_id)){
            $datos = [ 0 => $sou_id];
            
            $sql = "SELECT sou_idcrm FROM webservice.sources WHERE sou_id = ?;";
            
            $r = $db->selectPrepared($sql, $datos);
            
            if(!is_null($r)){
                return $r[0]->sou_idcrm;
            }
            return null;
        }
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
    
    public function test($db){
              
        $sqlPrevSources = "select sou_id from crmti.sou_sources where sou_description like ? and sou_active = ? ;";
        
        $datosPrevSources = [
            0 => "%CREDI%",
            1 => 1
        ];

        $resultSC = $db->selectPrepared($sqlPrevSources, $datosPrevSources, true);
        
        $paramPrevSources = UtilitiesConnection::arrayToPreparedParam($resultSC, "sou_id");

        $datos = [
            0 => $paramPrevSources["values"]
        ];
        
        $questions = $paramPrevSources["questions"];
        
        $sql = "SELECT * "
        . "FROM crmti.lea_leads ll "
        . "WHERE "
        . "ll.lea_source IN ($questions) "
        . "LIMIT 10;";
                
        $result = $db->selectPrepared($sql, $datos);
        return $result;
    }
}