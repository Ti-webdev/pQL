<?php
class pQL_Db_Field {
	private $name;
	private $isPk;


	function __construct($name, $isPk) {
		$this->name = $name;
		$this->isPk = $isPk;
	}
	
	
	function getName() {
		return $this->name;
	}


	function isPk() {
		return $this->isPk;
	}
}