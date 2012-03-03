<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Net\Zabbix\Agent;

if(!defined('ZABBIX_AGENT_DEFAULT_CONFIG_FILE')) {
    define('ZABBIX_AGENT_DEFAULT_CONFIG_FILE','/etc/zabbix/zabbix_agentd.conf',true);
}

class Config
{
    var $_config_filename = ZABBIX_AGENT_DEFAULT_CONFIG_FILE;
    var $_config_array = array();
     
    function __construct($filename=null){
        if( !is_null($filename) ){
           $this->_config_filename = $filename;
        }
        $this->_config_array = $this->loadAgentConfig($this->_config_filename);
    }
    
    function getAgentConfig(){
        return $this->_config_array;
    }    

    function setConfigFilename($filename){
        $this->_config_filename = $filename;
    }
    
    function getConfigFilename(){
        return $this->_config_filename;
    } 
 
    static function loadAgentConfig($filename=null){
        $config_array = array();
        $config_lines = file($filename);
        $config_lines = preg_grep("/^\s*[A-Za-z].+/",$config_lines);
        foreach($config_lines as $line_num => $line){
            list($key,$value) = explode("=",$line,2);
            $key = trim($key);
            $value = trim($value);
            $config_array{$key} = $value; 
        }
        return $config_array;
    }
}


