<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Libraries;

/**
 * Description of CustomException
 *
 * @author Jose
 */
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
        if(!$message){
            throw new $this('Unknwon '. get_class($this));
        }
        parent::__construct($message, $code, $previous);
    }
    
    public function __toString(){
    
        $logger = new Logger('CustomExceptionLogger');
        $logger->pushProcessor(new UidProcessor());
        $logger->pushHandler(new StreamHandler(__DIR__.'/logs/app.log', Logger::INFO));
        
        $a = [
            "message" => $this->message,
            "file" => $this->file,
            "line" => $this->line,
            "trace" => $this->getTraceAsString()
        ];
        
        $logger->info('Custom_exception => ',$a);
        
        return json_encode($a);
    }
}
