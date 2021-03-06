<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Libraries;

/**
 * Description of UtilitiesConnection
 *
 * @author Jose
 */
class UtilitiesConnection {
  
  /*
   * Genera un array con los formatos necesarios en función 
   * del tipo de variable de cada indice del array. 
    * Ahora tiene soporte para un array anidado.
   * @array      el array de parametros en función del cual habrá 
              que generar los parámetros correspondientes
  */    
  public static function getFormatPreparedSql($array){
    $salida = array();
    foreach($array as $key => $value){
      if(!is_array($value)){
        if(is_int($value) || is_double($value)){
          array_push($salida, "%d");
        }else if(is_string($value)){
          array_push($salida, "%s");
        }else if(is_null($value)){
          array_push($salida, "%s");
        }      
      }else{
        foreach($value as $k => $v){
          if(is_int($v) || is_double($v)){
            array_push($salida, "%d");
          }else if(is_string($v)){
            array_push($salida, "%s");
          }else if(is_null($v)){
            array_push($salida, "%s");
          }   
        }
      }            
    }
    return $salida;
  }
  
  /*
    * Devuelve un array con 4 indices que forman los elementos que se necesitarán para montar
    * una sentencia update con prepared_statement de mysqli
    */
  public static function getParametros($datos, $where = null){
    $formato = UtilitiesConnection::getFormatPreparedSql($datos);
    $formatoWhere = array();
    if($where != null)
      $formatoWhere = UtilitiesConnection::getFormatPreparedSql($where);
    
    return [
      "datos" => (array) $datos,
      "formatoDatos" => (array) $formato,
      "where" => (array) $where,
      "formatoWhere" => $formatoWhere
    ];
  }
  
  /*
    * Devuelve un string con los marcadores de formato que se usarán en la consulta 
    */    
  public static function getStringFormato($format){
    if(empty($format)){
      return false;
    }
    //Une elementos de un array en un string
    $cadenaFormatos = implode('', $format); 
    return str_replace('%', '', $cadenaFormatos);
  }
  
  /*
    * Pasando los valores por referencia (&)
    * Concatena en un string los valores contenidos en el array pasado por parametro, uniendolos con comas.
  */
  public static function ref_values($array) {
    $refs = array();
    foreach ($array as $key => $value) {
      $refs[$key] = &$array[$key]; 
    }
    return $refs; 
  }
  
  /*
    * @params:
    *      - array con los parametros que forman el where
    */
  public static function getParametrosWhere($parametros){
    $where_clause = '';
    $count = 0;
    
    foreach ( $parametros as $field => $value ) {
      if ( $count > 0 ) {
        $where_clause .= ' AND ';
      }

      $where_clause .= $field . '=?';
      $where_values[] = $value;
      $count++;
    }
    return array($where_clause, $where_values);
  }
  
  /*
    * Prepara todos los elementos utilizados en una acción CRUD
    * @params => 
    *      "datos" => (array) $datos,
    *      "formatoDatos" => (array) $formato,
    *      "where" => (array) $where, (update)
    *      "formatoWhere" => (array) $formatoWhere (update)
    *  fields => lista de campos concatenados con ,
    *  placeholders => campos con asignacion =? => lea_extracted=?,lea_crmid=?,lea_status=?
    *  values => array con valores a actualizar
    */
  public static function preparaElementosCRUD($params, $type='insert') {
    // Instantiate $fields and $placeholders for looping
    $fields = '';
    $placeholders = '';
    $values = array();
    $data = $params["datos"];
    
    $i = 0;
    // Loop through $data and build $fields, $placeholders, and $values			
    foreach ( $data as $field => $value ) {
      if($value != null){
        $fields .= "{$field},";
        $values[] = $value;

        if ( $type == 'update') {
          $placeholders .= $field . '=?,';
        } else {
          $placeholders .= '?,';
        }
      }else{
        //en caso de que el valor sea null hay que quitar el elemento correspondiente de $params["formatoDatos"]
        unset($params["formatoDatos"][$i]);
      }
      $i++;
    }

    // Normalize $fields and $placeholders for inserting
    $fields = substr($fields, 0, -1);
    $placeholders = substr($placeholders, 0, -1);

    
    //Construccion del formato
    $a = UtilitiesConnection::getStringFormato($params["formatoDatos"]);
    $b = "";
    if ( $type == 'update') {
      $b = UtilitiesConnection::getStringFormato($params["formatoWhere"]);
    }
    $format = $a.$b;
    
    // Prepend $format onto $values
    array_unshift($values, $format);
    
    return array( $fields, $placeholders, $values );
  }
  
  /*
    * Utilizada para obtener el sql de inserción en formato string
  */
  public static function bindParams($arr){
    if(is_array($arr)){
      $salida = "";
      $tam = count($arr) - 1;
      foreach ($arr as $key => $value){
        if(is_string($value)){
          $salida = $salida. "'".$value."'";
        }else{
          $salida = $salida.$value;
        }
        if($key < $tam){
          $salida .= ",";
        }
      }
      return $salida;
    }
    return null;
  }
  
  /*
    * Convierte un array con valores en un string con cada valor separado por comas.
    * Por ahora está recibiendo un array de objetos stdClass, que hay que desmontar en un array asociativo y obtener el valor a través del parametro
    * Usado para utilizar como prepared statement
    * $array => array clave => valor  [0 => "X"]
    * $salida => string separado por comas => "X,Y,Z"
    */
  private static function arrayToPreparedParam_notused($array){
      if(is_array($array)){
          $salida = "";
          $tam = count($array) - 1;
          foreach($array as $k => $v){
              if(is_object($v)){
                  $v = json_decode(json_encode($v),true);
              }
              $z = key($v);
              $a = $v[$z];
              //$salida .= $v[$k];
              $salida .= $a;
              if($k < $tam){
                  $salida .= ",";
              }
          }
          $b = "";
          return $salida;
      }
      return "";
  }
  
  /*
    * Transforma array en formato adecuado para utilizar como parametro
    * en prepared statement.
    * @$array => array de valores
    * @$prop => para obtener la propiedad deseada de cada subarray
    * 
    * @return => array con conjunto de ? para preparar la consulta
    *         => valores a sustituir por ?
    */
  public static function arrayToPreparedParam($array, $prop = null){
    if(!empty($array)){
      foreach($array as $k => $v){
        $valores[$k] = ($prop === null) ? $v : $v[$prop];
      }

      $questions = implode(",", array_fill(0, count($valores), "?"));

      if(empty($questions))
          $questions = "?";

      $salida['questions'] = $questions;
      $salida['values'] = $valores;  
    }else{
      $salida['questions'] = "?";
      $salida['values'] = "null";
    }
    return $salida;
  }
  
  
  public static function generaQuestions($array){
    if(!empty($array)){
      $questions = implode(",", array_fill(0, count($array), "?"));
      return $questions;
    }
    return null;
  }
}
