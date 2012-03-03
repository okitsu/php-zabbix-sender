<?php

require_once('Zabbix/Sender.php');
require_once('Zabbix/Agent/Config.php');

$agentConfig = new Zabbix_Agent_Config();
$sender = new Zabbix_Sender();

$sender->importAgentConfig($agentConfig);
$sender->addData("localhost","custom.string1","value1");
$sender->addData("localhost","custom.string1","value1");
$sender->send();

