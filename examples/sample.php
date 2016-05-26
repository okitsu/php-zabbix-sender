<?php

error_reporting(E_ALL | E_STRICT);
require_once __DIR__ . '/../vendor/autoload.php'; // using composer.phar

$defined_hostname = 'localhost';
$undefined_hostname = 'localhost__';

$agentConfig = new \Net\Zabbix\Agent\Config;
$sender = new \Net\Zabbix\Sender();
$sender->importAgentConfig($agentConfig);

## fail request
$sender->addData($undefined_hostname, 'custom.string1', 'string0');

## defined host
# string
$sender->addData($defined_hostname, 'custom.string1', 'string1');
$sender->addData($defined_hostname, 'custom.string1', 'string2');
# int
$sender->addData($defined_hostname, 'custom.int1', '99', '1234567890'); #with timestamp
$sender->addData($defined_hostname, 'custom.int1', intval('2'));
$sender->addData($defined_hostname, 'custom.int1', '1');
# float
$sender->addData($defined_hostname, 'custom.float1', strval('3.14')); # store 3.14
$sender->addData($defined_hostname, 'custom.float1', floatval('3.24')); # store 3
$sender->addData($defined_hostname, 'custom.float1', '6.14');
# text
$sender->addData($defined_hostname, 'custom.text1', 'text1');
$sender->addData($defined_hostname, 'custom.text1', 'text2');
# log
$sender->addData($defined_hostname, 'custom.log1', 'log1');
$sender->addData($defined_hostname, 'custom.log1', 'log2');

$result = $sender->send();

if ($result) {
    $info = $sender->getLastResponseInfo();
    $data = $sender->getLastResponseArray();
    echo "request result: success\n";
    echo "response info: $info\n";
    echo "response data:\n";
    var_dump($data);

    $processed = $sender->getLastProcessed();
    $failed = $sender->getLastFailed();
    $total = $sender->getLastTotal();
    $spent = $sender->getLastSpent();
    echo "parsedInfo: processed = $processed\n";
    echo "parsedInfo: failed    = $failed\n";
    echo "parsedInfo: total     = $total\n";
    echo "parsedInfo: spent     = $spent\n";
} else {
    echo "request result: failed\n";
}


/*
 * method chain pattern
 */
$sender = new \Net\Zabbix\Sender();
$result = $sender
    ->setServerName('localhost')
    ->setServerPort(10051)
    ->setTimeout(10)
    ->addData($undefined_hostname, 'custom.string1', 'string0')
    ->addData($defined_hostname, 'custom.string1', 'string1')
    ->addData($defined_hostname, 'custom.string1', 'string1')
    ->addData($defined_hostname, 'custom.string1', 'string1')
    ->send();
if ($result) {
    $info = $sender->getLastResponseInfo();
    $data = $sender->getLastResponseArray();
    echo "request result: success\n";
    echo "response info: $info\n";
    echo "response data:\n";
    var_dump($data);

    $processed = $sender->getLastProcessed();
    $failed = $sender->getLastFailed();
    $total = $sender->getLastTotal();
    $spent = $sender->getLastSpent();
    echo "parsedInfo: processed = $processed\n";
    echo "parsedInfo: failed    = $failed\n";
    echo "parsedInfo: total     = $total\n";
    echo "parsedInfo: spent     = $spent\n";
} else {
    echo "request result: failed\n";
}
