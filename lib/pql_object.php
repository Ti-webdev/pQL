<?php
abstract class pQL_Object {
	function __construct($properties) {
		$this->properties = $properties;
	}
	
	
	function getClass() {
		return get_class($this);
	}
	
	
	protected function getToStringField() {
		return $this->getPQL()->driver()->getToStringField($this->getClass());
	}


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
	function set($property, $value) {
		$this->newProperties[$property] = $value;
		return $this;
	}


	function get($property) {
		if (array_key_exists($property, $this->newProperties)) return $this->newProperties[$property];
		return $this->properties[$property];
	}


	final function __get($property) {
		return $this->get($property);
	}


	final function __set($property, $value) {
		return $this->set($property, $value);
	}
	
	
	function __toString() {
		return (string) $this->get($this->getToStringField());
	}
}