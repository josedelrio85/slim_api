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
    //create a JSON error string for the Response body
    $body = json_encode([
      'error' => $error->getMessage(),
      'code' => $error->getCode(),
      'file' => $error->getFile(),
      'line' => $error->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
    //Log the message
    // $this->logger->critical($body);

    $this->exception_handlerAlarm($error);
    
    return $response
      ->withStatus(500)
      ->withHeader('Content-type', 'application/json')
      ->withBody(new \Slim\Http\Body(fopen('php://temp','r+')))
      ->write($body);
  }

  public function exception_handlerAlarm($exception){
    $url = "https://alert.victorops.com/integrations/generic/20131114/alert/2f616629-de63-4162-bb6f-11966bbb538d/test";

    switch(get_class($exception)){
      case "UnexpectedValueException":
        $state = "CRITICAL";
        break;
      default:
        $state = "ACKNOWLEDGEMENT";
        break;
    }

    $params = [
      "message_type" => $state,
      "entity_state" => $state,
      "entity_id" => "webservice_exception",
      "entity_display_name" => "webservice_exception",
      "state_message" => json_encode($exception->__toString()),
      "state_start_time" => time(),
    ];

    $result = $this->curl($url, json_encode($params));
    return $result;
  }

  private function curl($url, $params){
    $options = array(
      CURLOPT_RETURNTRANSFER => true,   // return web page
      CURLOPT_HEADER         => false,  // don't return headers
      CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
      CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
      CURLOPT_TIMEOUT        => 120,    // time-out on response
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $params,
    ); 

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    // $content = curl_exec($ch);
    $content = null;
    curl_close($ch);
    return $content;
  }
}
