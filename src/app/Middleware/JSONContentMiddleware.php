<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Middleware;

/**
 * Middleware para asegurar que los content-type recibidos son de tipo JSON
 *
 * @author Jose
 */
class JSONContentMiddleware {
    
    public function __invoke($request, $response, $next) {
        if (!$request->isXhr()){
            //X-Requested-With realmente solo da información sobre si el request ha sido realizado desde Ajax, como value debe llegar XMLHttpRequest. 
            //Valorar si interesa implementarlo. ¿Habrá requests desde clientes, curl, etc?
            //return $response->withStatus(412, "The precondition given in the request evaluated to false by the server ");
        }
        
        if($request->getContentType() != "application/json")
            return $response->withStatus(400, "Bad content-type");
               
        return $next($request, $response);
    }
}
