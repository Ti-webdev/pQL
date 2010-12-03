<?php
/**
 * Базовый класс объекта pQL
 * 
 * @author Ti
 * @package pQL
 */
abstract class pQL_Object {
	function __construct(pQL $pQL, $properties) {
		$this->properties = $properties;
		$this->pQL = $pQL;
	}
	
	
	function getClass() {
		return get_class($this);
	}
	
	
	protected function getToStringField() {
		return $this->getDriver()->getToStringField($this->getClass());
	}


	function save() {
		$result = $this->getDriver()->save($this->getClass(), $this->newProperties, $this->properties);
		$this->properties = array_merge($this->properties, $result);
		$this->newProperties = array();
		return $this;
	}
	
	
	function delete() {
		$newProperties = $this->getDriver()->delete($this->getClass(), $this->newProperties, $this->properties);
		$this->properties = array();
		$this->newProperties = $newProperties;
		return $this;
	}


	private $pQL;


	final protected function getPQL() {
		return $this->pQL;
	}
	
	
	final protected function getDriver() { 
		return $this->getPQL()->driver();
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