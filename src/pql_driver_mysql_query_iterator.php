<?php
/**
 * Итератор SELECT запроса для MySQL драйвера
 * @author Ti
 * @package pQL
 */
class pQL_Driver_MySQL_Query_Iterator implements SeekableIterator, Countable {
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
		// перематываем на начало
		if (!is_null($this->key)) $this->seek(0);

		$this->key = -1;
		$this->next();
	}


	function seek($position) {
		mysql_data_seek($this->query, $position);
		$this->key = $position;
	}


	function count() {
		return mysql_num_rows($this->query);
	}
}