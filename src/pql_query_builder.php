<?php
/**
 * Построитель запросов
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Builder {
	private $registeredTables = array();


	/**
	 * Регистрирует талбицу (но не добавлет в выбоку!)
	 * 
	 * @param pQL_Query_Builder_Table $tableName
	 * @return pQL_Query_Builder_Table зарегистрированна таблица
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
	 * @return pQL_Query_Builder_Field зарегистрированное поле
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
	 * 
	 * @param pQL_Query_Builder_Table $table
	 * @return int
	 */
	private function getTableNum(pQL_Query_Builder_Table $bTable) {
		$index = array_search($bTable, $this->tables);
		if (false === $index) $index = array_push($this->tables, $bTable) - 1;
		return $index;
	}


	/**
	 * Добавляет таблицу в запрос
	 */
	function addTable(pQL_Query_Builder_Table $bTable) {
		$this->getTableNum($bTable);
	}


	/**
	 * Проверяет есть ли таблица в запросе
	 * 
	 * @param pQL_Query_Builder_Table $bTable
	 * @return bool
	 */
	function tableExists(pQL_Query_Builder_Table $bTable) {
		return false !== array_search($bTable, $this->tables);
	}
	
	
	/**
	 * Возращает список талбиц которые будут добавлены в FROM или JOIN выражения
	 * 
	 * @return array
	 */
	function getFromTables() {
		return $this->tables;
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
			
			// В именнованных алиасах пока нет необходимости
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


	/**
	 * @return string выражение FROM включая все JOIN
	 */
	private function getSQLFrom() {
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


	private $limit = 0;
	function setLimit($limit) {
		$this->limit = (int) $limit;
	}
	
	
	function getLimit() {
		return $this->limit;
	}
	

	private $offset = 0;
	function setOffset($offset) {
		$this->offset = (int) $offset;
	}
	
	
	private $orderBy = '';
	function addOrder($expr) {
		$this->orderBy .= $this->orderBy ? ', ' : ' ORDER BY ';
		$this->orderBy .= $expr;
	}
	
	
	private function getLimitExpr(pQL_Driver $driver) {
		return rtrim(' '.$driver->getLimitExpr($this->offset, $this->limit));
	}


	/**
	 * Возращает часть запроса, начиная с FROM до ORDER BY
	 */
	function getSQLSuffix(pQL_Driver $driver) {
		return $this->getSQLFrom().$this->where.$this->getLimitExpr($driver);
	}


	function getSQL(pQL_Driver $driver) {
		$sql = 'SELECT '.$this->getSQLFields().$this->getSQLSuffix($driver).$this->orderBy;
		return $sql;
	}
}