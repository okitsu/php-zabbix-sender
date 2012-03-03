<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

error_reporting(E_ALL | E_STRICT);
$src = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
set_include_path( get_include_path() . PATH_SEPARATOR . $src);

function my_autoload($className){
    $src = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
    $replaces = array(
            '_'  => DIRECTORY_SEPARATOR,
            '\\' => DIRECTORY_SEPARATOR,
            '.'  => '', 
        );
    $classPath = str_replace(array_keys($replaces),array_values($replaces),$className);
    $fileName = $src . '/' . $classPath . '.php';

    if(is_file($fileName)){
        require_once($fileName);
    } 
} 

spl_autoload_register('my_autoload');

