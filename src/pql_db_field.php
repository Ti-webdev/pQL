<?php
class pQL_Db_Field {
	private $name;
	private $isPrimaryKey;


	function __construct($name, $isPrimaryKey) {
		$this->name = $name;
		$this->isPrimaryKey = $isPrimaryKey;
	}


	function getName() {
		return $this->name;
	}


	function isPrimaryKey() {
		return $this->isPrimaryKey;
	}
}