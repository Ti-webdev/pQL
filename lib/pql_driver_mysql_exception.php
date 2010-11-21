<?php
class pQL_Driver_MySQL_Exception extends pQL_Exception {
	private $query;


	function __construct($message, $code, $query) {
		parent::__construct($message, $code);
		$this->query = $query;
	}


	function getQuery() {
		return $query;
	}
}