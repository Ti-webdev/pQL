<?php
/**
 * Итератор pQL запроса
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Iterator implements Iterator {
	private $driver;
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
	}
	
	
	private $iterator;
	function setSelectIterator(Iterator $iterator) {
		$this->iterator = $iterator;
	}
	
	
	/**
	 * Номер поля выборки, используемое в качестве ключей итератора
	 * @var int
	 */
	private $keyIndex;
	function setKeyIndex($index) {
		$this->keyIndex = $index;
	}
	

	/**
	 * Номер поля в выборке, используемое в качестве значений итератора
	 * @var int
	 */
	private $valueIndex;
	function setValueIndex($index) {
		$this->valueIndex = $index;
		$this->valueClass = null;
	}


	/**
	 * Класс используемый в качестве значений итератора
	 * @var pQL_Query_Iterator_Class
	 */
	private $valueClass;
	function setValueClass($className, $keys) {
		$this->valueClass = new pQL_Query_Iterator_Class($className, $keys);
		$this->valueIndex = null;
	}
	
	
	private $bindedObjectClasses = array();
	function bindValueObject(&$var, $className, $keys) {
		$this->bindedObjectClasses[] = array(&$var, $className, $keys);
	}
	
	
	private $bindedIndexes = array();
	function bindValueIndex(&$var, $index) {
		$this->bindedIndexes[] = array(&$var, $index);
	}
	
	
	private function setBindValues($current) {
		foreach($this->bindedIndexes as &$bind) {
			$bind[0] = $current[$bind[1]];
		}
		foreach($this->bindedObjectClasses as &$bind) {
			$bind[0] = $this->getObject($current, $bind[1], $bind[2]);
		}
	}
	
	
	private function getObject($current, $className, $inds) {
		$properties = array();
		foreach($inds as $i=>$name) $properties[$name] = $current[$i];
		return $this->driver->getObject($className, $properties);
	}


	function current() {
		$current = $this->iterator->current();
		$this->setBindValues($current);
		if (is_null($this->valueIndex)) {
			return $this->getObject($current, $this->valueClass->getName(), $this->valueClass->getIndexes());
		}
		else {
			return $current[$this->valueIndex];
		}
	}


	function next() {
		return $this->iterator->next();
	}


	function key() {
		if (is_null($this->keyIndex)) return $this->iterator->key();
		$current = $this->iterator->current();
		return $current[$this->keyIndex];
	}


	function valid() {
		return $this->iterator->valid();
	}


	function rewind() {
		return $this->iterator->rewind();
	}
}