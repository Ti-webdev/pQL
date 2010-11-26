<?php
error_reporting(E_ALL|E_STRICT);
#require_once __DIR__.'/../pql.php';
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../src');
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));
spl_autoload_register('spl_autoload');
