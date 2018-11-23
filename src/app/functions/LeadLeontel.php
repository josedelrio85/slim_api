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
    private $ws = null;
    private $wsCred = null;
    
    function __construct($params){
        if(is_array($params) && array_key_exists("location", $params) && array_key_exists("uri", $params)){
            $this->ws = self::invokeWSLeontel($params);
        }else{
            $this->ws = self::invokeWSLeontel();
        }
    }
    
    
    public static function sendLead($data, $db){
        //data tiene que tener nombres de parametro como lea_leads
        
        if(array_key_exists('sou_id', $data)){
            
            $datos = [
                0 => "LEONTEL",
                1 => NULL,
                2 => NULL,
                3 => $data['sou_id'],
            ];
                       
            $query = "SELECT "
                . "l.lea_id,"
                . "s.sou_idcrm 'source',"
                . "lt.leatype_idcrm 'type',"
                . "l.lea_phone,"
                . "l.lea_name,";
            
            $querySource = self::queryLead($data["sou_id"]);

            $query .= $querySource;

            $queryFromWhere = " FROM webservice.leads l "
                . "INNER JOIN webservice.sources s ON l.sou_id = s.sou_id "
                . "INNER JOIN webservice.leadtypes lt ON l.leatype_id = lt.leatype_id "
                . "WHERE "
                . "l.lea_destiny = ? "
                . "AND l.lea_extracted <=> ? "
                . "AND l.lea_status <=> ? "
                . "AND l.sou_id = ? ";
            
            if($data["sou_id"] == 1 || $data["sou_id"] == 21 || $data["sou_id"] == 22){

                switch($data["sou_id"]){
                    case 1:
                        $datos[4] = 21;
                        $datos[5] = 22;
                        break;
                    case 21:
                        $datos[4] = 1;
                        $datos[5] = 22;
                        break;
                    case 22:
                        $datos[4] = 1;
                        $datos[5] = 21;
                        break;
                    default:
                        $datos[3] = 1;
                        $datos[4] = 21;
                        $datos[5] = 22;
                }
                $queryFromWhere .= " OR l.sou_id = ? OR l.sou_id = ?";
            }
            
            if($data["sou_id"] == 2){
                $queryFromWhere .= " AND l.leatype_id = ? ";
                if($data["leatype_id"] == 2){
                    $datos[4] = $data["leatype_id"];
                }else{
                    $datos[4] = 2;
                }
                
            }
            
            if($data["sou_id"] == 5){
                $queryFromWhere .= " AND l.lea_ip NOT IN (?)";

                $queryFromWhere .= " AND l.lea_phone <> ? ";
                
                $datos[4] = "139.47.1.166', '194.39.218.10', '92.56.96.208','94.143.76.28'";
                $datos[5] = "''";
            }
            
            if($data["sou_id"] == 14){
                $datos[4] = "'139.47.1.166', '194.39.218.10', '92.56.96.208'";
                $queryFromWhere .= " AND l.lea_ip NOT IN (?)";
            }
            
            if($data["sou_id"] == 3){
                $queryFromWhere .= " AND l.leatype_id <> ? ";
                $queryFromWhere .= " AND l.lea_phone <> ? ";
                $datos[4] = 3;
                $datos[5] = "''";
            }
            
            $query .= $queryFromWhere; 
            $query .= " ORDER BY l.lea_id DESC LIMIT 1;";
            
        
            $r = $db->selectPrepared($query, $datos);
//            $consulta = array();
//            $consulta['query'] = $query;            
//            $consulta['datos'] = $datos;

            if(!is_null($r)){
                
                $id_origen_leontel = $r[0]->source;
		$id_tipo_leontel = $r[0]->type;
		$lea_id = $r[0]->lea_id;

                $lead = self::paramsLead($r, $data["sou_id"]);
                
                $ws = self::invokeWSLeontel();
//                $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
                $retorno["success"] = true;                
                $retorno["id"] = 9999;
                
                if($retorno["success"]){
                    $datos = [
                            "lea_extracted" => date("Y-m-d H:i:s"),
                            "lea_crmid" => $retorno["id"],
                            "lea_status" => "PRUEBA"
                            //"lea_status" => "SENT"
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
                $res = json_decode($result);
  
                return json_encode(['success'=> $res->success, 'message'=> $res->message]);      
//                return json_encode(['success'=> $res->success, 'message'=> $consulta]);      
            }
            return json_encode(['success'=> false, 'message'=> 'No results']);
        }
    }
    
    public static function sendLeadEvo($data, $db){
               
        if(array_key_exists('sou_id', $data)){
           
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
                . "URL_SALESFORCE,"
                . "LOGALTY_ESTADO__C,"
                . "STEPID";

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
                
                $stepid = $r[0]->STEPID;   
                $logalty_estado_c = $r[0]->LOGALTY_ESTADO__C;
                
                $array_tipo_leontel = self::getIdTipoLeontel($logalty_estado_c, $client_estado_c, $stepid);
                $id_tipo_leontel = $array_tipo_leontel["idTipoLeontel"];
                
                $lead = [
                    'TELEFONO' => $phone,
//                    'observaciones' => $stepid,
                    'observaciones' => $client_estado_c,
                    'url' => $url_salesforce,
                    'wsid' => $lea_id
                ];
                
                $ws  = self::invokeWSLeontelWithCredentials();                
                $data = $ws->getLeadLastStatus($id_origen_leontel,$id_tipo_leontel,$phone);

                if($data["success"]){
                    $datosDup = ["even_status" => "DUPLICATED"];
                    $whereDup = ["even_id" => $lea_id];
                    $parametrosDup = UtilitiesConnection::getParametros($datosDup, $whereDup);
                
                    $resultDup = $db->updatePrepared("webservice.evo_events_sf_v2_pro", $parametrosDup);
                    $res = json_decode($resultDup);
                    
                }else{
                    if(is_null(self::ws)){
                        $ws = self::invokeWSLeontel();
                    }
                    //$retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
                    $retorno["success"] = true;                
                    $retorno["id"] = 9999;
                    
                    if($retorno["success"]){
                        $datos = [
                            "even_extracted" => date("Y-m-d H:i:s"),
                            "even_crmid" => $retorno["id"],
                            "even_status" => "PRUEBA"
                            //"even_status" => "SENT"
                        ];
                    }else{
                        $datos = [
                            "even_crmid" => "ERROR",
                            "even_status" => "ERROR"
                        ];
                    }

                    $where = ["even_id" => $lea_id];
                    $parametros = UtilitiesConnection::getParametros($datos, $where);

                    $result = $db->updatePrepared("webservice.evo_events_sf_v2_pro", $parametros);               
                    $res = json_decode($result);
                    return json_encode(['success'=> $res->success, 'message'=> $res->message]);
                }
                return json_encode(['success'=> false, 'message'=> 'Last status false.']);      
            }
            return json_encode(['success'=> false, 'message'=> 'No results']);
        }
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
    public static function sendLeadLeontelPagoRecurrente($tipo, $db){
        
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
                AND date(ll.lea_ts) >= ?;";
            
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
//                $retorno = $ws->sendLead($id_origen_leontel, $id_tipo_leontel, $lead);
                $retorno["success"] = true;                
                $retorno["id"] = 9999;

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
        $credentials = array('login'=> "login",
                       'password'=> "pass",
                       'location'=>"http://192.168.50.102/webservice",
                       'uri'=>"LeonTel",
                       'soap_version'=>SOAP_1_2
       );
       $webservice = new SoapClient(null,$credentials);
       return $webservice;
    }
    
    /*
     * Devuelve parte de la query que se realiza en función del sou_id 
     * para la recuperación del último lead registrado en webservice.
     */
    private function queryLead($sou_id){
        /*  
            * webservice
            1	CREDITEA ABANDONADOS            2
            2   CREDITEA STAND                  3
            3	EVO BANCO                       4
            4	CREDITEA TIMEOUT                5
            5	R CABLE                         6
            6	BYSIDECAR                       7
            7	HERCULES                        8
            8	SEGURO PARA MOVIL               11
            9	CREDITEA END TO END             13
            10	CREDITEA FB                     14
            11	CREDITEA RASTREATOR             15
            12	EUSKALTEL                       16
            13	ADESLAS                         19
            14	R CABLE EMPRESAS                20
            15	PRUEBA BySidecar                23
            16	EVO BANCO FIRMADOS NO FORMALIZ	24
            17	YOIGO NEGOCIOS DERIVACION YOIG	25
            18	YOIGO NEGOCIOS SEO              26
            19	YOIGO NEGOCIOS SEM              27
            20	YOIGO NEGOCIOS EMAILING         28
         * 
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
            18	EUSKALTEL ATENCION AL CLIENTE	
            19	ADESLAS	
            20	R CABLE EMPRESAS	
            21	DINERO VELOZ RECEPCION	
            22	DINERO VELOZ RECEPCION NO CLI	
            23	PRUEBA BySidecar	
            24	EVO BANCO FIRMADOS NO FORMALIZADOS	
            25	YOIGO NEGOCIOS DERIVACION YOIGO	
            26	YOIGO NEGOCIOS SEO	
            27	YOIGO NEGOCIOS SEM	
            28	YOIGO NEGOCIOS EMAILING	
        */		
        if(!is_null($sou_id) && $sou_id!= ""){         
            
            switch($sou_id){
                case 1:
                case 21:
                case 22:
                    //Creditea Abandonos
                    $querySource = "l.lea_surname,"
                        . "l.lea_aux2 dninie,"
                        . "UPPER(l.lea_aux3) asnef,"
                        . "l.lea_url,"
                        . "l.lea_ip";
                    break;
                case 9:
                    //Creditea EndToEnd
                    $querySource = "l.lea_mail,"
                        . "l.lea_url,"
                        . "l.lea_aux1,"
                        . "l.lea_aux2 ";
                    break;
                case 10:
                    //Creditea FB
                    $querySource = "l.lea_mail,"
                        . "l.lea_ip";
                    break;
                case 11:
                    //Creditea Rastreator
                    $querySource = "l.lea_aux1,"
                        . "l.lea_aux2,"
                        . "l.lea_aux3,"
                        . "l.lea_aux4";
                    break;
                case 2:
                    //Creditea Stand
                    $querySource = "l.lea_surname,"
                        . "l.lea_aux2 dninie,"
                        . "UPPER(l.lea_aux3) asnef,"
                        . "l.lea_url,"
                        . "l.lea_ip";
                    $tam = count($datos);
                    $datos[$tam] = $data["leatype_id"];
                    break; 
                case 17:
                case 18:
                case 19:
                case 20:
                    // Yoigo
                    $querySource = "l.lea_aux2,"
                        . "l.lea_aux3,"
                        . "l.lea_url,"
                        . "l.lea_ip";
                    break;
                
                default:
                    /* case 5: case 12: case 7:case 14:case 8: case 6:*/
                    //R Cable + Euskaltel + Hercules + R Cable Empresas + SEGURO PARA MOVIL + Bysidecar + EvoBanco (sendC2CToLeontel)
                    $querySource = "l.lea_mail,"
                        . "l.lea_url";
            }
            return $querySource;
        }
        return "";
    }
    
    /*
     * Devuelve array de datos en función del sou_id que se adjuntarán al WS SOAP.
     */
    private function paramsLead($r, $sou_id){
        
        if(!is_null($r) && $sou_id != "" && !is_null($sou_id)){
                    
            $id_origen_leontel = $r[0]->source;
            $id_tipo_leontel = $r[0]->type;
            $lea_id = $r[0]->lea_id;
            $phone = $r[0]->lea_phone;
            $nombre = $r[0]->lea_name;
 
            switch($sou_id){
                case 1                :
                case 21:
                case 22:
                    //Abandonos
                    $apellidos = $r[0]->lea_surname;
                    $url = $r[0]->lea_url;
                    $ip = $r[0]->lea_ip;
                    $asnef = $r[0]->asnef;

                    $lead = [
                        'TELEFONO' => $phone,
                        'nombre' => $nombre,
                        'apellido1' => $apellidos,
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
                case 14:
                case 3:
                case 15:
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
                case 12:
                    $url = $r[0]->lea_url;

                    $lead = [
                        'TELEFONO' => $phone,
                        'url' => $url,
                        'wsid' => $lea_id
                    ];
                    break;
                case 7:
                case 8:
                case 6:
                    $lead = [
                        'TELEFONO' => $phone,
                        'wsid' => $lea_id
                    ];
                    break;
                case 17:
                case 18:
                case 19:
                case 20:
                    $observaciones = $r[0]->lea_aux2;
                    $url = $r[0]->lea_url;
                    $observaciones2 = $r[0]->lea_aux3;
                    
                    $lead = [
                        'TELEFONO' => $phone,
                        'url' => $url,
                        'wsid' => $lea_id,
                        'ip' => $ip,
                        'observaciones' => $observaciones,
                        'observaciones2' => $observaciones2
                    ];
                    break;
                default:
                    $lead = [
                        'TELEFONO' => $phone,
                        'nombre' => $nombre,
                        'wsid' => $lea_id
                    ];
            }
            return $lead;
        }
        return [];
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
//            case 'metodo-validacion':
//                switch($CLIENT_ESTADO__C) {
//                    case 'Potencial':
//                    case 'Pendiente revisión Captación':
//                        return ["destiny"=> "LEONTEL",
//                            // "filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelIncompletosV2.php",
//                            "idTipoLeontel" => 22
//			];
//                    break;
//                    default:
//                        return null;
//                }
//            break;
		
            case 'identificacion-video':
                switch($CLIENT_ESTADO__C) {
                    case 'Potencial':
                    case 'Pendiente revisión Captación':
			return ["destiny"=> "LEONTEL",
//                          "filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteElectronicaIDV2.php"
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
                        return ["destiny"=> "LEONTEL",
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
                                        return ["destiny" => "LEONTEL",
                                            //"filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteOTPV2.php"
                                            "idTipoLeontel" => 18];
                                        break;
                                    case 'Firma':
                                        return ["destiny" => "LEONTEL",
                                            //"filePath"=> "/var/www/html/Leontel/EvoBanco/FullOnline2.0/sendLeadToLeontelPendienteOTPV2.php"
                                            "idTipoLeontel" => 18];
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
}
