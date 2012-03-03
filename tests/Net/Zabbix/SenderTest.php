<?php

class Zabbix_SenderTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->sender = new Net\Zabbix\Sender;
	}
	
	public function test_set_getTimeout()
	{
		$timeout = 99;
		$this->sender->setTimeout($timeout);
		$this->assertEquals($timeout,$this->sender->getTimeout());
	}
	
	public function test_createDataTemplate()
	{
		$this->assertArrayHasKey('request',$this->sender->_createDataTemplate() );	
		$this->assertArrayHasKey('data',$this->sender->_createDataTemplate() );	
	}
	
	public function test_addData()
	{
		$sender = new \Net\Zabbix\Sender;
		$sender->addData("hostname1","key1","value1");	
		$sender->addData("hostname2","key2","value2");
		$dataArray = $sender->getDataArray();
		$this->assertCount(2,$dataArray);
	}

}
