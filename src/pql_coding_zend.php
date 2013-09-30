<?php
/**
 * Типичные правила преобразования классов как в Zend Framework
 * В базе table_name; в коде Table_Name
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Coding_Zend implements pQL_Coding_Interface {
	function toDB($string) {
		return strtolower($string);
	}


	function fromDb($string) {
		return preg_replace_callback('#(^|_)\w#u', function($matches) { return strtoupper($matches[0]); }, $string);
	}
}