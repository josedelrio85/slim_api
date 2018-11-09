<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Libraries;

use App\Libraries\MiExcepcion;

class ProbarExcepcion
{
    public $var;

    const THROW_NONE    = 0;
    const THROW_CUSTOM  = 1;
    const THROW_DEFAULT = 2;

    function __construct($avalue = self::THROW_NONE) {

        switch ($avalue) {
            case self::THROW_CUSTOM:
                // lanzar la excepción personalizada
                throw new MiExcepcion('1 no es un parámetro válido', 5);
                break;

            case self::THROW_DEFAULT:
                // lanzar la predeterminada.
                throw new \Exception('2 no está permitido como parámetro', 6);
                break;

            default: 
                // No hay excepción, el objeto se creará.
                $this->var = $avalue;
                break;
        }
    }
}