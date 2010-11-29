<?php
/**
 * SQLite PDO драйвер для pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Driver_PDO_SQLite extends pQL_Driver_PDO {
	function getToStringField($class) {
		$table = $this->getTranslator()->classToTable($class);
		$result = null;
		foreach($this->getDbh()->query("PRAGMA table_info($table)", PDO::FETCH_ASSOC) as $column) {
			$isString = preg_match('#^(text|char|varchar)#i', $column['type']);
			if ($isString or is_nulL($result)) {
				$result = $column['name'];
				if ($isString) break;
			}
		}
		if ($result) return $this->getTranslator()->fieldToProperty($result);
		return $result;
	}


	protected function getTablePrimaryKey($table) {
		$result = null;
		foreach($this->getDbh()->query("PRAGMA table_info($table)", PDO::FETCH_ASSOC) as $column) {
			$isPK = (bool) $column['pk'];
			if ($isPK) { // or is_nulL($result)
				$result = $column['name'];
				if ($isPK) break;
			}
		}
		return $result;
	}


	function getTableFields($table) {
		$q = $this->getDbh()->query("PRAGMA table_info($table)");
		$q->setFetchMode(PDO::FETCH_COLUMN, 1);
		return $q;
	}


	function getCount(pQL_Query_Mediator $mediator) {
		$mediator->setup($this);
		$sql = 'SELECT COUNT(*)'.$mediator->getBuilder()->getSQLSuffix($this);
		return $this->getDbh()->query($sql)->fetchColumn(0);
	}
}