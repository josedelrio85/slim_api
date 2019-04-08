<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Libraries;

/**
 *
 * @author Jose
 */
interface IException {
    /*Protected methods inherited from Exception class */
    
    /*
     * @Exception message
     */
    public function getMessage();
    
    /*
     * @User-defined Exception code
     */
    public function getCode();
    
    /*
     * @Source filename
     */
    public function getFile();
    
    /*
     * @Source line
     */
    public function getLine();
    
    /*
     * @An array of the backtrace()
     */
    public function getTrace();
    
    /*
     * @Formate string of trace
     */
    public function getTraceAsString();
    
    public function __toString();
    
    public function __construct($message = null, $code = 0);
    
}
