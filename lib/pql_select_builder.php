<?php
final class pQL_Select_Builder {
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
		return $result;
	}


	/**
	 * Возращает часть запроса, начиная с FROM
	 */
	function getSQLSuffix() {
		return 'FROM '.$this->getSQLFrom();
	}


	function getSQL() {
		$sql = 'SELECT '.$this->getSQLFields().' '.$this->getSQLSuffix();
		return $sql;
	}
}


final class pQL_Select_Builder_Table {
	private $name;


	function __construct($name) {
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}
}


final class pQL_Select_Builder_Field {
	private $table;
	private $name;


	function __construct(pQL_Select_Builder_Table $table, $name) {
		$this->table = $table;
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}


	function getTable() {
		return $this->table;
	}
}