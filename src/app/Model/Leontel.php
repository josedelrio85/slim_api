<?php

namespace App\Model;

class Leontel {
  // lea_ fields
  public $lea_id;
  public $lea_type;
  public $lea_ts; 
  public $lea_source;
  public $lea_lot;
  public $lea_assigned;
  public $lea_scheduled; 
  public $lea_scheduled_auto;
  public $lea_cost;
  public $lea_seen;
  public $lea_closed;
  public $lea_new;
  public $lea_client;
  
  // most used fields
  public $TELEFONO;
  public $nombre;
  public $apellido1;
  public $apellido2;
  public $dninie;
  public $observaciones;
  public $url;
  public $asnef;
  public $wsid;
  public $ip;
  public $Email;
  public $observaciones2;

  public $hashid;
  public $nombrecompleto;
  public $movil;
  public $tipocliente;
  public $escliente;
  public $tiposolicitud;
  public $fechasolicitud;
  public $poblacion;
  public $provincia;
  public $direccion;
  public $cargo;
  public $cantidaddeseada;
  public $cantidadofrecida;
  public $ncliente;
  public $calle;
  public $cp;
  public $interesadoen;
  public $numero;
  public $compaiaactualfibraadsl;
  public $companiaactualmovil;
  public $fibraactual;
  public $moviactuallineaprincipal;
  public $numerolineasadicionales;
  public $tarifaactualsiniva;
  public $motivocambio;
  public $importeaumentado;
  public $importeretirado;
  public $tipoordenador;
  public $sector;
  public $presupuesto;
  public $rendimiento;
  public $movilidad;
  public $tipouso;
  public $Office365;

  public function __construct(){}

  public function getLeaId(){
    return $this->lea_id;
  }

  public function getLeaTs(){
    return $this->lea_ts;
  }

  public function setLeaTs($lea_ts){
    $this->lea_ts = $lea_ts;
  }

  public function getLeaType(){
    return $this->lea_type;
  }

  public function setLeaType($lea_type){
    $this->lea_type = $lea_type;
  }

  public function getLeaSource(){
    return $this->lea_source;
  }

  public function setLeaSource($lea_source){
    $this->lea_source = $lea_source;
  }

  public function getLeaAssigned(){
    return $this->lea_assigned;
  }

  public function setLeaAssigned($lea_assigned){
    $this->lea_assigned = $lea_assigned;
  }

  public function getLeaScheduled(){
    return $this->lea_scheduled;
  }

  public function setLeaScheduled($lea_scheduled){
    $this->lea_scheduled = $lea_scheduled;
  }

  public function getLeaScheduledAuto(){
    return $this->lea_scheduled_auto;
  }

  public function setLeaScheduledAuto($lea_scheduled_auto){
    $this->lea_scheduled_auto = $lea_scheduled_auto;
  }

  public function getLeaClosed(){
    return $this->lea_closed;
  }

  public function setLeaClosed($lea_closed){
    $this->lea_closed = $lea_closed;
  }

  public function getTelefono(){
    return $this->TELEFONO;
  }

  public function setTelefono($telefono){
    $this->TELEFONO = $telefono;
  }

  public function getNombre(){
    return $this->nombre;
  }

  public function setNombre($nombre){
    $this->nombre = $nombre;
  }

  public function getApellido1(){
    return $this->apellido1;
  }

  public function setApellido1($apellido1){
    $this->apellido1 = $apellido1;
  }

  public function getApellido2(){
    return $this->apellido2;
  }

  public function setApellido2($apellido2){
    $this->apellido2 = $apellido2;
  }

  public function getDninie(){
    return $this->dninie;
  }

  public function setDninie($dninie){
    $this->dninie = $dninie;
  }

  public function getObservaciones(){
    return $this->observaciones;
  }

  public function setObservaciones($observaciones){
    $this->observaciones = $observaciones;
  }
}