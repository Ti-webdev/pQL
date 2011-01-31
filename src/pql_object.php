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
	
	
	abstract function getModel();


	protected function getToStringField() {
		return $this->getDriver()->getToStringField($this->getModel());
	}


	function save() {
		$result = $this->getDriver()->save($this->getModel(), $this->newProperties, $this->properties);
		$this->properties = array_merge($this->properties, $result);
		$this->newProperties = array();
		return $this;
	}
	
	
	function delete() {
		$newProperties = $this->getDriver()->delete($this->getModel(), $this->newProperties, $this->properties);
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
		$found = false;
		$result = null;

		// ищем в новых свойствах
		if (array_key_exists($property, $this->newProperties)) {
			$result = $this->newProperties[$property];
			$found = true;
		}
		// и в текущих
		elseif (array_key_exists($property, $this->properties)) {
			$result = $this->properties[$property];
			$found = true;
		}

		// если это связанный объект - получаем его
		if (!is_object($result) and $this->isPropertyObject($property)) {
			// если найдено свойство, значит id определен
			if ($found) {
				$result = $this->getDriver()->getObjectProperty($this, $property, $result);
			} 
			// иначе нужно его загрузить
			else {
				$result = $this->getDriver()->loadObjectProperty($this, $property);
			}
			$found = is_object($result);
		}


		if (!$found) throw new pQL_Object_Exception_PropertyNotExists("'".$this->getModel().".$property' not found");

		return $result;
	}


	private function isPropertyObject($property) {
		return $this->getDriver()->isObjectProperty($this->getModel(), $property);
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