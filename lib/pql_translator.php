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
	
	
	function classToTable($className, $addQuotes = true) {
		$result = $this->tablePrefix.$className;
		if ($addQuotes) $this->addDbQuotes($result);
		return $result;
	}
	
	
	function propertyToField($property) {
		return $this->addDbQuotes($property);
	}


	function fieldToProperty($field) {
		return $this->removeDbQuotes($field);
	}


	function addDbQuotes($field) {
		return $this->dbQuote.$field.$this->dbQuote;
	}
	
	
	private function removeDbQuotes($property) {
		return '' === $this->dbQuote ? $property : trim($property, $this->dbQuote);
	}

	
	private $dbQuote = '';
	function setDbQuote($newQuote)  {
		$this->dbQuote = $newQuote;
	}
	
	
	function getObject($class) {
		$result = new pQL_Object_Simple;
		$result->setClass($class);
		return $result;
	}
}