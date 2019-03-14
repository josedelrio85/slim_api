<?php

namespace App\Middleware;
/**
  * Example middleware invokable class
  *
  * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
  * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
  * @param  callable                                 $next     Next middleware
  *
  * @return \Psr\Http\Message\ResponseInterface
  */

class ParsedBodyMiddleware {

    /*
     * withParsedBody() => It completely overwrites the request body with the modified object. 
     * What you have to pay mind to:
     * From there on, the request will hold a parsed object as body and on calling $request->getParsedBody() it won't be reparsed, 
     * if I understand the source correctly on calling $request->getParsedBody() you usually get an associative array if the body was JSON, 
     * but using the snippet above, the parsed body will be an object instead.
    */
    
    public function __invoke($request, $response, $next){
        # inside middleware:
        $requestbody = $request->getBody();
        $requestobject = json_decode($requestbody);
        # validation and modification of $requestobject takes place here
        $request = $request->withParsedBody($requestobject);

        return $next($request, $response);
    }
}
