<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Functions;

/**
 * Description of Utilities
 *
 * @author Jose
 */
class Utilities {
    
 /*
    Array insert
    @array      the array to add an element to
    @element    the element to add to the array
    @position   the position in the array to add the element
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
    

}