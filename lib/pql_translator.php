<?php
/**
 * Преобразователь имен PHP <> DB
 * @author Ti
 * @package pQL
 */
final class pQL_Translator {
	private $tablePrefix = '';
	
	
	private $tableCoding;
	private function getTableCoding() {
		if (!$this->tableCoding) $this->tableCoding = new pQL_Coding_Null;
		return $this->tableCoding;
	}
	
	
	function setTableCoding(pQL_Coding_Interface $coding) {
		$this->tableCoding = $coding;
	}
	
	
	private $fieldCoding;
	private function getFieldCoding() {
		if (!$this->fieldCoding) $this->fieldCoding = new pQL_Coding_Null;
		return $this->fieldCoding;
	} 


	function setFieldCoding(pQL_Coding_Interface $coding) {
		$this->fieldCoding = $coding;
	}
	
	
	function getTablePrefix() {
		return $this->tablePrefix;
	}
	
	
	function setTablePrefix($newPrefix) {
		$this->tablePrefix = $newPrefix;
	}
	

	function classToTable($className, $addQuotes = true) {
		$result = $this->getTableCoding()->toDb($className);
		$result = preg_replace('#(.)([A-Z])#ue', '"$1"."_".strtolower("$2")', $className);
		$result = $this->tablePrefix.$result;
		if ($addQuotes) $this->addDbQuotes($result);
		return $result;
	}
	
	
	function propertyToField($property) {
		$result = $this->getFieldCoding()->toDb($property);
		$result = $this->addDbQuotes($result);
		return $result;
	}


	function fieldToProperty($field) {
		$result = $this->removeDbQuotes($field);
		$result = $this->getFieldCoding()->fromDb($result);
		return $result;
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
	
	
	function getObject($class, $properties = array()) {
		return new pQL_Object_Simple($properties, $class);
	}
}