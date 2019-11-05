<?php

namespace App\Old;

use App\Libraries\UtilitiesConnection;

class Oldfunctions {

  private $dev = null;
  private $container = null;

  public function __construct($dev, $container){
    $this->dev = $dev;
    $this->container = $container;
  }
  /**
    * Devuelve el sou_id apropiado en función del contenido recibido para
    * los parámetros gclid, domain y utm_source
  */
  public function getSouidMicrosoft($utm_source, $tipo, $gclid){
    if(!empty($gclid)){
      //Google
      switch($tipo){
        // Recomendador => 1
        case 1:
          $sou_id = 49;
        break;
        // Ofertas => 2
        case 2:
          $sou_id = 50;
        break;
        //FichaProducto => 3
        case 3:
          $sou_id = 51;
        break;
        case 4:
          //Microsoft Mundo R
          $sou_id = 25;
        case 5:
          //Microsoft Hazelcambio
          $sou_id = 46;
        break;
        case 6:
          //Calculadora
          $sou_id = 48;
        break;
      }
    }else{
      switch($tipo){
        // Recomendador
        case 1:
          $sou_id = 49;
        break;
        // Ofertas
        case 2:
          $sou_id = 50;
        break;
        // FichaProducto
        case 3:
          $sou_id = 51;
        break;
        //Microsoft Mundo R
        case 4:
          $sou_id = 25;
        break;
        //Microsoft Hazelcambio
        case 5:
          $sou_id = 46;
        break;
        //Calculadora
        case 6:
          $sou_id = 48;
        break;
      }
    }      
    return $sou_id;
  }
}