<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Libraries;

use App\Libraries\CustomException;

/**
 * Description of Connection
 *
 * @author Jose
 */
class Connection implements IConnection{
    
    /**
     * Objeto mysqli de la conexión.
     * @var mysqli
     */
    static public $mysqli = null;

    /**
     * Número de objetos creados de esta clase, con esta variable se controla el momento necesario para cerrar la conexión con MySQL
     * @var int
     */
    static protected $instances = 0;

    /**
     * Objeto interno de conexión con la base de datos
     * @var \mysqli
     */
    public $mysql = null; 
    
    function __construct($params) {
        
        if(!empty($params) && is_array($params)){
            $server = $params["host"];            
            $database = $params["dbname"];
            $username = $params["user"];
            $password = $params["password"];
 
            if(!is_null($server) && !is_null($username) && !is_null($database)){
                self::$mysqli = new \mysqli($server, $username, $password, $database);

                if (self::$mysqli->connect_errno) {
                    throw new \App\Libraries\CustomException("Fallo al conectar a la bbdd " . $database, self::$mysqli->connect_errno);
                }               
                self::$mysqli->set_charset("utf8");
                self::$instances++;
                
            }else{
                throw new \App\Libraries\CustomException("Fallo parametros conexion ", 0);
            }
        }else{
            throw new \App\Libraries\CustomException("Fallo parametros conexion ", 0);
        }
    }    
    
    /*
     * Ejecuta la conulta pasada por parametrro
     * @param string $query La consulta SQL a realizar
     * @return \mysqli_result|true|false Devuelve un objeto mysqli_result con consultas que devuelvan un resultado, true para las consultas
     * correctas que no devuelven un resultado y false para las consultas fallidas
     */
    public function Query($query) {
//        return \is_null($this->mysql) ? self::$mysqli->query($query) : $this->mysql->query($query);
        $res = \is_null($this->mysql) ? self::$mysqli->query($query) : $this->mysql->query($query);
        if(false===$res){
            $error = $this->LastError();
            $errno = self::$mysqli->connect_errno;
            throw new \App\Libraries\CustomException($error, $errno);
        }
        return $res;
    }
    
    /*
     * Ejecuta la multiconsula pasada por parametrro
     * @param string $query La consulta SQL a realizar
     * @return \mysqli_result|true|false Devuelve un objeto mysqli_result con consultas que devuelvan un resultado, true para las consultas
     * correctas que no devuelven un resultado y false para las consultas fallidas
     */
    public function MultiQuery($query) {
        return \is_null($this->mysql) ? self::$mysqli->multi_query($query) : $this->mysql->multi_query($query);
    }    
    
    /**
     * Devuelve el last_insert_id, el último id autonumérico asignado en la última consulta tipo INSERT, REPLACE, etc.
     *
     * @return int El último ID creado
     */
    public function LastID() {
        return \is_null($this->mysql) ? self::$mysqli->insert_id : $this->mysql->insert_id;
    }
    
    /**
     *  Devuelve el numero de filas afectadas en la ultima consulta
     *
     */
    public function AffectedRows() {
        return \is_null($this->mysql) ? self::$mysqli->affected_rows : $this->mysql->affected_rows;
    }   
    
    /**
     *  Devuelve el ultimo error
     *
     */
    public function LastError() {
        return \is_null($this->mysql) ? self::$mysqli->error : $this->mysql->error;
    }    
    
    /**
     *  Escapa los caracteres especiales de una cadena para usarla en una sentencia SQL
     *
     */
    public function RealScapeString($escapestr) {
        return \is_null($this->mysql) ? self::$mysqli->real_escape_string($escapestr) : $this->mysql->real_escape_string($escapestr);
    }
    
    /**
     *  Transfiere un conjunto de resulados de la última consulta
     *
     */
    public function StoreResult() {
        return \is_null($this->mysql) ? self::$mysqli->store_result() : $this->mysql->store_result();
    }

    /**
     *  Prepara el siguiente juego de resultados de una llamada
     *
     */
    public function NextResult() {
        return \is_null($this->mysql) ? self::$mysqli->next_result() : $this->mysql->next_result();
    }
    
    public function Prepare($sql){
        $res = \is_null($this->mysql) ? self::$mysqli->prepare($sql) : $this->mysql->prepare($sql);
        if(false===$res){
            $error = $this->LastError();
            $errno = self::$mysqli->connect_errno;
            throw new \App\Libraries\CustomException($error, $errno);
        }
        return $res;
    }
    
    /**
     *
     * Funcion destructora
     *
     */
    function __destruct() {
        if (\is_null($this->mysql)) {
            self::$instances--;

            if (self::$instances < 1) {
                self::$mysqli->close();
                self::$mysqli = null;
            }
        } else {
            $this->mysql->close();
        }
    }

