<?php

namespace Net\Zabbix;

use Net\Zabbix\Exception\SenderNetworkException;
use Net\Zabbix\Exception\SenderProtocolException;

class Sender {

    private $_serversList;

    private $_servername;
    private $_serverport;

    private $_timeout = 5;

    private $_protocolHeaderString = 'ZBXD';
    private $_protocolVersion      = 1;

    private $_lastResponseInfo  = null;
    private $_lastResponseArray = null;
    private $_lastProcessed     = null;
    private $_lastFailed        = null;
    private $_lastSpent         = null;
    private $_lastTotal         = null;

    private $_socket;
    private $_data;

    /**
     * __construct
     *
     * @param  string  $servername
     * @param  integer $serverport
     * @return void
     */
    function __construct($serverslist = 'localhost', $serverport = 10051)
    {
        $this->setServerName($serverslist);
        $this->setServerPort($serverport);
        $this->initData();
    }
    
    function initData()
    {
        $this->_data = array(
            "request" => "sender data",
            "data" => array()
        );
    }

    function importAgentConfig(Agent\Config $agentConfig)
    {
        $this->setServerName($agentConfig->getServer());
        $this->setServerPort($agentConfig->getServerPort());
        return $this;
    }
    
    function setServerName($serverslist){
        $this->_serversList = explode(",",$serverslist);
        $this->_servername = array_shift($this->_serversList);
        return $this;
    }
    
    function setServerPort($serverport){
        if (is_int($serverport)) {
            $this->_serverport = $serverport;
        }
        return $this;
    }
    
    function setTimeout($timeout=0){
        if( (is_int($timeout) or is_numeric($timeout) ) and intval($timeout) > 0){
            $this->_timeout = $timeout;
        }
        return $this;
    }   

    function getTimeout(){
        return $this->_timeout;
    }

    function setProtocolHeaderString($headerString){
        $this->_protocolHeaderString = $headerString;
        return $this;
    }

    function setProtocolVersion($version){
        if (is_int($version) and $version > 0) {
            $this->_protocolVersion = $version;
        }
        return $this;
    }

    function addData($hostname=null,$key=null,$value=null,$clock=null)
    {
        $input = array("host"=>$hostname,"value"=>$value,"key"=>$key);
        if( isset($clock) ){
            $input{"clock"} = $clock;
        }
        array_push($this->_data{"data"},$input);
        return $this;
    }
    
    function getDataArray()
    {
        return $this->_data{"data"};
    }

    private function _buildSendData(){
        $json_data   = json_encode( array_map(
                                        function($t){ return is_string($t) ? utf8_encode($t) : $t; },
                                        $this->_data
                                    ) 
                                );
        $json_length = strlen($json_data);
        $data_header = pack("aaaaCCCCCCCCC",
                                substr($this->_protocolHeaderString,0,1),
                                substr($this->_protocolHeaderString,1,1),
                                substr($this->_protocolHeaderString,2,1),
                                substr($this->_protocolHeaderString,3,1),
                                intval($this->_protocolVersion),
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

    protected function _parseResponseInfo($info=null){
        # info: "Processed 1 Failed 1 Total 2 Seconds spent 0.000035"
        $parsedInfo = null;       
        if(isset($info)){
            list(,$processed,,$failed,,$total,,,$spent) = explode(" ",$info);
            $parsedInfo = array(
                "processed" => intval($processed),
                "failed"    => intval($failed),
                "total"     => intval($total),
                "spent"     => $spent,
            );
        }
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
    
    private function _clearLastResponseData(){
        $this->_lastResponseInfo    = null;
        $this->_lastResponseArray   = null;
        $this->_lastProcessed       = null;
        $this->_lastFailed          = null;
        $this->_lastSpent           = null;
        $this->_lastTotal           = null;
    }

    private function _close(){
        if($this->_socket){
            fclose($this->_socket);
        }
    }

    /**
     * connect to Zabbix Server
     * @throws Net\Zabbix\Exception\SenderNetworkException
     *
     */
    private function _connect(){
        $this->_socket = @fsockopen( $this->_servername,
                                        intval($this->_serverport),
                                        $errno,
                                        $errmsg,
                                        $this->_timeout);
        if(! $this->_socket){
            if (count($this->_serversList)>0){
                $this->_servername  = array_shift($this->_serversList);
                $this->_connect();
            }else{
                throw new SenderNetworkException(sprintf('%s,%s',$errno,$errmsg));
            }
        }
    }
    
    /**
     * write data to socket
     * @throws Net\Zabbix\Exception\SenderNetworkException
     *
     */
    private function _write($socket,$data){
        if(! $socket){
            throw new SenderNetworkException('socket was not writable,connect failed.');
        }
        $totalWritten = 0;
        $length = strlen($data);
        while( $totalWritten < $length ){
            $writeSize = @fwrite($socket,$data);
            if($writeSize === false){
                return false;
            }else{
                $totalWritten += $writeSize;
                $data = substr($data,$writeSize);
            }
        }
        return $totalWritten; 
    }

    /**
     * read data from socket
     * @throws Net\Zabbix\Exception\SenderNetworkException
     *
     */ 
    private function _read($socket){
        if(! $socket){
            throw new SenderNetworkException('socket was not readable,connect failed.');
        }
        $recvData = "";
        while(!feof($socket)){
            $buffer = fread($socket,8192);
            if($buffer === false){
                return false; 
            }
            $recvData .= $buffer;
        }
        return $recvData; 
    }
    

    /**
     * main 
     * @throws Net\Zabbix\Exception\SenderNetworkException
     * @throws Net\Zabbix\Exception\SenderProtocolException
     *
     */ 
    function send(){
        $sendData = $this->_buildSendData();
        $datasize = strlen($sendData);
 
        $this->_connect();
      
        /* send data to zabbix server */ 
        $sentsize = $this->_write($this->_socket,$sendData);
        if($sentsize === false or $sentsize != $datasize){
            throw new SenderNetworkException('cannot receive response');
        }
        
        /* receive data from zabbix server */ 
        $recvData = $this->_read($this->_socket);
        if($recvData === false){
            throw new SenderNetworkException('cannot receive response');
        }
        
        $this->_close();
        
        $recvProtocolHeader = substr($recvData,0,4);
        if( $recvProtocolHeader == "ZBXD"){
            $responseData               = substr($recvData,13);
            $responseArray              = json_decode($responseData,true);
            if(is_null($responseArray)){
                throw new SenderProtocolException('invalid json data in receive data'); 
            }
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
            }else{
                $this->_clearLastResponseData();
                return false; 
            }
        }else{
            $this->_clearLastResponseData();
            throw new SenderProtocolException('invalid protocol header in receive data'); 
        }
    }
}


