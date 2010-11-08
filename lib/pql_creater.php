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


	function __call($table, $arguments) {
		// find by pk
		if ($arguments and $arguments[0]) {
			return $this->pQL->driver()->findByPk($table, $arguments[0]);
		}

		// new object
		return $this->pQL->driver()->create($table);
	}
	
	
	function __get($key) {
		$q = new pQL_Query;
		return $q->$key;
	}
}