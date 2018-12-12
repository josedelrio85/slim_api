<?php
namespace App\Libraries;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

/**
 * Description of CustomError
 * Class to handle errors. Extends default Error Slim class.
 * Log errors in a text file and response with JSON error.
 *
 * @author Jose
 */

final class CustomError extends \Slim\Handlers\Error {
    
    protected $logger;
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    
    public function __invoke(Request $request, Response $response, \Exception $exception){
        //Log the message
        $this->logger->critical($exception->getMessage());
        
//        return parent::__invoke($request, $response, $exception);        
        
        //create a JSON error string for the Response body
        $body = json_encode([
            'error' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return $response
                ->withStatus(500)
                ->withHeader('Content-type', 'application/json')
                ->withBody(new \Slim\Http\Body(fopen('php://temp','r+')))
                ->write($body);
    }
}
