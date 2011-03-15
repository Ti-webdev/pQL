<?php
/**
 * MySQL драйвер для pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Driver_MySQL extends pQL_Driver {
	private $db;
	function __construct($db = null) {
		if (!function_exists('mysql_query')) throw new pQL_Exception('MySQL extension not loaded!');
		if (!is_null($db) and !is_resource($db)) throw new InvalidArgumentException('Invalid db connection');
		$this->db = $db;
	}


	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}


	private function query($query) {
		$result = is_null($this->db) ? mysql_query($query) : mysql_query($query, $this->db);
		if (!$result) throw new pQL_Driver_MySQL_Query_Exception(mysql_error(), mysql_errno(), $query);
		return $result;
	}


	function getToStringField($class) {
		$table = $this->getTranslator()->modelToTable($class);
		$result = null;
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_assoc($Q)) {
			$isString = preg_match('#^(text|char|varchar)#', $column['Type']);
			if ($isString or is_nulL($result)) {
				$result = $column['Field'];
				if ($isString) break;
			}
		}
		if ($result) return $this->getTranslator()->fieldToProperty($result);
		return $result;
	}


	protected function getTableFields($table) {
		$result = array();
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_assoc($Q)) {
			$result[] = new pQL_Db_Field($column['Field'], 'PRI' == $column['Key']);
		}
		return $result;
	}
	
	
	private function quote($value) {
		if (is_null($value)) return 'NULL';
		if (is_null($this->db)) return '"'.mysql_real_escape_string($value).'"';
		return '"'.mysql_real_escape_string($value, $this->db).'"';
	}


	protected function update($table, $fields, $values, $where) {
		$sql = "UPDATE $table SET ";

		// SET
		foreach($fields as $i=>$field) {
			if ($i) $sql .= ', ';
			$sql .= "$field = ".$this->quote($values[$i]);
		}

		// WHERE
		$first = true;
		foreach($where as $whereField=>$whereValue) {
			if ($first) {
				$sql .= ' WHERE ';
				$first = false;
			}
			else {
				$sql .= ' AND ';
			}
			$qPkValue = $this->quote($whereValue);
			$sql .= "$whereField = $qPkValue";
		}
		$sql .= ' LIMIT 1';
		$this->query($sql);
	}


	protected function insert($table, $fields, $values) {
		if (!$fields) {
			$fields = $this->getTablePrimaryKey($table);
			$values = array_fill(0, count($fields), null);
		}

		$sql = "INSERT INTO $table(".implode(',', $fields).") VALUES(";
		foreach($values as $i=>$value) {
			if ($i) $sql .= ', ';
			$sql .= $this->quote($value);
		} 
		$sql .= ")";
		
		$this->query($sql);

		if (is_null($this->db)) return mysql_insert_id();
		return mysql_insert_id($this->db);
	}
	
	
	function delete($table, $where) {
		$sql = "DELETE FROM $table WHERE ";
		$first = true;
		foreach($where as $field=>$value) {
			if ($first) $first = false;
			else $sql .= ' AND ';
			$sql .= "$field = ".$this->quote($value);
		}
		$this->query($sql);
	}


	function getQueryHandler(pQL_Query_Builder $builder) {
		return $this->query($builder->getSQL($this));
	}


	function getQueryIterator(pQL_Query_Mediator $mediator) {
		$handle = $mediator->getQueryHandler($this);
		return new pQL_Driver_MySQL_Query_Iterator($handle);
	}


	function getCount(pQL_Query_Mediator $mediator) {
		return count($this->getQueryIterator($mediator));
	}


	function getIsNullExpr() {
		return 'IS NULL';
	}
	

	function getNotNullExpr() {
		return 'IS NOT NULL';
	}



	function getParam($val) {
		return $this->quote($val);
	}


	protected function getTables() {
		$query = $this->query("SHOW TABLES");
		$result = array();
		while($row = mysql_fetch_row($query)) $result[] = $this->getTranslator()->addDbQuotes(reset($row));
		return $result;
	}


	private function getDbName() {
		$cache = $this->cache('dbname');
		if ($cache->exists()) {
			$result = $cache->get();
		}
		else {
			$query = $this->query('SELECT DATABASE()');
			$result = mysql_result($query, 0, 0);
			$cache->set($result);
		}
		return $result;
	}
	

	const ERR_DENIED = 1142;
	
	
	private function getAllForeignKeys() {
		$cache = $this->cache('fk');
		if ($cache->exists()) return $cache->get();
		$dbName = $this->quote($this->getDbName());
		try {
		$Q = $this->query("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE CONSTRAINT_SCHEMA = $dbName AND REFERENCED_TABLE_NAME IS NOT NULL
			ORDER BY TABLE_NAME, REFERENCED_TABLE_NAME, POSITION_IN_UNIQUE_CONSTRAINT");
		}
		catch (pQL_Driver_MySQL_Query_Exception $e) {
			if (self::ERR_DENIED != $e->getCode()) throw $e;
			$cache->set(null);
			return null; 
		}
		$result = array();
		$tr = $this->getTranslator();
		while($R = mysql_fetch_assoc($Q)) {
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


	function exec($sql) {
		$this->query($sql);
		return is_null($this->db) ? mysql_affected_rows() : mysql_affected_rows($this->db);
	}
}