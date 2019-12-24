<?php

namespace App\Model;

class Lead {
  
  public $lea_id;
  public $lea_ts;
  public $lea_destiny;
  public $lea_extracted;
  public $lea_crmid;
  public $lea_status;
  
  public $sou_id;
  public $leatype_id;

  public $sou_idcrm;
  public $leatype_idcrm;

  public $utm_source;
  public $sub_source;
  public $lea_phone;
  public $lea_mail;
  public $lea_name;
  public $lea_url;
  public $lea_ip;
  public $observations;

  public $lea_aux1;
  public $lea_aux2;
  public $lea_aux3;
  public $lea_aux4;
  public $lea_aux5;
  public $lea_aux6;
  public $lea_aux7;
  public $lea_aux8;
  public $lea_aux9;
  public $lea_aux10;

  public $lea_surname;
  public $lea_campa;
  public $lea_extid;
  public $lea_medium;
  
  public function __construct(){}

  public function getQueryParams(){
    return [
      0 => $this->lea_phone,
      1 => $this->sou_id,
      2 => $this->leatype_id,
    ];
  }

  public function getLeaId(){
    return $this->lea_id;
  }

  public function setLeaId($lea_id){
    $this->lea_id = $lea_id;
  }

  public function getLeaTs(){
    return $this->lea_ts;
  }

  public function setLeaTs($lea_ts){
    $this->lea_ts = $lea_ts;
  }

  public function getLeaType(){
    return $this->leatype_id;
  }

  public function setLeaType($leatype_id){
    $this->leatype_id = $leatype_id;
  }

  public function getSouId(){
    return $this->sou_id;
  }

  public function setSouId($sou_id){
    $this->sou_id = $sou_id;
  }

  public function getLeaDestiny(){
    return $this->lea_destiny;
  }

  public function setLeaDestiny($lea_destiny){
    $this->lea_destiny = $lea_destiny;
  }

  public function getLeaExtracted(){
    return $this->lea_extracted;
  }

  public function setLeaExtracted($lea_extracted){
    $this->lea_extracted = $lea_extracted;
  }

  public function getLeaCrmid(){
    return $this->lea_crmid;
  }

  public function setLeaCrmid($lea_crmid){
    $this->lea_crmid = $lea_crmid;
  }

  public function getLeaStatus(){
    return $this->lea_status;
  }

  public function setLeaStatus($lea_status){
    $this->lea_status = $lea_status;
  }
  
  public function getUtmSource(){
    return $this->utm_source;
  }

  public function setUtmSource($utm_source){
    $this->utm_source = $utm_source;
  }

  public function getSubSource(){
    return $this->sub_source;
  }

  public function setSubSource($sub_source){
    $this->sub_source = $sub_source;
  }

  public function getLeaPhone(){
    return $this->lea_phone;
  }

  public function setLeaPhone($lea_phone){
    $this->lea_phone = $lea_phone;
  }

  public function getLeaMail(){
    return $this->lea_mail;
  }

  public function setLeaMail($lea_mail){
    $this->lea_mail = $lea_mail;
  }

  public function getLeaName(){
    return $this->lea_name;
  }

  public function setLeaName($lea_name){
    $this->lea_name = $lea_name;
  }

  public function getLeaUrl(){
    return $this->lea_url;
  }

  public function setLeaUrl($lea_url){
    $this->lea_url = $lea_url;
  }

  public function getLeaIP(){
    return $this->lea_ip;
  }

  public function setLeaIP($lea_ip){
    $this->lea_ip = $lea_ip;
  }

  public function getObservations(){
    return $this->observations;
  }

  public function setObservations($observations){
    $this->observations = $observations;
  }

  public function getLeaAux1(){
    return $this->lea_aux1;
  }

  public function setLeaAux1($lea_aux1){
    $this->lea_aux1 = $lea_aux1;
  }

  public function getLeaAux2(){
    return $this->lea_aux2;
  }

  public function setLeaAux2($lea_aux2){
    $this->lea_aux2 = $lea_aux2;
  }

  public function getLeaAux3(){
    return $this->lea_aux3;
  }

  public function setLeaAux3($lea_aux3){
    $this->lea_aux3 = $lea_aux3;
  }

  public function getLeaAux4(){
    return $this->lea_aux4;
  }

  public function setLeaAux4($lea_aux4){
    $this->lea_aux4 = $lea_aux4;
  }

  public function getLeaAux5(){
    return $this->lea_aux5;
  }

  public function setLeaAux5($lea_aux5){
    $this->lea_aux5 = $lea_aux5;
  }

  public function getLeaAux6(){
    return $this->lea_aux6;
  }

  public function setLeaAux6($lea_aux6){
    $this->lea_aux6 = $lea_aux6;
  }

  public function getLeaAux7(){
    return $this->lea_aux7;
  }

  public function setLeaAux7($lea_aux7){
    $this->lea_aux7 = $lea_aux7;
  }

  public function getLeaAux8(){
    return $this->lea_aux8;
  }

  public function setLeaAux8($lea_aux8){
    $this->lea_aux8 = $lea_aux8;
  }

  public function getLeaAux9(){
    return $this->lea_aux9;
  }

  public function setLeaAux9($lea_aux9){
    $this->lea_aux9 = $lea_aux9;
  }

  public function getLeaAux10(){
    return $this->lea_aux10;
  }

  public function setLeaAux10($lea_aux10){
    $this->lea_aux10 = $lea_aux10;
  }
}