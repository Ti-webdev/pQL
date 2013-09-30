<?php
chdir(dirname(__FILE__));

if (!class_exists('PHPUnit_Framework_TestSuite', false)) {
    require_once 'PHPUnit/Autoload.php';
}

error_reporting(E_ALL|E_STRICT);
#require_once __DIR__.'/../pql.php';
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../src');
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));
spl_autoload_register('spl_autoload');