    public function __call($method, $attributtes) {
        return "The method $method does not exists";
    }
    
    
    /************************************************************************/
    
    
    /*
     * Añadido soportes para array anidados (1 dimension)
     * @query: Parte selectiva de la consulta
     * @data: array con los valores del where
     */
    public function selectPrepared($query, $data, $assoc = false) {
        //Prepare our query for binding
        $stmt = $this->prepare($query);
        
        if(is_null($data)){
          $data = array();
        }

        $formato = UtilitiesConnection::getFormatPreparedSql($data);
        $format = UtilitiesConnection::getStringFormato($formato);
        
        /****************************************/
        $d = array();
        foreach($data as $k => $v){
            if(is_array($v)){
                foreach ($v as $c){
                    array_push($d, $c);
                }
            }else{
                array_push($d, $v);
            }
        }
        /****************************************/
        // Añade format al inicio de data (en el indice 0)
        array_unshift($d, $format);

        // data es un array, elemento 0 es el formato, y los siguientes son los datos
        if($d[0] != false)
          call_user_func_array(array($stmt, 'bind_param'), UtilitiesConnection::ref_values($d));       

        $stmt->execute();
        $result = $stmt->get_result();
        $results = null;
        if($assoc){
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }            
        }else{
            while ($row = $result->fetch_object()) {
                $results[] = $row;
            }            
        }
        return $results;
    }
    
    /*
     * @table: nombre de la tabla a actualizar
     * @parametros:
     *      "datos" => (array) $datos,
     *      "formatoDatos" => (array) $formato,
     *      "where" => (array) $where,
     *      "formatoWhere" => (array) $formatoWhere
     * 
     * - camposActualizar: Obtiene un string con la parte de la sentencia referente a qué campos se actualizan
     * - valores: array que contiene cadena con formato parámetros y valores de los mismos, para pasarsela a bind_param de mysqli
     */
    public function updatePrepared($table, $parametros) {
        
        if ( empty( $table ) || empty( $parametros["datos"]) || empty( $parametros["formatoDatos"] )) {
            return false;
        }
        
        // campos => lista de campos concatenados con ,
        // camposActualizar => campos con asignacion =? => lea_extracted=?,lea_crmid=?,lea_status=?
        // valores => array con valores a actualizar
        list( $campos, $camposActualizar, $valores ) = UtilitiesConnection::preparaElementosCRUD($parametros, 'update'); 
        list($where_clause, $where_values) = UtilitiesConnection::getParametrosWhere($parametros["where"]);

        //  array 0 => valores de formato, resto valores
        $valores = array_merge($valores, $where_values);       
      
        $stmt = $this->Prepare("UPDATE {$table} SET {$camposActualizar} WHERE {$where_clause}");

        // Dynamically bind values
        call_user_func_array( array( $stmt, 'bind_param'), UtilitiesConnection::ref_values($valores));
        

        $rc = $stmt->execute();

        if ( $stmt->affected_rows ) {
            return json_encode(['success'=> true, 'message'=> $stmt->affected_rows]);
        }
        return json_encode(['success'=> false, 'message'=> $stmt->error]);
    }

    /*
     * @table: nombre de la tabla a actualizar
     * @parametros:
     *      "datos" => (array) $datos,
     *      "formatoDatos" => (array) $formato,
     * 
     * - campos: string con los nombres de campos que van a ser utilizados en la inserción
     * - camposActualizar: Obtiene un string con la parte de la sentencia referente a qué campos se insertan
     * - valores: array que contiene cadena con formato parámetros y valores de los mismos, para pasarsela a bind_param de mysqli
     */
    public function insertPrepared($table, $parametros){

        if ( empty( $table ) || empty( $parametros["datos"]) || empty( $parametros["formatoDatos"] )) {
            return false;
        }
        
        // campos => lista de campos concatenados con ,
        // camposActualizar => campos con asignacion =? => lea_extracted=?,lea_crmid=?,lea_status=?
        // valores => array con valores a actualizar
        list( $campos, $camposActualizar, $valores ) = UtilitiesConnection::preparaElementosCRUD($parametros, 'insert'); 


        $stmt = $this->prepare("INSERT INTO {$table} ({$campos}) VALUES ({$camposActualizar})");
        call_user_func_array( array( $stmt, 'bind_param'), UtilitiesConnection::ref_values($valores));
        
        $stmt->execute();
        if ( $stmt->affected_rows ) {
            return json_encode(['success'=> true, 'message'=> $stmt->insert_id]);
        }
        return json_encode(['success'=> false, 'message'=> $stmt->error]);
    }
    
    /*
     * @table: nombre de la tabla a actualizar
     * @parametros:
     *      "datos" => (array) $datos,
     *      "formatoDatos" => (array) $formato,
     * 
     * - campos: string con los nombres de campos que van a ser utilizados en la inserción
     * - camposActualizar: Obtiene un string con la parte de la sentencia referente a qué campos se insertan
     * - valores: array que contiene cadena con formato parámetros y valores de los mismos, para pasarsela a bind_param de mysqli
     */
    public function insertStatementPrepared($table, $parametros){
        if ( empty( $table ) || empty( $parametros["datos"]) || empty( $parametros["formatoDatos"] )) {
            return false;
        }
        
        // campos => lista de campos concatenados con ,
        // camposActualizar => campos con asignacion =? => lea_extracted=?,lea_crmid=?,lea_status=?
        // valores => array con valores a actualizar
        list( $campos, $camposActualizar, $valores ) = UtilitiesConnection::preparaElementosCRUD($parametros, 'insert'); 

        //eliminar el primer elemento, que representa la lista de parámetros adjuntados (útil para prepared statement, aquí sobra)
        array_shift($valores);
        $stringValores = UtilitiesConnection::bindParams($valores);
        return "INSERT INTO {$table} ({$campos}) VALUES ({$stringValores})";

    }
    
    public function delete($table, $id) {
        // Connect to the database
        $db = $this->connect();

        // Prepary our query for binding
        $stmt = $db->prepare("DELETE FROM {$table} WHERE ID = ?");

        // Dynamically bind values
        $stmt->bind_param('d', $id);

        // Execute the query
        $stmt->execute();

        // Check for successful insertion
        if ( $stmt->affected_rows ) {
                return true;
        }
    }
}
