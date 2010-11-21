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


	protected function getTableFields($table) {
		$q = $this->getDbh()->query("PRAGMA table_info($table)");
		$q->setFetchMode(PDO::FETCH_COLUMN, 1);
		return $q;
	}


	protected function getSelectQuery(pQL_Select_Builder $builder) {
		return array(parent::getSelectQuery($builder), $builder);
	}


	protected function getSelectIterator($query) {
		return parent::getSelectIterator($query[0]);
	}


	protected function getCountResults($query) {
		$sql = 'SELECT COUNT(*) '.$query[1]->getSQLSuffix();
		return $this->getDbh()->query($sql)->fetchColumn(0);
	}
}