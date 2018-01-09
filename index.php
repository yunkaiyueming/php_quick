<?php
define('SYSDEBUG', false);

if (SYSDEBUG) {
    version_compare(PHP_VERSION, '5.3.0', '>=') || exit("Requires PHP 5.3.0 or newer, this version is " . PHP_VERSION);
    error_reporting(E_ALL ^ E_NOTICE);      //developing
} else {
    error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE);  //production
}

define('SYSCHARSET', 'UTF-8');
define('INCPATH', realpath(dirname(__FILE__)) . '/');
define('APPPATH', INCPATH  . "app/");
define('SYSPATH', INCPATH."sys/");
define('LOGPATH', 'log');

header('Content-type: text/plain; charset=' . SYSCHARSET);
//header('Content-type: text/html; charset=' . SYSCHARSET);
date_default_timezone_set('Asia/Shanghai');

require SYSPATH . 'class/base.php';

spl_autoload_register(array('base', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');

set_exception_handler(array('base', 'exception_handler'));
set_error_handler(array('base', 'error_handler'));

run::i();