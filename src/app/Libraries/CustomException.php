<?php
namespace App\Libraries;

use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Handler\StreamHandler;

class CustomException extends \Exception {
    /*
     * @Exception message
    */
    protected $message = 'Unknown Exception';
    
    private $string;
    
    /*
     * @User-defined Exception code
    */    
    protected $code = 0;
    
    /*
     * @Source filename
    */    
    protected $file;
    
    /*
     * @Source line
    */    
    protected $line;
    
    /*
     * @An array of the backtrace()
    */    
    protected $trace;
    
    public function __construct($message, $code = 0, Exception $previous = null){
      if(!is_null($message)){
        $this->message = $message;
      }
      parent::__construct($this->message, $code, $previous);
    }
    
    public function __toString(){  
      $a = [
        "message" => $this->message,
        "file" => $this->file,
        "line" => $this->line,
        "trace" => $this->getTraceAsString()
      ];     
      return json_encode($a);
    }
}
