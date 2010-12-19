<?php
/**
 * SQLite PDO драйвер для pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Driver_PDO_SQLite extends pQL_Driver_PDO {
	function getToStringField($class) {
		$table = $this->getTranslator()->modelToTable($class);
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


	protected function getTableFields($table) {
		$q = $this->getDbh()->query("PRAGMA table_info($table)");
		$q->setFetchMode(PDO::FETCH_COLUMN, 1);
		$result = array();
		foreach ($q as $field) $result[] = $this->getTranslator()->addDbQuotes($field);
		return $result;
	}


	function getCount(pQL_Query_Mediator $mediator) {
		$mediator->setup($this);
		$sql = 'SELECT COUNT(*)'.$mediator->getBuilder()->getSQLSuffix($this);
		return $this->getDbh()->query($sql)->fetchColumn(0);
	}
	
	
	protected function getTables() {
		$sth = $this->getDbh()->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'");
		$sth->setFetchMode(PDO::FETCH_COLUMN, 0);
		$result = array();
		foreach($sth as $table) $result[] = $this->getTranslator()->addDbQuotes($table);
		return $result;
	}
	
	
	protected function getForeignKeys($table) {
		$sth = $this->getDbh()->query("PRAGMA foreign_key_list($table)");
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$result = array();
		$tr = $this->getTranslator();
		foreach($sth as $key) {
			$i = intval($key['id']);
			if (!isset($result[$i])) {
				$result[$i] = array(
					'table'=>$tr->addDbQuotes($key['table']),
					'from'=>array(),
					'to'=>array(),
				);
			}
			$result[$i]['from'][] = $tr->addDbQuotes($key['from']);
			$result[$i]['to'][] = $tr->addDbQuotes($key['to']);
		}
		return $result;
	}
}