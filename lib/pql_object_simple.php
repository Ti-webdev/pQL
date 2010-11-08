<?php
/**
 * Реализация простого объекта pQL
 * @author Ti
 */
class pQL_Object_Simple extends pQL_Object {
	protected function getClass() {
		return $this->class;
	}
	
	
	private $class;
	function setClass($class) {
		$this->class = $class;
	}
}