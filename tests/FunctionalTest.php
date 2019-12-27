<?php
namespace Tests;

class FunctionalTest extends BaseTest {

  public function testGetSouIdcrm() {
    
    $response = $this->runApp('GET', '/');
    $container = $this->getContainer();

    $tests = [
      [
        "description" => "No existent sou_id",
        "sou_id" => 909,
        "result" => false,
        "expectedResult" => 0,
      ],
      [
        "description" => "Existent sou_id",
        "sou_id" => 5,
        "result" => true,
        "expectedResult" => [
          "sou_idcrm" => 6,
        ],
      ],
    ];

    foreach($tests as $k => $test) {

      $mock =  $this->getMockBuilder(\App\Functions\Functions::class)
        ->setConstructorArgs(array(true, $container))
        ->setMethods(['getSouIdcrm'])
        ->getMock();
      
      $mock
        ->method('getSouIdcrm')
        ->willReturn($test['expectedResult']['sou_idcrm']);

      $response = $mock->getSouIdcrm($test['sou_id']);

      $this->assertEquals($response, $test['expectedResult']['sou_idcrm']);
    }
  }

  public function testGetTypeIdcrm() {
    // Needed to start the mock environment
    $response = $this->runApp('GET', '/');
    $container = $this->getContainer();

    $tests = [
      [
        "description" => "No existent leatype_id",
        "leatype_id" => 909,
        "result" => false,
        "expectedResult" => [
          "leatype_idcrm" => 0,
        ],
      ],
      [
        "description" => "Existent leatype_id",
        "leatype_id" => 1,
        "result" => true,
        "expectedResult" => [
          "leatype_idcrm" => 2,
        ],
      ],
    ];

    foreach($tests as $k => $test) {

      $mock =  $this->getMockBuilder(\App\Functions\Functions::class)
        ->setConstructorArgs(array(true, $container))
        ->setMethods(['getSouIdcrm'])
        ->getMock();
      
      $mock
        ->method('getSouIdcrm')
        ->willReturn($test['expectedResult']['leatype_idcrm']);

      $response = $mock->getSouIdcrm($test['leatype_id']);

      $this->assertEquals($response, $test['expectedResult']['leatype_idcrm']);
    }
  }

  public function testIsCampaignOnTime() {

    $response = $this->runApp('GET', '/');
    $container = $this->getContainer();

    $tests = [
      [
        "description" => "Campaign does not exists",
        "data" => [
          "sou_id" => 909,
        ],
        "curlMockResult" => json_encode([
          'results' => false,
        ]),
        "expectedResult" => false,
      ],
      [
        "description" => "Existent sou_id",
        "data" => [
          "sou_id" => 6,
        ],
        "curlMockResult" => json_encode([
          'results' => true,
        ]),
        "expectedResult" => true,
      ],
      [
        "description" => "Existent sou_id",
        "data" => [
          "sou_id" => 6,
        ],
        "curlMockResult" => json_encode([
          'results' => true,
        ]),
        "expectedResult" => true,
      ],
    ];

    foreach($tests as $k => $test) {
      $curlMock =  $this->getMockBuilder(\App\Functions\Functions::class)
        ->setConstructorArgs(array(true, $container))
        ->setMethods(['curlRequest'])
        ->getMock();
        
      $curlMock
        ->method('curlRequest')
        ->willReturn($test['curlMockResult']);

      $response = $curlMock->curlRequest($test['data'],"");

      $this->assertEquals($test['curlMockResult'] , $response);

      if(!is_null($response)){
        $resp = json_decode($response);
        $this->assertEquals($resp->results, $test['expectedResult']);


      }
    }
  }

  public function testIsLeadOpen() {
    $response = $this->runApp('GET', '/');
    $container = $this->getContainer();

    $data = [
      "lea_phone" => '665932355',
      "sou_id" => 6,
      "leatype_id" => 2,
    ];

    $tests = [
      [
        "description" => "Lead is open in database",
        "curlMockResult" => json_encode([
          'success' => true,
          'message' => '',
          'error' => null
        ]),
        "expectedResult" => true,
      ],
      [
        "description" => "Lead is closed in database",
        "curlMockResult" => json_encode([
          'success' => false,
          'message' => '',
          'error' => null
        ]),
        "expectedResult" => false,
      ],
    ];

    foreach($tests as $k => $test) {
      $curlMock =  $this->getMockBuilder(\App\Functions\Functions::class)
        ->setConstructorArgs(array(true, $container))
        ->setMethods(['curlRequest'])
        ->getMock();
        
      $curlMock
        ->method('curlRequest')
        ->willReturn($test['curlMockResult']);

      $response = $curlMock->curlRequest($data,"");

      $this->assertEquals($test['curlMockResult'] , $response);

      if(!is_null($response)){
        $resp = json_decode($response);
        $this->assertEquals($resp->success, $test['expectedResult']);
      }
    }
  }
}