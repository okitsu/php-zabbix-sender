<?php

namespace Net\Zabbix\Agent;

class Config
{
    /* @var string */
    private $_config_filename;
    /* @var array */
    private $_config_array = array();
     
    function __construct($filename=null){
        $this->_config_filenme = isset($filename) && is_readable($filename)
            ? $filename : '/etc/zabbix/zabbix_agentd.conf';

        $this->_config_array = $this->_load($this->_config_filename);
    }
    
    function getConfigArray(){
        return $this->_config_array;
    }    

    function getServer(){
        $return_value = null;
        if( array_key_exists('Server',$this->_config_array) )
        {
            $return_value = $this->_config_array{'Server'}; 
        }
        return $return_value;
    }
    
    function getServerPort(){
        $return_value = null;
        if( array_key_exists('ServerPort',$this->_config_array)
            and is_numeric($this->_config_array{'ServerPort'}) )
        {
            $return_value = intval($this->_config_array{'ServerPort'}); 
        }
        return $return_value;
    }

    function getCurrentConfigFilename()
    {
        return $this->_config_filename;
    } 
 
    function _load($filename=null){
        $config_array = array();
        if( isset($filename) and is_readable($filename) ){
            $config_lines = file($filename);
            $config_lines = preg_grep("/^\s*[A-Za-z].+\=.+/",$config_lines);
            foreach($config_lines as $line_num => $line){
                list($key,$value) = explode("=",$line,2);
                $key = trim($key);
                $value = trim($value);
                $config_array{$key} = $value; 
            }
        }
        return $config_array;
    }
}


