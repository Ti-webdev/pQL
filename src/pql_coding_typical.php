<?php
/**
 * Типичные правила преобразования
 * В базе слова разделяются через символ подчеркивания "_", в коде CamelCase
 * 
 * @see http://en.wikipedia.org/wiki/CamelCase
 * @author Ti
 * @package pQL
 */
final class pQL_Coding_Typical implements pQL_Coding_Interface {
	function toDB($string) {
		return strtolower(preg_replace('#(.)([A-Z])#u', '$1_$2', $string));
	}


	function fromDb($string) {
		return preg_replace_callback('#_(\w)#u', function($matches) { return strtoupper($matches[1]); }, $string);
	}
}