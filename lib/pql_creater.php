<?php
/**
 * Создатель pQL выражений и объектов
 * @author Ti
 * @package pQL
 */
final class pQL_Creater {
	function __construct(pQl $pql) {
		$this->pQL = $pql;
	}


	private $pQL;


	function __call($class, $arguments) {
		// find by pk
		if ($arguments and $arguments[0]) {
			return $this->pQL->driver()->findByPk($class, $arguments[0]);
		}

		// new object
		$object = $this->pQL->driver()->getObject($class);
		$object->setPQL($this->pQL);
		return $object;
	}


	function __get($key) {
		$q = new pQL_Query($this->pQL->driver());
		return $q->$key;
	}
}