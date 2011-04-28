<?php
/**
 * Итератор pQL запроса, в значениях - простое значение одного поля
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Iterator_Values_Field implements pQL_Query_Iterator_Values_Interface {
	/**
	 * Номер поля в выборке, используемое в качестве значений итератора
	 * @var int
	 */
	private $index;
	function __construct($index) {
		$this->index = $index;
	}


	function getValue($current) {
		return $current[$this->index];
	}
}