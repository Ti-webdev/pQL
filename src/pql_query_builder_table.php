<?php
final class pQL_Query_Builder_Table {
	private $name;


	function __construct($name) {
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}
}