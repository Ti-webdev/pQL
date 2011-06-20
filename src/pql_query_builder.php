<?php
/**
 * Построитель запросов
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Builder {
	private $registeredTables = array();
	function __construct() {
		$this->fieldsExp = new pQL_Query_Expr('', ', ');
		$this->setExpr = new pQL_Query_Expr(' SET ', ', ');
		$this->whereExpr = new pQL_Query_Expr(' WHERE ', ' AND ');
		$this->groupByExpr = new pQL_Query_Expr(' GROUP BY ', ', ');
		$this->orderByExpr = new pQL_Query_Expr(' ORDER BY ', ', ');
		$this->havingExpr = new pQL_Query_Expr(' HAVING ', ' AND ');
	}


	function __clone() {
		$this->fieldsExp = clone $this->fieldsExp;
		$this->setExpr = clone $this->setExpr;
		$this->whereExpr = clone $this->whereExpr;
		$this->groupByExpr = clone $this->groupByExpr;
		$this->orderByExpr = clone $this->orderByExpr;
		$this->havingExpr = clone $this->havingExpr;
	}
	
	
	function export(pQL_Query_Builder $queryBuilder) {
		$params = $this->fieldsExp->export($queryBuilder);
		if ($params) $queryBuilder->addField($params);

		$params = $this->setExpr->export($queryBuilder);
		if ($params) $queryBuilder->addSet($params);
		
		$params = $this->whereExpr->export($queryBuilder);
		if ($params) $queryBuilder->addWhere($params);
		
		$params = $this->groupByExpr->export($queryBuilder);
		if ($params) $queryBuilder->addGroup($params);

		$params = $this->orderByExpr->export($queryBuilder);
		if ($params) $queryBuilder->addOrder($params);

		$params = $this->havingExpr->export($queryBuilder);
		if ($params) $queryBuilder->addHaving($params);
	}


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
		$fieldsExpr = $this->fieldsExp->get($this);
		if ($fieldsExpr) {
			if ($result) $result .= ', ';
			$result .= $fieldsExpr;
		}
		return $result;
	}


	private function getFieldAlias(pQL_Query_Builder_Field $bField) {
		return 'f'.$this->getFieldNum($bField);
	}

	
	function getField(pQL_Query_Builder_Field $field, $withAlias = true) {
		if ($withAlias) {
			$result = $this->getTableAlias($field->getTable());
			$result .= '.';
		}
		else {
			$result = '';
		}
		$result .= $field->getName();
		return $result;
	}


	function getTableAlias(pQL_Query_Builder_Table $bTable) {
		return 't'.$this->getTableNum($bTable);
	}


	/**
	 * @return string выражение FROM включая все JOIN
	 */
	private function getSQLFrom($withAlias = true, $from = ' FROM ') {
		$result = '';
		foreach($this->tables as $tableNum=>$rTable) {
			if ($tableNum) $result .= ', ';
			$result .= $rTable->getName();
			if ($withAlias) {
				$result .= ' AS ';
				$result .= $this->getTableAlias($rTable);
			}
		}
		return $from.$result;
	}


	private $fieldsExp;
	private $setExpr;
	private $whereExpr;
	private $groupByExpr;
	private $orderByExpr;
	private $havingExpr;


	function addField($arg) {
		$this->fieldsExp->pushArray(is_array($arg) ? $arg : func_get_args());
	}
	
	
	function addSet($arg) {
		$this->setExpr->pushArray(is_array($arg) ? $arg : func_get_args());
	}
	
	
	function addWhere($arg) {
		$this->whereExpr->pushArray(is_array($arg) ? $arg : func_get_args());
	}
	
	
	function addGroup($arg) {
		$this->groupByExpr->pushArray(is_array($arg) ? $arg : func_get_args());
	}
	
	
	function addOrder($arg) {
		$this->orderByExpr->pushArray(is_array($arg) ? $arg : func_get_args());
	}


	function addHaving($arg) {
		$this->havingExpr->pushArray(is_array($arg) ? $arg : func_get_args());
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
	
	
	
	private function getLimitExpr(pQL_Driver $driver) {
		return rtrim(' '.$driver->getLimitExpr($this->offset, $this->limit));
	}


	/**
	 * Возращает часть запроса, начиная с FROM до ORDER BY
	 */
	function getSQLSuffix(pQL_Driver $driver, $suffix = '') {
		$where = $this->whereExpr->get($this);
		return $this->getSQLFrom().$where.$suffix.$this->getLimitExpr($driver);
	}


	function getSQL(pQL_Driver $driver) {
		$suffix = $this->groupByExpr->get($this).$this->havingExpr->get($this).$this->orderByExpr->get($this);
		$sql = 'SELECT '.$this->getSQLFields().$this->getSQLSuffix($driver, $suffix);
		return $sql;
	}


	function getDeleteSQL(pQL_Driver $driver) {
		if (!$this->tables) $this->addTable(reset($this->registeredTables));
		
		$result = 'DELETE ';
		$result .= $this->getSQLFrom(false);
		$result .= $this->whereExpr->get($this, false);
		
		$limit = $this->getLimit($driver);
		if ($limit) $result .= $this->orderByExpr->get($this, false).$limit;

		return $result;
	}
	
	
	function getUpdateSQL(pQL_Driver $driver) {
		if (!$this->tables) $this->addTable(reset($this->registeredTables));
		
		$result = 'UPDATE ';
		$result .= $this->getSQLFrom(false, '');
		$result .= $this->setExpr->get($this, false);
		$result .= $this->whereExpr->get($this, false);
		
		$limit = $this->getLimit($driver);
		if ($limit) $result .= $this->orderByExpr->get($this, false).$limit;

		return $result;
	}
}