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
interface IConnection {
    
    
    /*
     * Ejecuta la conulta pasada por parametrro
     * @param string $query La consulta SQL a realizar
     * @return \mysqli_result|true|false Devuelve un objeto mysqli_result con consultas que devuelvan un resultado, true para las consultas
     * correctas que no devuelven un resultado y false para las consultas fallidas
     */
    public function Query($query);
    
    /*
     * Ejecuta la multiconsula pasada por parametrro
     * @param string $query La consulta SQL a realizar
     * @return \mysqli_result|true|false Devuelve un objeto mysqli_result con consultas que devuelvan un resultado, true para las consultas
     * correctas que no devuelven un resultado y false para las consultas fallidas
     */
    public function MultiQuery($query);
            
    /**
     * Devuelve el last_insert_id, el último id autonumérico asignado en la última consulta tipo INSERT, REPLACE, etc.
     *
     * @return int El último ID creado
     */
    public function LastID();
    /**
     *  Devuelve el numero de filas afectadas en la ultima consulta
     *
     */
    public function AffectedRows();
    
    
    /**
     *  Devuelve el ultimo error
     *
     */
    public function LastError();
    
    
    /**
     *  Escapa los caracteres especiales de una cadena para usarla en una sentencia SQL
     *
     */
    public function RealScapeString($escapestr);
    
    
    /**
     *  Transfiere un conjunto de resulados de la última consulta
     *
     */
    public function StoreResult();

    /**
     *  Prepara el siguiente juego de resultados de una llamada
     *
     */
    public function NextResult();

    
    public function Prepare($sql);
    /**
     *
     * Funcion destructora
     *
     */
    function __destruct();

    public function __call($method, $attributtes);
}
