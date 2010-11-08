<?php
/**
 * Создатель pQL выражений и объектов
 * @author Ti
 * @package pQL
 */
class pQL_Creater {
	function __construct(pQL $pql) {
		$this->pQL = $pql;
	}


	private $pQL;


	function __call($class, $arguments) {
		// find by pk
		if ($arguments and $arguments[0]) {
			return $this->pQL->driver()->findByPk($class, $arguments[0]);
		}

		// new object
		$object = $this->pQL->driver()->create($class);
		$object->setPQL($this->pQL);
		return $object;
	}
	
	
	function __get($key) {
		$q = new pQL_Query;
		return $q->$key;
	}
}