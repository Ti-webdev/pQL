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
	
	
	function getTableName($className, $quoted = true) {
		$result = $this->tablePrefix.$className;
		if ($quoted) $this->tableQuote.$result.$this->tableQuote;
		return $result;
	}
	
	
	private $tableQuote = '';
	function setTableQuote($newQuote)  {
		$this->tableQuote = $newQuote;
	}
}