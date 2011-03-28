<?php
/**
 * Базовый класс объекта pQL
 * 
 * @author Ti
 * @package pQL
 */
abstract class pQL_Object implements ArrayAccess {
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
		$newProperties = $this->getDriver()->deleteByModel($this->getModel(), $this->newProperties, $this->properties);
		$this->properties = array();
		$this->newProperties = $newProperties;
		return $this;
	}


	private $pQL;


	final protected function getPQL() {
		return $this->pQL;
	}
	

	/**
	 * @return pQL_Driver
	 */
	final protected function getDriver() { 
		return $this->getPQL()->driver();
	}


	private $properties = array();
	private $newProperties = array();
	function set($property, $value) {
		$this->newProperties[$property] = $value;
		return $this;
	}


	final function loadProperties() {
		$this->properties = $this->getDriver()->getProperties($this);
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
				if (is_null($result)) return null;
				$result = $this->getDriver()->getObjectProperty($this, $property, $result);
			} 
			// иначе нужно его загрузить
			else {
				$result = $this->getDriver()->loadObjectProperty($this, $property);
			}
			$found = is_object($result);
			if ($found) {
				// object in properties
				if (isset($this->newProperties[$property])) $this->newProperties[$property] = $result;
				else $this->properties[$property] = $result;
			}
		}

		if (!$found) {
			$result = $this->loadProperty($property);
		}

		return $result;
	}
	
	
	function loadProperty($property) {
		if (!$this->properties) return null;

		$model = $this->getModel();
		if (!$this->getDriver()->propertyExists($model, $property)) throw new pQL_Object_Exception_PropertyNotExists("Unable lazy load property '".$this->getModel().".$property': field not exists");
		
		$table = $this->getDriver()->modelToTable($model);
		$pk = $this->getDriver()->getTablePrimaryKey($table);
		if (!$pk) throw new pQL_Object_Exception_PropertyNotExists("Unable lazy load property '".$this->getModel().".$property': primary key not found");
		
		$query = $this->pQL->creater()->{$this->getModel()};
		foreach($pk as $pkField) {
			$pkProperty = $this->getDriver()->fieldToProperty($pkField);
			if (!isset($this->properties[$pkProperty])) throw new pQL_Object_Exception_PropertyNotExists("Unable lazy load property '".$this->getModel().".$property': primary key property '".$this->getModel().".$pkProperty' not set in object");
			$id = $this->properties[$pkProperty];
			$query->$pkProperty->in($id);
		}
		foreach($query->$property as $result) {
			$this->properties[$property] = $result;
			return $result;
		}
		throw new pQL_Object_Exception_PropertyNotExists("Unable lazy load property '".$this->getModel().".$property' record not found");
	}


	private function isPropertyObject($property) {
		return $this->getDriver()->isPropertyObject($this->getModel(), $property);
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


	final function offsetGet($property) {
		return $this->get($property);
	}


	final function offsetSet($property, $value) {
		return $this->set($property, $value);
	}


	final function offsetExists($property) {
		if (array_key_exists($property, $this->properties)) return true;
		if (array_key_exists($property, $this->newProperties)) return true;
		return $this->getDriver()->propertyExists($this->getModel(), $property);
	}


	final function offsetUnset($property) {
		throw new RuntimeException('Method '.__METHOD__.' not allow');
		return $this;
	}


	final function needSave($property = null) {
		if (is_null($property)) return $this->newProperties || !$this->properties;
		return array_key_exists($property, $this->newProperties);
	}
}