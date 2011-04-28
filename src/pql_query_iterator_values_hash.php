<?php
/**
 * Итератор pQL запроса, в значениях - ассоциативный массив
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Iterator_Values_Hash implements pQL_Query_Iterator_Values_Interface {
	/**
	 * @var array | null 
	 */
	private $keys;
	function __construct($keys) {
		$this->keys = $keys;
	}
	
	
	function getValue($current) {
		$result = array();
		foreach($this->keys as $i=>$name) {
			$result[$name] = $current[$i];
		}
		return $result;
	}
}