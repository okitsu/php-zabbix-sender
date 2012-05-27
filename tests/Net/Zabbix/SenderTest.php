<?php

class Zabbix_SenderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sender = new Net\Zabbix\Sender('localhost',10051);
        $agentConfig = new Net\Zabbix\Agent\Config();
        $this->sender->importAgentConfig($agentConfig);
    }
    
    public function test_set_getTimeout()
    {
        $timeout = 99;
        $this->sender->setTimeout($timeout);
        $this->assertEquals($timeout,$this->sender->getTimeout());
    }
    
    function _addData(\Net\Zabbix\Sender $sender){
        $sender->addData("hostname1","key1","value1");    
        $sender->addData("hostname2","key2","value2");
        $sender->addData("hostname3","key3","value3",1234567890);
    }
    
    public function test_addData()
    {
        $this->_addData($this->sender);
        $dataArray = $this->sender->getDataArray();
        $this->assertCount(3,$dataArray);
    }

    public function test_unsetData()
    {
        $this->_addData($this->sender);
        $this->sender->initData();
        $dataArray = $this->sender->getDataArray();
        $this->assertCount(0,$dataArray);
    }

    /**
     * @expectedException Net\Zabbix\Exception\SenderNetworkException
     */
    public function test_send_fail_invalid_hostname()
    {
        $this->sender->setServerName('invalid-hostname'); 
        $result = $this->sender->send();
        $this->assertFalse($result);
    }
    
    /**
     * @expectedException Net\Zabbix\Exception\SenderNetworkException
     */
    public function test_send_fail_invalid_port()
    {
        $this->sender->setServerPort(11111); 
        $result = $this->sender->send();
        $this->assertFalse($result);
    }
    
    public function test_send()
    {
        $this->_addData($this->sender);
        $result = $this->sender->send();
        $this->assertTrue($result);
        $this->assertEquals(3,$this->sender->getLastFailed());
        $this->assertEquals(0,$this->sender->getLastProcessed());
        $this->assertEquals(3,$this->sender->getLastTotal());
        $this->assertGreaterThanOrEqual(0.000000001,$this->sender->getLastSpent());
        $this->assertArrayHasKey('info',$this->sender->getLastResponseArray());
        $this->assertArrayHasKey('response',$this->sender->getLastResponseArray());
        $this->assertRegExp('/Processed \d+ Failed \d+ Total \d+ Seconds spent \d+\.\d+/',
                                $this->sender->getLastResponseInfo());
    }
    
}
