<?php
/**
 * Преобразователь имен PHP <> DB
 * @author Ti
 * @package pQL
 */
class pQL_Translator {
	private $tablePrefix = '';
	
	
	function getTablePrefix() {
		return $this->tablePrefix;
	}
	
	
	function setTablePrefix($newPrefix) {
		$this->tablePrefix = $newPrefix;
	}
	
	
	function classToTable($className, $quoted = true) {
		$result = $this->tablePrefix.$className;
		if ($quoted) $this->tableQuote.$result.$this->tableQuote;
		return $result;
	}
	
	
	function propertyToField($property) {
		return $this->tableQuote.$property.$this->tableQuote;;
	}
	
	
	private $tableQuote = '';
	function setTableQuote($newQuote)  {
		$this->tableQuote = $newQuote;
	}
	
	
	function getObject($class) {
		$result = new pQL_Object_Simple;
		$result->setClass($class);
		return $result;
	}
}