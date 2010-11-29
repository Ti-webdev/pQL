<?php
/**
 * Реализация простого объекта pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Object_Simple extends pQL_Object {
	function __construct(pQL $pQL, $properties, $class) {
		$this->class = $class;
		parent::__construct($pQL, $properties);
	}
	
	
	function getClass() {
		return $this->class;
	}


	private $class;
}