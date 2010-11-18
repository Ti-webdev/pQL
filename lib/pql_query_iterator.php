<?php
final class pQL_Query_Iterator implements Iterator {
	private $iterator;
	function setIterator(Iterator $iterator) {
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


	function current() {
		$current = $this->iterator->current();
		return $current[$this->valueIndex];
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