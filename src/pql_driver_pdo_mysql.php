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



	protected function getTableFields($table) {
		$result = array();
		$hasPrimary = false;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isPrimary = 'PRI' == $column['Key'];
			$result[] = new pQL_Db_Field($column['Field'], $isPrimary);
			$hasPrimary = $hasPrimary || $isPrimary;
		}
		if ($result && !$hasPrimary) {
			// если не pk, - делаем первую клонку pk
			$firstField = array_shift($result);
			array_unshift($result, new pQL_Db_Field($firstField->getName(), true));
		}
		return $result;
	}


	function getCount(pQL_Query_Mediator $queryMediator) {
		return $queryMediator->getQueryHandler($this)->rowCount();
	}


	function getIsNullExpr() {
		return 'IS NULL';
	}
	

	function getNotNullExpr() {
		return 'IS NOT NULL';
	}
	
	
	protected function getTables() {
		$result = array();
		$sth = $this->getDbh()->query("SHOW TABLES");
		$sth->setFetchMode(PDO::FETCH_COLUMN, 0);
		foreach($sth as $table) $result[] = $this->getTranslator()->addDbQuotes($table);
		return $result;
	}


	private function getDbName() {
		$cache = $this->cache('dbname');
		if ($cache->exists()) {
			$result = $cache->get();
		}
		else {
			$query = $this->getDbh()->query('SELECT DATABASE()');
			$result = $query->fetchColumn(0);
			$cache->set($result);
		}
		return $result;
	}
	
	
	const ERR_DENIED = 1142;
	
	
	private function getAllForeignKeys() {
		$cache = $this->cache('fk');
		if ($cache->exists()) return $cache->get();
		$dbName = $this->getDbh()->quote($this->getDbName());
		try {
			$Q = $this->getDbh()->query("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE CONSTRAINT_SCHEMA = $dbName AND REFERENCED_TABLE_NAME IS NOT NULL
				ORDER BY TABLE_NAME, REFERENCED_TABLE_NAME, POSITION_IN_UNIQUE_CONSTRAINT", PDO::FETCH_ASSOC);
		}
		catch (PDOException $e) {
			if (self::ERR_DENIED != $e->getCode()) throw $e;
			$cache->set(null);
			return null; 
		}
		$result = array();
		$tr = $this->getTranslator();
		foreach($Q as $R) {
			if (!isset($result[$R['TABLE_NAME']][$R['CONSTRAINT_NAME']])) {
				$result[$R['TABLE_NAME']][$R['CONSTRAINT_NAME']] = array(
					'table'=>$tr->addDbQuotes($R['REFERENCED_TABLE_NAME']),
					'from'=>array(),
					'to'=>array(),
				);
			}
			
			$result[$R['TABLE_NAME']][$R['CONSTRAINT_NAME']]['from'][] = $tr->addDbQuotes($R['COLUMN_NAME']);
			$result[$R['TABLE_NAME']][$R['CONSTRAINT_NAME']]['to'][] = $tr->addDbQuotes($R['REFERENCED_COLUMN_NAME']);
		}
		$cache->set($result);
		return $result;
	}


	protected function getForeignKeys($table) {
		$all = $this->getAllForeignKeys();
		$table = $this->getTranslator()->removeDbQuotes($table);
		if (isset($all[$table])) return array_values($all[$table]);
		return array();
	}
}