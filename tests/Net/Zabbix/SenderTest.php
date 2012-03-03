<?php

class Zabbix_SenderTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->sender = new Zabbix_Sender;
	}
	
	public function test_set_getTimeout()
	{
		$this->sender->setTimeout(99);
		$this->assertEquals(99,$this->sender->getTimeout());
	}
	
	public function test_createDataTemplate()
	{
		$this->assertArrayHasKey('request',$this->sender->_createDataTemplate() );	
		$this->assertArrayHasKey('data',$this->sender->_createDataTemplate() );	
	}		
}
