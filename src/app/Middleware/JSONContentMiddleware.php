<?php

namespace App\Middleware;

class ContentType {
    
    const _JSON = "application/json";
    const _FORM_URLENCODED = "application/x-www-form-urlencoded";
    
    const _JSON_UTF8 = "application/json; charset=UTF-8";
    const _FORM_URLENCODED_UTF8 = "application/x-www-form-urlencoded; charset=UTF-8";
    
    
    static function getContentTypes(){
        return $array = [
            self::_JSON,
            self::_FORM_URLENCODED,
            self::_JSON_UTF8,
            self::_FORM_URLENCODED_UTF8,
        ];       
    }
}

/**
 * Middleware para asegurar que los content-type recibidos son de tipo JSON (form-urlencoded para navegadores)
 *
 * @author Jose del Río
 */
class JSONContentMiddleware {
    
    public function __invoke($request, $response, $next) {
        /*
        if (!$request->isXhr()){
            // X-Requested-With realmente solo da información sobre si el request ha sido realizado desde Ajax, como value debe llegar XMLHttpRequest. 
            // Valorar si interesa implementarlo. ¿Habrá requests desde clientes, curl, etc?
            // return $response->withStatus(412, "The precondition given in the request evaluated to false by the server ");
        }
        */
        
        /*
        if($request->getContentType() != "application/json")        
            return $response->withStatus(400, "Bad content-type");
        return $next($request, $response);
        */
        
        /*
        * The following middleware can be used to query Slim’s router and get a list of methods a particular pattern implements.
        * Here is a complete example application:
        */
        if(in_array($request->getContentType(), ContentType::getContentTypes())){
            
            // It will add the Access-Control-Allow-Methods header to every request
            $route = $request->getAttribute("route");
            $methods = [];

            if (!empty($route)) {
                $pattern = $route->getPattern();

                foreach ($this->router->getRoutes() as $route) {
                    if ($pattern === $route->getPattern()) {
                        $methods = array_merge_recursive($methods, $route->getMethods());
                    }
                }
                //Methods holds all of the HTTP Verbs that a particular route handles.
            } else {
                $methods[] = $request->getMethod();
            }           
            
            $response = $next($request, $response);

            return $response->withHeader("Access-Control-Allow-Methods", implode(",", $methods));
            
        }else{
            return $response->withStatus(400, $request->getContentType()." Bad content-type");
        }               
    }
}
