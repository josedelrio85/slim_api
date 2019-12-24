<?php

namespace App\Functions;

/**
 * Description of Utilities
 *
 * @author Jose
 */
class Utilities {

  private $logger;

  function __construct($logger){
    $this->logger = $logger;
  }
  
  /*
   *  Array insert
   *  @array      the array to add an element to
   *  @element    the element to add to the array
   *  @position   the position in the array to add the element
  */
  public static function array_insert($array, $element, $position) {
      // if the array is empty just add the element to it
      if(empty($array)) {
          $array[] = $element;
      // if the position is a negative number
      } elseif(is_numeric($position) && $position < 0) {
          // if negative position after count
          if(count($array) + $position < 0) {
              $position = 0;
          } else {
              $position = count($array) + $position;
          }
          // try again with a positive position
          $array = array_insert($array, $element, $position);
      // if array position already set
      } elseif(isset($array[$position])) {
          // split array into two parts
          $split1 = array_slice($array, 0, $position, true);
          $split2 = array_slice($array, $position, null, true);
          // add new array element at between two parts
          $array = array_merge($split1, array($position => $element), $split2);
      // if position not set add to end of array
      } elseif(is_null($position)) {
          $array[] = $element;
      // if the position is not set
      } elseif(!isset($array[$position])) {
          $array[$position] = $element;
      }
      // clean up indexes
      $array = array_values($array);
      return $array;
  }
  
  public function infoLog($message){
    try{
      $this->logger->info($message);
    }catch(\Exception $e){
      return false;
    }
    return true;
  }

  /**
   * arrayToClass creates an array of objects typed as class param indicates
   * @param array $parsedBody data from POST method
   * @param string $class fully qualified name of the class. Ex: \\Foo\\Bar\\MyClass
   * @return array of objects of the indicated class
   */
  public function arrayToClass($parsedBody, $class) {
    if(is_array($parsedBody)){
      $instance = new $class();
      foreach ($parsedBody as $i => $ld) {
        if(is_object($ld) || is_array($ld)){
          foreach ($ld as $key => $value) {
            if(property_exists($class, $key)){
              $instance->{$key} = $value;
            }
          }
          $output[] = $instance;
        } else {
          if(property_exists($class, $i)){
            $instance->{$i} = $ld;
          }
          $output[0] = $instance;
        }
      }
      return $output[0];

    } else if (is_object($parsedBody)){
      $instance = new $class();
      foreach ($parsedBody as $i => $ld) {
        if(is_object($ld) || is_array($ld)){
          foreach ($ld as $key => $value) {
            if(property_exists($class, $key)){
              $instance->{$key} = $value;
            }
          }
        } else {
          if(property_exists($class, $i)){
            $instance->{$i} = $ld;
          }
        }
      }
      $output[0] = $instance;
      return $output[0];
    }
    return null;
  }
}