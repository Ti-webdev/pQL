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
		$table = $this->getTranslator()->classToTable($class);
		$result = null;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isString = preg_match('#^(text|char|varchar)#', $column['Type']);
			if ($isString or is_nulL($result)) {
				$result = $column['Field'];
				if ($isString) break;
			}
		}
		if ($result) return $this->getTranslator()->fieldToProperty($result);
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
		return $q;
	}
}