<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
    
    public static function sendLead($data, $db){
               
        if(array_key_exists('sou_id', $data)){
            
            $datos = [
                0 => "LEONTEL",
                1 => NULL,
                2 => NULL,
                3 => $data['sou_id'],
            ];
/*  
 * webservice
sou_id                              sou_idcrm
1	CREDITEA ABANDONADOS        2
2	CREDITEA STAND              3
4	CREDITEA TIMEOUT            5
5	R CABLE                     6
9	CREDITEA END TO END         13
10	CREDITEA FB                 14
11	CREDITEA RASTREATOR         15
		
 * crmti
2	CREDITEA ABANDONOS          
3	CREDITEA STAND	
5	CREDITEA TIMEOUT	
6	R CABLE	
9	CREDITEA RECEPCION	
10	CREDITEA RECEPCION NO CLI	
13	CREDITEA END TO END	
14	CREDITEA FB	
15	CREDITEA RASTREATOR	
17	CREDITEA DISPOSICION DE EFECTIVO	
		
*/		
            switch($data["sou_id"]){
                case 1:
                    //Abandonos
                    $querySource = "l.lea_surname,"
                        . "l.lea_aux2 dninie,"
                        . "UPPER(l.lea_aux3) asnef,"
                        . "l.lea_url,"
                        . "l.lea_ip";
                    break;
                case 9:
                    //EndToEnd
                    $querySource = "l.lea_mail,"
                        . "l.lea_url,"
                        . "l.lea_aux1,"
                        . "l.lea_aux2 ";
                    break;
                case 10:
                    //FB
                    $querySource = "l.lea_mail,"
                        . "l.lea_ip";
                    break;
                case 11:
                    //Rastreator
                    $querySource = "l.lea_aux1,"
                        . "l.lea_aux2,"
                        . "l.lea_aux3,"
                        . "l.lea_aux4";
                    break;
                case 2:
                    $querySource = "l.lea_surname,"
                        . "l.lea_aux2 dninie,"
                        . "UPPER(l.lea_aux3) asnef,"
                        . "l.lea_url,"
                        . "l.lea_ip";
                    $tam = count($datos);
                    $datos[$tam] = $data["leatype_id"];
                    break;       
                case 5:
                    $querySource = "l.lea_mail,"
                        . "l.lea_url";
                    break;
                default;
            }
            
            $query = "SELECT "
                . "l.lea_id,"
                . "s.sou_idcrm 'source',"
                . "lt.leatype_idcrm 'type',"
                . "l.lea_phone,"
                . "l.lea_name,";
            
            $query .= $querySource;

            $queryFromWhere = " FROM webservice.leads l "
                . "INNER JOIN webservice.sources s ON l.sou_id = s.sou_id "
                . "INNER JOIN webservice.leadtypes lt ON l.leatype_id = lt.leatype_id "
                . "WHERE "
                . "l.lea_destiny = ? "
                . "AND l.lea_extracted <=> ? "
                . "AND l.lea_status <=> ? "
                . "AND l.sou_id = ? ";
            
            if($data["sou_id"] == 2){
                $queryFromWhere .= " AND l.leatype_id = ? ";
            }
            
            if($data["sou_id"] == 5){
                $datos[4] = "139.47.1.166', '194.39.218.10', '92.56.96.208','94.143.76.28";
                $datos[5] = "";

                $queryFromWhere .= " AND l.lea_ip NOT IN (?)";

                $queryFromWhere .= " AND l.lea_phone <> ? ";
            }
            
            $query .= $queryFromWhere; 
            $query .= " ORDER BY l.lea_id DESC LIMIT 1;";
            
        
            $r = $db->selectPrepared($query, $datos);
            
            
            if(!is_null($r)){
                
                $id_origen_leontel = $r[0]->source;
		$id_tipo_leontel = $r[0]->type;
		$lea_id = $r[0]->lea_id;
                $phone = $r[0]->lea_phone;
                $nombre = $r[0]->lea_name;

                switch($data["sou_id"]){
                    case 1:
                        //Abandonos
                        $apellidos = $r[0]->lea_surname;
        		//$dninie = $r[0]->lea_aux1;
                        $url = $r[0]->lea_url;
                        $ip = $r[0]->lea_ip;
                        $asnef = $r[0]->asnef;
                        
                        $lead = [
                            'TELEFONO' => $phone,
                            'nombre' => $nombre,
                            'apellido1' => $apellidos,
                            //'dninie' => $dninie,
                            'asnef' => $asnef,
                            'url' => $url,
                            'ip' => $ip,
                            'wsid' => $lea_id
                        ];
                        break;
                    case 9:
                        //EndToEnd
                        $dninie = $r[0]->lea_aux1;
                        $url = $r[0]->lea_url;
                        $cantidadSolicitada = $r[0]->lea_aux2;

                        $lead = [
                                'TELEFONO' => $phone,
                                'url' => $url,
                                'wsid' => $lea_id,
                                'dninie' => $dninie,
                                'observaciones' => $cantidadSolicitada
                                //'cantidaddeseada' => $cantidadSolicitada
                        ];
                        break;
                    case 10:
                        //FB
                        $email = $r[0]->lea_mail;
                        $ip = $r[0]->lea_ip;

                        $lead = [
                                'TELEFONO' => $phone,
                                'nombre' => $nombre,
                                'Email' => $email,
                                'ip' => $ip,
                                'wsid' => $lea_id
                        ];
                        break;
                    case 11:
                        //Rastreator
                        $observaciones = "DNI: ".$r[0]->lea_aux4." Ingresos netos: ".$r[0]->lea_aux3." Tipo contrato: ".$r[0]->lea_aux2." Cantidad solicitada: ".$r[0]->lea_aux1;
                        $name = $r[0]->lea_name;

                        $lead = [
                                'TELEFONO' => $phone,
                                'observaciones' => $observaciones,
                                'nombre' => $name,
                                'wsid' => $lea_id
                        ];
                        break;
                    case 2:
                        $apellidos = $r[0]->lea_surname;
                        $dninie = $r[0]->dninie;
                        $asnef = $r[0]->asnef;
                        $url = $r[0]->lea_url;
                        $ip = $r[0]->lea_ip;

                        $lead = [
                                'TELEFONO' => $phone,
                                'nombre' => $nombre,
                                'apellido1' => $apellidos,
                                'dninie' => $dninie,
                                'asnef' => $asnef,
                                'url' => $url,
                                'ip' => $ip,
                                'wsid' => $lea_id
                        ];
                        break;            
                    case 5:
                        $url = $r[0]->lea_url;
                        $email = $r[0]->lea_mail;
                        
                        $lead = [
                                'TELEFONO' => $phone,
                                'nombre' => $nombre,
                                'Email' => $email,
                                'url' => $url,
                                'wsid' => $lea_id
                        ];
                        break;
                    default;
                }
            
                $ws = self::invokeWSLeontel();
//                comentado para desarrollo
//                $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
                $retorno["success"] = true;                
                $retorno["id"] = 9999;
                
                if($retorno["success"]){
                    $datos = [
                            "lea_extracted" => date("Y-m-d H:i:s"),
                            "lea_crmid" => $retorno["id"],
                            "lea_status" => "PRUEBA"
                        ];
                }else{
                    $datos = [
                            "lea_crmid" => "ERROR",
                            "lea_status" => "ERROR"
                        ];
                }
                
                $where = ["lea_id" => $lea_id];
                $parametros = UtilitiesConnection::getParametros($datos, $where);
                
                $result = $db->updatePrepared("webservice.leads", $parametros);               
                $r = json_decode($result);
  
                return json_encode(['success'=> $r->success, 'message'=> $r->message]);      


            }
            return json_encode(['success'=> false, 'message'=> 'No results']);
        }
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
}
