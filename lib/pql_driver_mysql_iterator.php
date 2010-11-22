<?php
class pQL_Driver_MySQL_Iterator implements SeekableIterator {
	private $query;
	private $current;
	private $key;

	function __construct($query) {
		if (!is_resource($query)) throw new InvalidArgumentException('Invalid resource');
		$this->query = $query;
	}


	function current() {
		return $this->current;
	}


	function next() {
		$this->current = mysql_fetch_row($this->query);
		$this->key++;
	}


	function key() {
		return $this->key;
	}


	function valid() {
		return false !== $this->current;
	}


	function rewind() {
		$this->key = -1;
		$this->next();
	}


	function seek($position) {
		mysql_data_seek($this->query, $position);
	}
}