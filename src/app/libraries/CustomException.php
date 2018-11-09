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
//implements IException
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
    
    public function __toString()
    {
          $a = [
              "message" => $this->message,
              "file" => $this->file,
              "line" => $this->line,
              "trace" => $this->getTraceAsString()
          ];
          return $a;
    }
}
