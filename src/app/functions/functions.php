<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Functions;


class Functions {
      
    public function consultaTimeTableC2C($data, $db){
        
        $laborable = 1;

        if(array_key_exists('hora', $data)){
            
            $datos = [
                0 => $laborable, 
                1 => $data['sou_id'], 
                2 => $data['num_dia'], 
                3 => $data['hora'], 
                4 => $data['hora']];
            
            $sql = "SELECT * FROM webservice.c2c_timetable WHERE  laborable = ? and sou_id  = ? and num_dia = ? and h_ini <= ? and h_fin >= ? ;";
        }else{                       

            $datos = [
                0 => $laborable, 
                1 => $data['sou_id'], 
                2 => $laborable, 
                3 => $data['sou_id'], 
                4 => $laborable, 
                5 => $data['sou_id']];

            $sql = "SELECT * FROM webservice.c2c_timetable WHERE laborable = ? and sou_id  = ? and
            (num_dia = (select min(num_dia) from webservice.c2c_timetable where laborable = ? and sou_id= ? )
            or num_dia = (select max(num_dia) from webservice.c2c_timetable where laborable = ? and sou_id= ? ));";
        }
        
        $format = \App\Functions\Utilities::get_format_prepared_sql($datos);
        
        $r = $db->select($sql, $datos, $format);
        
        if(!is_null($r)){
            $aux = 0;
            $i = 0;
            $indice = "primerDia";
            
            foreach($r as $key => $obj){
                if($i == 0){
                    $elements[$indice][$i] = $obj;
                }else{
                    if($aux == $obj->num_dia){
                        $elements[$indice][$i] = $obj;
                    }else{
                        $indice = "ultimoDia";
                        $i = 0;
                        $elements[$indice][$i] = $obj;
                    }
                }
                $aux = $obj->num_dia;
                $i++;
            }
            return $elements;
        }
        return null;
    }
}