<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
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
class ExampleMiddleware {

    public function __invoke($request, $response, $next){
//        $response->getBody()->write('BEFORE');
       
        $response = $next($request, $response);
//        $response->getBody()->write('AFTER');
        
        return $response->withHeader('Access-Control-Allow-Origin', 'http://paquito')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    }
}
