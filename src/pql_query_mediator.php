<?php
/**
 * Посредник между pQL_Query и pQL_Driver
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Mediator {
	/**
	 * @var pQL_Query_Builder
	 */
	private $builder;
	function __construct(pQL_Query_Builder $builder) {
		$this->builder = $builder;
	}
	
	
	function __clone() {
		$this->builder = clone $this->builder;
	}


	private $firstTable;
	function setTable(pQL_Query_Builder_Table $table) {
		if (!$this->firstTable) $this->firstTable = $table;
	}


	private $lastField;
	function setField(pQL_Query_Builder_Field $field) {
		$this->lastField = $field;
	}


	function getBuilder() {
		return $this->builder;
	}


	function cleanResult() {
		$this->count = null;
		$this->iterator = null;
		$this->queryHandler = null;
		$this->driverIterator = null;
		$this->lastField = null;
	}


	/**
	 * @var pQL_Query_Builder_Field
	 */
	private $keyField;
	/**
	 * Устанавливает поле в качестве ключей итератора
	 * @param pQL_Query_Builder_Field $field
	 */
	function setKeyField(pQL_Query_Builder_Field $field) {
		$this->keyField = $field;
	}


	private $valueField;
	/**
	 * Устанавливает поле в качестве заначений итератора
	 * @param pQL_Query_Builder_Field $field
	 */
	function setFieldValue(pQL_Query_Builder_Field $field) {
		$this->valueTable = null;
		$this->valueField = $field;
	}
	
	
	private $valueTable;
	/**
	 * Устанавливает талбицу в качестве заначений итератора
	 * @param pQL_Query_Builder_Table $table
	 */
	function setTableValue(pQL_Query_Builder_Table $table) {
		$this->valueField = null;
		$this->valueTable = $table;
	}


	/**
	 * @var pQL_Query_Iterator
	 */
	private $iterator;
	
	
	function setup(pQL_Driver $driver) {
		if (is_null($this->iterator)) {
			$this->iterator = new pQL_Query_Iterator($driver);
			$this->setupKeyIndex();
			$this->setupValue($driver);
		}
		return $this->iterator;
	}


	function getIterator(pQL_Driver $driver) {
		$this->setup($driver);
		$this->iterator->setSelectIterator($this->getDriverIterator($driver));
		return $this->iterator;
	}


	/**
	 * устанавливаем ключи в итератор
	 */
	private function setupKeyIndex() {
		if ($this->keyField) {
			$index = $this->builder->getFieldNum($this->keyField);
			$this->iterator->setKeyIndex($index);
		}
	}


	/**
	 * определяем значения в итератор
	 */
	private function setupValue(pQL_Driver $driver) {
		$field = $this->valueField ? $this->valueField : $this->lastField;
		if ($field) {
			$driver->joinTable($this, $field->getTable());
			$index = $this->builder->getFieldNum($field);
			$this->iterator->setValueIndex($index);
			return;
		}

		$table = $this->valueTable ? $this->valueTable : $this->firstTable;
		$driver->joinTable($this, $table);
		$tableName = $table->getName();
		$className = $driver->tableToClass($tableName);
		$keys = array();
		foreach($driver->getTableFields($tableName) as $fieldName) {
			$field = $this->builder->registerField($table, $fieldName);
			$num = $this->builder->getFieldNum($field);
			$keys[$num] = $driver->fieldToProperty($field->getName());
		}
		$this->iterator->setValueClass($className, $keys);
	}


	/**
	 * Низкоуровневый итератор запроса
	 * @var Iterator
	 */
	private $driverIterator;


	private function setDriverIterator(Iterator $driverIterator) {
		$this->driverIterator = $driverIterator;
	}
	
	
	private $queryHandler;


	function getQueryHandler(pQL_Driver $driver) {
		if (is_null($this->queryHandler)) {
			$this->setup($driver);
			$this->queryHandler = $driver->getQueryHandler($this->getBuilder());
		}
		return $this->queryHandler;
	}


	function getDriverIterator(pQL_Driver $driver) {
		// если драйвер не поддерживает несколько итерация одного запроса
		// пересоздаем запрос
		if ($this->driverIterator and !$driver->isSupportRewindQuery()) {
			$this->driverIterator = null;
			$this->queryHandler = null;
		}

		if (is_null($this->driverIterator)) {
			$driverIterator = $driver->getQueryIterator($this);
			$this->setDriverIterator($driverIterator);
		}
		return $this->driverIterator;
	}


	private $count;
	function getCount(pQL_Driver $driver) {
		if (is_null($this->count)) $this->count = $driver->getCount($this);
		return $this->count;
	}
}