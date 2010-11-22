<?php
final class pQL_Select_Builder_Field {
	private $table;
	private $name;


	function __construct(pQL_Select_Builder_Table $table, $name) {
		$this->table = $table;
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}


	function getTable() {
		return $this->table;
	}
}