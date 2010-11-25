<?php
final class pQL_Query_Builder {
	private $registeredTables = array();
	/**
	 * Регистрирует талбицу (но не добавлет в выбоку!)
	 * @param pQL_Query_Builder_Table $tableName
	 * @return pQL_Query_Builder_Table
	 */
	function registerTable($tableName) {
		if (isset($this->registeredTables[$tableName])) {
			$bTable = $this->registeredTables[$tableName];
		}
		else {
			$bTable = new pQL_Query_Builder_Table($tableName);
			$this->registeredTables[$tableName] = $bTable;
		}
		return $bTable;
	}


	private $registeredFields = array();


	/**
	 * Регистрирует поле (но не добавлет в выбоку!)
	 * 
	 * @param pQL_Query_Builder_Table $rTable
	 * @param string $fieldName
	 * @return pQL_Query_Builder_Field
	 */
	function registerField(pQL_Query_Builder_Table $bTable, $fieldName) {
		if (isset($this->registeredFields[$bTable->getName()][$fieldName])) {
			$bField = $this->registeredFields[$bTable->getName()][$fieldName];
		}
		else {
			$bField = $this->registeredFields[$bTable->getName()][$fieldName] = new pQL_Query_Builder_Field($bTable, $fieldName);
		}
		return $bField;
	}


	private $tables = array();


	/**
	 * Возращает номер таблицы в запросе
	 * @param pQL_Query_Builder_Table $table
	 * @return int
	 */
	private function getTableNum(pQL_Query_Builder_Table $bTable) {
		$index = array_search($bTable, $this->tables);
		if (false === $index) $index = array_push($this->tables, $bTable) - 1;
		return $index;
	}


	private $fields = array();
	/**
	 * Возращает номер поля в запросе
	 * @param pQL_Query_Builder_Table $bTable
	 * @param pQL_Query_Builder_Field $bField
	 * @return int
	 */
	function getFieldNum(pQL_Query_Builder_Field $bField) {
		$index = array_search($bField, $this->fields);
		if (false === $index) $index = array_push($this->fields, $bField) - 1;
		return $index;
	}


	private function getSQLFields() {
		$result = '';
		foreach($this->fields as $fieldNum=>$rField) {
			if ($fieldNum) $result .= ', ';
			$result .= $this->getTableAlias($rField->getTable());
			$result .= '.';
			$result .= $rField->getName();
			#$result .= ' AS ';
			#$result .= $this->getFieldAlias($rField);
		}
		return $result;
	}
	
	
	private function getFieldAlias(pQL_Query_Builder_Field $bField) {
		return 'f'.$this->getFieldNum($bField);
	}
	
	
	function getTableAlias(pQL_Query_Builder_Table $bTable) {
		return 't'.$this->getTableNum($bTable);
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


	private $where = '';
	function addWhere($expression) {
		if ($this->where) $this->where .= ' AND ';
		else $this->where .= ' WHERE ';

		$this->where .= $expression;

		return $this;
	}
	
	
	private $limit;
	function setLimit($limit) {
		$this->limit = (int) $limit;
	}


	/**
	 * Возращает часть запроса, начиная с FROM
	 */
	function getSQLSuffix() {
		$result = $this->getSQLFrom().$this->where;
		if ($this->limit) $result .= " LIMIT $this->limit";
		return $result;
	}


	function getSQL() {
		$sql = 'SELECT '.$this->getSQLFields().$this->getSQLSuffix();
		return $sql;
	}
}