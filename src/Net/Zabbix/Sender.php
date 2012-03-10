<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Net\Zabbix;

/**
    define default configuration
 */

if(!defined('ZABBIX_SENDER_DEFAULT_SERVERNAME')) {
    define('ZABBIX_SENDER_DEFAULT_SERVERNAME','localhost',true);
}
if(!defined('ZABBIX_SENDER_DEFAULT_SERVERPORT')) {
    define('ZABBIX_SENDER_DEFAULT_SERVERPORT',10051,true);
}
if(!defined('ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT')) {
    define('ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT',30,true);
}

# ZABBIX Sender Protocol
if(!defined('ZABBIX_SENDER_PROTOCOL_HEADER_STRING')) {
    define('ZABBIX_SENDER_PROTOCOL_HEADER_STRING','ZBXD',true);
}
if(!defined('ZABBIX_SENDER_PROTOCOL_VERSION')) {
    define('ZABBIX_SENDER_PROTOCOL_VERSION',1,true);
}

class Sender {
   
    var $_servername = ZABBIX_SENDER_DEFAULT_SERVERNAME;
    var $_serverport = ZABBIX_SENDER_DEFAULT_SERVERPORT;
    var $_timeout    = ZABBIX_SENDER_DEFAULT_CONNECTION_TIMEOUT;

    var $_lastResponseInfo  = null;
    var $_lastResponseArray = null;
    var $_lastProcessed     = null;
    var $_lastFailed        = null;
    var $_lastSpent         = null;
    var $_lastTotal         = null;

    var $_data; 

    function __construct($servername = null,$serverport = null)
    {
        if(! is_null($servername)){
            $this->_servername = $servername;
        }
        if(! is_null($serverport)){
            $this->_serverport = $serverport;
        }
        $this->initData();
    }
    
    function initData(){
        $this->_data = $this->_createDataTemplate();
    }

    function _createDataTemplate()
    {
        return array(
            "request" => "sender data",
            "data" => array()
        );
    }

    function importAgentConfig(Net\Zabbix\Agent\Config $agentConfig){
        $config = $agentConfig->getAgentConfig();
        $this->_servername = $config{'Server'}; 
        $this->_serverport = $config{'ServerPort'}; 
    }
    
    function setTimeout($timeout=0){
        if(intval($timeout) > 0){
            $this->_timeout = $timeout;
        }
    }   

    function getTimeout(){
        return $this->_timeout;
    }
 
    function addData($hostname=null,$key=null,$value=null,$clock=null)
    {
        if(is_null($clock)){
            array_push($this->_data{"data"},
                        array(
                                "host"=>$hostname,
                                "value"=>$value,
                                "key"=>$key,
                            )
                        );
        }else{
            array_push($this->_data{"data"},
                        array(
                                "host"=>$hostname,
                                "value"=>$value,
                                "key"=>$key,
                                "clock"=>$clock,
                            )
                        );
        }
    }
    
    
    function getDataArray()
    {
        return $this->_data{"data"};
    }

    function _buildSendData(){
        $json_data   = json_encode( array_map(
                                        function($t){ return is_string($t) ? utf8_encode($t) : $t; },
                                        $this->_data
                                    ) 
                                );
        $json_length = strlen($json_data);
        $data_header = pack("aaaaCCCCCCCCC",
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,0,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,1,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,2,1),
                                substr(ZABBIX_SENDER_PROTOCOL_HEADER_STRING,3,1),
                                intval(ZABBIX_SENDER_PROTOCOL_VERSION),
                                ($json_length & 0xFF),
                                ($json_length & 0x00FF)>>8,
                                ($json_length & 0x0000FF)>>16,
                                ($json_length & 0x000000FF)>>24,
                                0x00,
                                0x00,
                                0x00,
                                0x00
                            );
        return ($data_header . $json_data);
    }

    function _parseResponseInfo($info=null){
        # "Processed 1 Failed 1 Total 2 Seconds spent 0.000035"         
        list(,$processed,,$failed,,$total,,$spent) = explode(" ",$info);
        $parsedInfo = array(
                "processed" => intval($processed),
                "failed"    => intval($failed),
                "total"     => intval($total),
                "spent"     => floatval($failed),
            );
        return $parsedInfo;
    }
    
    function getLastResponseInfo(){
        return $this->_lastResponseInfo;
    }   
    
    function getLastResponseArray(){
        return $this->_lastResponseArray;
    }   
    
    function getLastProcessed(){
        return $this->_lastProcessed;
    }

    function getLastFailed(){
        return $this->_lastFailed;
    }

    function getLastSpent(){
        return $this->_lastSpent;
    }

    function getLastTotal(){
        return $this->_lastTotal;
    }
    
    function _clearLastResponseData(){
        $this->_lastResponseInfo    = null;
        $this->_lastResponseArray   = null;
        $this->_lastProcessed       = null;
        $this->_lastFailed          = null;
        $this->_lastSpent           = null;
        $this->_lastTotal           = null;
    }
 
    function send()
    {
        $recvData = "";
        $sendData = $this->_buildSendData();
        $sock = fsockopen($this->_servername,intval($this->_serverport),$errno,$errmsg,$this->_timeout);
        fputs($sock,$sendData);
        while( !feof($sock) ){
            $recvData .= fgets($sock,8192);
        }
        fclose($sock);
        $recvProtocolHeader = substr($recvData,0,4);
        if( $recvProtocolHeader == "ZBXD"){
            $responseData               = substr($recvData,13);
            $responseArray              = json_decode($responseData,true);
            $this->_lastResponseArray   = $responseArray;
            $this->_lastResponseInfo    = $responseArray{'info'}; 
            $parsedInfo                 = $this->_parseResponseInfo($this->_lastResponseInfo); 
            $this->_lastProcessed       = $parsedInfo{'processed'};
            $this->_lastFailed          = $parsedInfo{'failed'};
            $this->_lastSpent           = $parsedInfo{'spent'};
            $this->_lastTotal           = $parsedInfo{'total'};
            if($responseArray{'response'} == "success"){
                $this->initData();
                return true;
            }
        }
        $this->_clearLastResponseData();
        return false;
    }
}


