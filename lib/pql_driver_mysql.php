<?php
/**
 * MySQL драйвер для pQL
 * @author Ti
 * @package pQL
 */
final class pQL_Driver_MySQL extends pQL_Driver {
	private $db;
	function __construct($db) {
		if (!is_resource($db)) throw new InvalidArgumentException('Invalid db connection');
		$this->db = $db;
	}
	
	
	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}
	
	
	private function query($query) {
		$result = mysql_query($query, $this->db);
		if (!$result) throw new pQL_Driver_MySQL_Exception(mysql_error(), mysql_errno(), $query);
		return $result;
	}


	function getToStringField($class) {
		$table = $this->getTranslator()->classToTable($class);
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


	protected function getTablePrimaryKey($table) {
		$result = null;
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_assoc($Q)) {
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
		$result = array();
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_row($Q)) $result[] = reset($column);
		return $result;
	}
	
	
	private function quote($value) {
		if (is_null($value)) return 'NULL';
		return '"'.mysql_real_escape_string($value, $this->db).'"';
	}

	
	function findByPk($class, $value) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$qValue = $this->quote($value);
		$Q = $this->query("SELECT * FROM $table WHERE $pk = $qValue");
		$R = mysql_fetch_assoc($Q);
		$properties = array();
		foreach($R as $field=>$value) $properties[$tr->fieldToProperty($field)] = $value;
		return $this->getObject($class, $properties);
	}


	protected function updateByPk($table, $fields, $values, $pkValue) {
		$pk = $this->getTablePrimaryKey($table);
		$sql = "UPDATE $table SET ";
		foreach($fields as $i=>$field) {
			if ($i) $sql .= ', ';
			$sql .= "$field = ".$this->quote($values[$i]);
		}
		$qPkValue = $this->quote($pkValue);
		$sql .= " WHERE $pk = $qPkValue LIMIT 1";
		$this->query($sql);
	}


	protected function insert($table, $fields, $values) {
		if (!$fields) {
			$fields = array($this->getTablePrimaryKey($table));
			$values = array(null);
		}

		$sql = "INSERT INTO $table(".implode(',', $fields).") VALUES(";
		foreach($values as $i=>$value) {
			if ($i) $sql .= ', ';
			$sql .= $this->quote($value);
		} 
		$sql .= ")";
		
		$this->query($sql);
			
		return mysql_insert_id($this->db);
	}


	function getSelectHandle(pQL_Select_Builder $builder) {
		return $this->query($builder->getSQL());
	}


	/**
	 * пересоздавать запрос не надо - будем перематывать
	 * @see pQL_Driver::needRecreateSelectHandle()
	 * @see pQL_Driver_MySQL::getSelectIterator()
	 */
	protected function needRecreateSelectHandle(pQL_Query_Mediator $queryMediator) {
		return false;
	}


	protected function getSelectIterator(pQL_Query_Mediator $queryMediator) {
		$handle = $queryMediator->getSelectHandle($this);

		// перематываем запрос на начало
		if ($queryMediator->getIsDone()) mysql_data_seek($handle, 0);
		else $queryMediator->setIsDone();

		return new pQL_Driver_MySQL_Iterator($handle);
	}


	function getCount(pQL_Query_Mediator $mediator) {
		return mysql_num_rows($mediator->getSelectHandle($this));
	}


	function getIsNull($partSql) {
		return "ISNULL($partSql)";
	}


	final function getParam(pQL_Select_Builder_Field $field, $val) {
		return $this->quote($val);
	}
}