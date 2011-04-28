<?php
/**
 * Итератор pQL запроса
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Iterator implements Iterator {
	private $driver;
	
	
	/**
	 * @var pQL_Query_Iterator_Values_Interface
	 */
	private $valuesConverter;
	
	
	function __construct(pQL_Driver $driver, pQL_Query_Iterator_Values_Interface $values) {
		$this->driver = $driver;
		$this->valuesConverter = $values;
	}
	
	
	private $iterator;
	function setSelectIterator(Iterator $iterator) {
		$this->iterator = $iterator;
	}
	
	
	/**
	 * Номер поля выборки, используемое в качестве ключей итератора
	 * @var int
	 */
	private $keysIndex;
	function setKeyIndex($index) {
		$this->keysIndex = $index;
	}


	private $bindedObjectClasses = array();
	function bindValueObject(&$var, pQL_Query_Iterator_Values_Object $valuesObject) {
		$this->bindedObjectClasses[] = array(&$var, $valuesObject);
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
			$bind[0] = $bind[1]->getValue($current);
		}
	}
	
	
	function current() {
		$current = $this->iterator->current();
		$this->setBindValues($current);
		return $this->valuesConverter->getValue($current);
	}


	function next() {
		return $this->iterator->next();
	}


	function key() {
		if (is_null($this->keysIndex)) return $this->iterator->key();
		$current = $this->iterator->current();
		return $current[$this->keysIndex];
	}


	function valid() {
		return $this->iterator->valid();
	}


	function rewind() {
		return $this->iterator->rewind();
	}
}