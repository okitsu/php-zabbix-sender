<?php

class Zabbix_Agent_ConfigTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->config = new Zabbix_Agent_Config;
	}
	
	public function test_set_getConfigFilename()
	{
		$this->config->setConfigFilename('/etc/zabbix/zabbix_agent.conf');
		$this->assertEquals('/etc/zabbix/zabbix_agent.conf',$this->config->getConfigFilename());
	}
	
	public function testAgentConfigHasKey_Server()
	{
		$this->assertArrayHasKey('Server',$this->config->loadAgentConfig('/etc/zabbix/zabbix_agentd.conf'));
	}	

	public function testAgentConfigHasKey_ServerPort()
	{
		$this->assertArrayHasKey('ServerPort',$this->config->loadAgentConfig('/etc/zabbix/zabbix_agentd.conf'));
	}	
	
	public function test_getAgentConfigHasKey_Server()
	{
		$this->assertArrayHasKey('Server',$this->config->getAgentConfig());
	}	
	
	public function test_getAgentConfigHasKey_ServerPort()
	{
		$this->assertArrayHasKey('ServerPort',$this->config->getAgentConfig());
	}	
}
