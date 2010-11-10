<?php
abstract class pQL_Object {
	abstract protected function getClass();

	function save() {
		$result = $this->getPQL()->driver()->save($this->getClass(), $this->newProperties, $this->properties);
		$this->properties = array_merge($this->properties, $result);
		$this->newProperties = array();
		return $this;
	}


	private $pQL;
	final function setPQL(pQL $pQL) {
		$this->pQL = $pQL;
	}


	final protected function getPQL() {
		return $this->pQL;
	}


	private $properties = array();
	private $newProperties = array();
	function __set($key, $value) {
		$this->newProperties[$key] = $value;
		return $this;
	}


	function __get($key) {
		if (array_key_exists($key, $this->newProperties)) return $this->newProperties[$key];
		return $this->properties[$key];
	}
}