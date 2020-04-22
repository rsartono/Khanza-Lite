<?php

require_once('functions.php');

class Autoloader
{
    public static function init($className)
    {
        $className = explode('\\', $className);
        $file = array_pop($className);
        $file = strtolower(implode('/', $className)).'/'.$file.'.php';

        if (strpos($_SERVER['SCRIPT_NAME'], '/'.ADMIN.'/') !== false) {
            $file = '../'.$file;
        }
        if (is_readable($file)) {
            require_once($file);
        }
    }
}

//header(gz64_decode("eNqL0HUuSk0sSU3Rdaq0UnBKLEnLSSxRsEmCMPTyi9LtANXtDCw"));
spl_autoload_register('Autoloader::init');
