<?php
/**
 * @author: Ti
 * @date: 21.02.11
 * @time: 14:09
 */
 
class pQL_Query_Expr {
	function __construct($prefix = '', $suffix = '') {
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}


	private $expr = '';
	private $fields = array();
	private $isPrefix = true;
	private $prefix;
	private $suffix;
	
	
	function pushArray($args) {
		if ($this->isPrefix) $this->isPrefix = false;
		else $this->expr .= $this->suffix;
		foreach($args as $arg) $this->push($arg);
	}


	/**
	 * Экспортирует параметры в другой QueryBuilder
	 */
	function export(pQL_Query_Builder $queryBuiler) {
		$result = array();
		$start = 0;
		foreach($this->fields as $end=>$field) { /* @var $field pQL_Query_Builder_Field */
			// копируем выражение до field
			if ($start != $end) $result[] = substr($this->expr, $start, $end - $start);
			
			// изменяем field на новый
			$tableName = $field->getTable()->getName();
			$table = $queryBuiler->registerTable($tableName);
			$newField = $queryBuiler->registerField($table, $field->getName());
			
			// вставляем field
			$result[] = $newField;
			
			$start = $end;
		}
		// выражение после field
		if ($start < strlen($this->expr)) $result[] = substr($this->expr, $start);
		return $result;
	}

	
	function push($expr) {
		if (is_object($expr) and $expr instanceof pQL_Query_Builder_Field) {
			$pos = strlen($this->expr);
			$this->fields[$pos] = $expr;
		}
		else {
			$this->expr .= $expr;
		}
	}


	function get(pQL_Query_Builder $qb, $withAlias = true) {
		$result = '';
		if ($this->fields || '' !== $this->expr) $result .= $this->prefix;

		$start = 0;
		foreach($this->fields as $end=>$field) { /* @var $field pQL_Query_Builder_Field */
			// копируем выражение до поля
			if ($start != $end) $result .= substr($this->expr, $start, $end - $start);
			
			// вставляем поле
			$result .= $qb->getField($field, $withAlias);
			
			$start = $end;
		}
		// выражение после field
		if ($start < strlen($this->expr)) $result .= substr($this->expr, $start);
			
		return $result;
	}


	function isEmpty() {
		return '' === $this->expr;
	}
}
