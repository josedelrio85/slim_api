<?php
namespace App\Libraries;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class CustomPHPErrorHandler extends \Slim\Handlers\PhpError {
    
  protected $logger;
  
  public function __construct(Logger $logger){
    $this->logger = $logger;
  }
  
  public function __invoke(Request $request, Response $response, \Throwable $error){   
    // create a JSON error string for the Response body
    $body = json_encode([
      'error' => $error->getMessage(),
      'code' => $error->getCode(),
      'file' => $error->getFile(),
      'line' => $error->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
    // Log the message
    try{
      $this->logger->critical($body);
    }catch(\Exception $e){
    }

    // send an alarm
    CustomErrorHandler::exception_handlerAlarm($error);
    
    return $response
      ->withStatus(500)
      ->withHeader('Content-type', 'application/json')
      ->withBody(new \Slim\Http\Body(fopen('php://temp','r+')))
      ->write($body);
  }
}
