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
		return preg_replace('#(.)([A-Z])#ue', '"$1"."_".strtolower("$2")', $string);
	}


	function fromDb($string) {
		return preg_replace('#_(\w)#ue', 'strtoupper("$1")', $string);
	}
}