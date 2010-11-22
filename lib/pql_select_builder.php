<?php
final class pQL_Select_Builder {
	private $driver;
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
	}
	
	
	private $registeredTables = array();
	/**
	 * Регистрирует талбицу (но не добавлет в выбоку!)
	 * @param pQL_Select_Builder_Table $tableName
	 * @return pQL_Select_Builder_Table
	 */
	function registerTable($tableName) {
		if (isset($this->registeredTables[$tableName])) {
			$rTable = $this->registeredTables[$tableName];
		}
		else {
			$rTable = new pQL_Select_Builder_Table($tableName);
			$this->registeredTables[$tableName] = $rTable;
		}
		return $rTable;
	}


	private $equals = array();
	function setEquals(pQL_Select_Builder_Field $rField, $value) {
		$this->equals[$this->getFieldNum($rField)][] = $value;
		return $this;
	}


	private $null = array();
	function setIsNull(pQL_Select_Builder_Field $rField) {
		$this->null[] = $this->getFieldAlias($rField);
		return $this;
	}


	private $registeredFields = array();


	/**
	 * Регистрирует поле (но не добавлет в выбоку!)
	 * 
	 * @param pQL_Select_Builder_Table $rTable
	 * @param string $fieldName
	 * @return pQL_Select_Builder_Field
	 */
	function registerField(pQL_Select_Builder_Table $rTable, $fieldName) {
		if (isset($this->registeredFields[$rTable->getName()][$fieldName])) {
			$rField = $this->registeredFields[$rTable->getName()][$fieldName];
		}
		else {
			$rField = $this->registeredFields[$rTable->getName()][$fieldName] = new pQL_Select_Builder_Field($rTable, $fieldName);
		}
		return $rField;
	}


	private $tables = array();


	/**
	 * Возращает номер таблицы в запросе
	 * @param pQL_Select_Builder_Table $table
	 * @return int
	 */
	private function getTableNum(pQL_Select_Builder_Table $rTable) {
		$index = array_search($rTable, $this->tables);
		if (false === $index) $index = array_push($this->tables, $rTable) - 1;
		return $index;
	}


	private $fields = array();
	/**
	 * Возращает номер поля в запросе
	 * @param pQL_Select_Builder_Table $rTable
	 * @param pQL_Select_Builder_Field $rField
	 * @return int
	 */
	function getFieldNum(pQL_Select_Builder_Field $rField) {
		$index = array_search($rField, $this->fields);
		if (false === $index) $index = array_push($this->fields, $rField) - 1;
		return $index;
	}


	private function getSQLFields() {
		$result = '';
		foreach($this->fields as $fieldNum=>$rField) {
			if ($fieldNum) $result .= ', ';
			$result .= $this->getTableAlias($rField->getTable());
			$result .= '.';
			$result .= $rField->getName();
			$result .= ' AS ';
			$result .= $this->getFieldAlias($rField);
		}
		return $result;
	}
	
	
	private function getFieldAlias(pQL_Select_Builder_Field $rField) {
		return 'f'.$this->getFieldNum($rField);
	}
	
	
	private function getTableAlias(pQL_Select_Builder_Table $rTable) {
		return 't'.$this->getTableNum($rTable);
	}


	private function getSQLFrom() {
		// if empty from - add first table!
		if (empty($this->tables)) $this->getTableNum(reset($this->registeredTables));
		
		$result = '';
		foreach($this->tables as $tableNum=>$rTable) {
			if ($tableNum) $result .= ', ';
			$result .= $rTable->getName();
			$result .= ' AS ';
			$result .= $this->getTableAlias($rTable);
		}
		return ' FROM '.$result;
	}
	
	
	private function getWherePrefix(&$isFirst) {
		if ($isFirst) {
			$isFirst = false;
			return ' WHERE ';
		}
		return ' AND ';
	}


	private function getSQLWhere() {
		$result = '';
		$isFirst = true;
		foreach($this->equals as $num=>$vals) {
			$rField = $this->fields[$num];
			$result .= $this->getWherePrefix($isFirst);
			$result .= $this->getTableAlias($rField->getTable()).'.'.$rField->getName();
			if (1 < count($vals)) {
				$result .= ' IN(';
				foreach($vals as $i=>$val) {
					if ($i) $result .= ', ';
					$result .= $this->driver->getParam($rField, $val);
				}
				$result .= ')';
			}
			else {
				$result .= ' = '.$this->driver->getParam($rField, reset($vals));
			}
		}
		foreach($this->null as $field) {
			$result .= $this->getWherePrefix($isFirst);
			$result .= $this->driver->getIsNull($field);
		}
		return $result;
	}


	/**
	 * Возращает часть запроса, начиная с FROM
	 */
	function getSQLSuffix() {
		return $this->getSQLFrom().$this->getSQLWhere();
	}


	function getSQL() {
		$sql = 'SELECT '.$this->getSQLFields().$this->getSQLSuffix();
		return $sql;
	}
}