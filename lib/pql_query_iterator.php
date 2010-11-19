<?php
final class pQL_Query_Iterator implements Iterator {
	private $driver;
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
	}
	
	
	private $iterator;
	function setSelectIterator(Iterator $iterator) {
		$this->iterator = $iterator;
	}
	
	
	private $keyIndex;
	function setKeyIndex($index) {
		$this->keyIndex = $index;
	}
	
	
	private $valueIndex;
	function setValueIndex($index) {
		$this->valueIndex = $index;
	}
	
	
	private $valueClass;
	private $valueClassIndexes;
	function setValueClass($className, $keys) {
		$this->valueClass = $className;
		$this->valueClassIndexes = $keys;
	}


	function current() {
		$current = $this->iterator->current();
		if (is_null($this->valueIndex)) {
			$properties = array();
			foreach($this->valueClassIndexes as $i=>$name) $properties[$name] = $current[$i];
			return $this->driver->getObject($this->valueClass, $properties);
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