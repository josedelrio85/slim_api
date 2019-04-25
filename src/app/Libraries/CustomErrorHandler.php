<?php
namespace App\Libraries;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class CustomErrorHandler extends \Slim\Handlers\Error {
    
  protected $logger;
  
  public function __construct(Logger $logger){
    $this->logger = $logger;
  }
  
  public function __invoke(Request $request, Response $response, \Exception $exception){   
    // create a JSON error string for the Response body
    $body = json_encode([
      'error' => $exception->getMessage(),
      'code' => $exception->getCode(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
    // Log the message
    try{
      $this->logger->WARNING($body);
    }catch(\Exception $e){
    }

    // Send an alarm
    self::exception_handlerAlarm($exception);
    
    return $response
      ->withStatus(500)
      ->withHeader('Content-type', 'application/json')
      ->withBody(new \Slim\Http\Body(fopen('php://temp','r+')))
      ->write($body);
  }

  public static function exception_handlerAlarm($exception){
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

    $result = self::curl($url, json_encode($params));
    return $result;
  }


  private static function curl($url, $params){
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
    $content  = curl_exec($ch);
    curl_close($ch);
    return $content;
  }
}
