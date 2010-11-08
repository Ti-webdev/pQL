<?php
abstract class pQL_Object {
	abstract protected function getClass();

	function save() {
		$id = $this->getPQL()->driver()->save($this->getClass(), $this->properties);
		if ($id) $this->id = $id;
		return $this;
	}


	private $pQL;
	function setPQL(pQL $pQL) {
		$this->pQL = $pQL;
	}


	protected function getPQL() {
		return $this->pQL;
	}


	private $properties = array();
	function __set($key, $value) {
		$this->properties[$key] = $value;
	}


	function __get($key) {
		return $this->properties[$key];
	}
}