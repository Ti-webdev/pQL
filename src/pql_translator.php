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
	
	
	function tableToClass($table) {
		$result = $this->removeDbQuotes($table);
		if (0 === strcasecmp(substr($result, 0, strlen($this->tablePrefix)), $this->tablePrefix)) {
			$result = substr($result, strlen($this->tablePrefix));
		}
		return $this->getTableCoding()->fromDb($result);
	}
	 

	function classToTable($className, $addQuotes = true) {
		$result = $this->getTableCoding()->toDb($className);
		$result = $this->tablePrefix.$result;
		if ($addQuotes) $result = $this->addDbQuotes($result);
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


	function addDbQuotes($name) {
		return $this->dbQuote.$name.$this->dbQuote;
	}
	
	
	function removeDbQuotes($name) {
		return '' === $this->dbQuote ? $name : trim($name, $this->dbQuote);
	}

	
	private $dbQuote = '';
	function setDbQuote($newQuote)  {
		$this->dbQuote = $newQuote;
	}
}