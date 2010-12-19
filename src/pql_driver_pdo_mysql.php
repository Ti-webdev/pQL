<?php
/**
 * MySQL PDO драйвер для pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Driver_PDO_MySQL extends pQL_Driver_PDO {
	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}


	function getToStringField($class) {
		$table = $this->modelToTable($class);
		$result = null;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isString = preg_match('#^(text|char|varchar)#', $column['Type']);
			if ($isString or is_nulL($result)) {
				$result = $column['Field'];
				if ($isString) break;
			}
		}
		if ($result) return $this->fieldToProperty($result);
		return $result;
	}


	protected function getTablePrimaryKey($table) {
		$result = null;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isPK = 'PRI' == $column['Key'];
			if ($isPK) { //  or is_nulL($result)
				$result = $column['Field'];
				if ($isPK) break;
			}
		}
		if ($result) return $this->getTranslator()->addDbQuotes($result);
		return $result;
	}


	protected function getTableFields($table) {
		$q = $this->getDbh()->query("SHOW COLUMNS FROM $table");
		$q->setFetchMode(PDO::FETCH_COLUMN, 0);
		$result = array();
		foreach ($q as $field) $result[] = $this->getTranslator()->addDbQuotes($field);
		return $result;
	}


	function getCount(pQL_Query_Mediator $queryMediator) {
		return $queryMediator->getQueryHandler($this)->rowCount();
	}


	function getIsNullExpr($partSql) {
		return "$partSql IS NULL";
	}
	

	function getNotNullExpr($expr) {
		return "$expr IS NOT NULL";
	}
	
	
	protected function getTables() {
		$result = array();
		$sth = $this->getDbh()->query("SHOW TABLES");
		$sth->setFetchMode(PDO::FETCH_COLUMN, 0);
		foreach($sth as $table) $result[] = $this->getTranslator()->addDbQuotes($table);
		return $result;
	}
	
	
	protected function getForeignKeys($table) {
		return array();
	}
}