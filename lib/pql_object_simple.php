<?php
/**
 * Реализация простого объекта pQL
 * @author Ti
 */
class pQL_Object_Simple extends pQL_Object {
	function __construct($properties, $class) {
		$this->class = $class;
		parent::__construct($properties);
	}
	
	
	protected function getClass() {
		return $this->class;
	}
	
	
	private $class;
}