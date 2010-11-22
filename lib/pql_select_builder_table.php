<?php
final class pQL_Select_Builder_Table {
	private $name;


	function __construct($name) {
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}
}