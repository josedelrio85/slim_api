# Bysidecar Webservice API

The goal of this API is to handle the requests received from different kind of environments and for the different campaigns implemented. 

Uses Slim 3 Framework and PHP 7.1 + MySQL.

To install, run


```
php composer

```



## Short Description Usecase

```
    $this->post('/logic_explanation', function(Request $request, Response $response, array $args){

        // logs a message using a Monolog instance
        $this->utilities->infoLog("WS para validacion datos LP Creditea.");

        // if request is POST
        if($request->isPost()){

            // getServerParams returns values for url, ip and device used in request. Check functions/functions.php for documentation.
            list($url, $ip) = $this->funciones->getServerParams($request);

            // To populate $datos array propperly, you must know what are the keys of the data received by POST, and set it to manage Leontel requirements
            $observations = $lead->idStatusDate."---".$lead->application;

            // Some logic applied to input data
            if($this->funciones->phoneFormatValidator($lead->phoneId)){
                $phone = $lead->phoneId;
            }else{
                $phone = substr($lead->phoneId,3);
            }            


            // This sou_id value is for testing purposes. Check dependencies.php and settings_dev.php
            $sou_id = $this->sou_id_test;

            // In production environment use the correct sou_id, for example for this queue check in webservices.sources and use sou_id 53 (62 in crmti.sou_sources)
            $sou_id = 53;

            // lea_type identifies the type of interaction (C2C, ABANDONO, etc). Check webservice.sources for the different types.
            $lea_type = 1;

            // lea_destiny, sou_id, leatype_id are mandatory fields.    
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

            // Create an DB instance using settings as parameters.
            $setwebservice = $this->settings_db_webservice;
            $db = new \App\Libraries\Connection($setwebservice);

            // prepareAndSendLeadLeontel works with data passed by param and implements the logic to send the lead to Leontel.
            // Check functions/functions.php for documentation.
            $salida = $this->funciones->prepareAndSendLeadLeontel($datos,$db);

            
            // NOTE: the preapareAndSendLeadLeontel inserts the lead in crmti.lea_leads and webservice.leads table too, so if you use
            // this method you don't need to use the following code.


            // With this code you can get an instance of webservice DB, generate params for using as prepared statements with MySQL
            // and insert them into the BD. For insertPrepared function you must set the name of the table and pass the parameters
            // formatted using getparametros function.

            $db = $this->db_webservice;
            $parametros = UtilitiesConnection::getParametros($datos,null);
            $salida = json_decode($db->insertPrepared("leads", $parametros),true);

            // returns a JSON formatted response
            return $response->withJson($salida);
        }
    });

```
