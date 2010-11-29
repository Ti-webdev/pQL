<?php
/**
 * @author Ti
 * @package pQL
 */
class pQL_Driver_MySQL_QueryException extends pQL_Exception {
	private $query;


	function __construct($message, $code, $query) {
		parent::__construct($message, $code);
		$this->query = $query;
	}


	function getQuery() {
		return $this->query;
	}
}