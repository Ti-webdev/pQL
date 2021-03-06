<?php
/**
 * Создатель pQL выражений и объектов
 * @author Ti
 * @package pQL
 */
final class pQL_Creater implements ArrayAccess {
	function __construct(pQl $pql) {
		$this->pQL = $pql;
	}


	private $pQL;


	function __call($model, $arguments) {
		// find by pk
		if ($arguments) {
			// is pQL object
			if (is_object($arguments[0]) and $arguments[0] instanceof pQL_Object) {
				return $this->pQL->driver()->findByForeignObject($model, $arguments[0]);
			}
			return $this->pQL->driver()->findByPk($model, $arguments[0]);
		}

		// new object
		return $this->pQL->driver()->getObject($model);
	}


	function __get($key) {
		$q = new pQL_Query($this->pQL->driver());
		return $q->$key;
	}
	

	function offsetExists($offset) {
		return $this->pQL->driver()->modelExists($offset);
	}


	function offsetGet($offset) {
		return $this->__get($offset);
	}


	function offsetSet($offset, $value) {
		throw new RuntimeException('Not implementd');
	}

	
	function offsetUnset($offset) {
		throw new RuntimeException('Not implementd');
	}
}