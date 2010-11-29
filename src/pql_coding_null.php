<?php
/**
 * Пустые правила преобразования
 * (Без преобразований)
 * 
 * @see Null-pattern
 * @author Ti
 * @package pQL
 */
final class pQL_Coding_Null implements pQL_Coding_Interface {
	function toDB($string) {
		return $string;
	}


	function fromDb($string) {
		return $string;
	}
}