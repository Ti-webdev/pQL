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


	private $valuesField;
	/**
	 * Устанавливает поле в качестве заначений итератора
	 * @param pQL_Query_Builder_Field $field
	 */
	function setValuesField(pQL_Query_Builder_Field $field) {
		$this->valuesObject = null;
		$this->valuesField = $field;
		$this->valuesHash = null;
	}
	
	
	private $valuesObject;
	/**
	 * Устанавливает талбицу в качестве заначений итератора
	 * @param pQL_Query_Builder_Table $table
	 */
	function setValuesObject(pQL_Query_Builder_Table $table) {
		$this->valuesField = null;
		$this->valuesObject = $table;
		$this->valuesHash = null;
	}
	
	
	private $valuesHash;
	/**
	 * Устанавливает талбицу в качестве заначений итератора в виде ассоциативного массива
	 * @param pQL_Query_Builder_Table $table
	 */
	function setValuesHash($table) {
		$this->valuesField = null;
		$this->valuesObject = null;
		$this->valuesHash = $table;
	}


	/**
	 * @var pQL_Query_Iterator
	 */
	private $iterator;
	
	
	function setup(pQL_Driver $driver) {
		if (is_null($this->iterator)) {
			$this->iterator = new pQL_Query_Iterator($driver, $this->setupIteratorValues($driver)); 
			$this->setupIteratorKeysIndex($driver);
			$this->setupBindTables($driver);
			$this->setupBindFields($driver);
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
	private function setupIteratorKeysIndex(pQL_Driver $driver) {
		if ($this->keyField) {
			$driver->joinTable($this, $this->keyField->getTable());
			$index = $this->builder->getFieldNum($this->keyField);
			$this->iterator->setKeyIndex($index);
		}
	}


	/**
	 * устанавливает конвертер значений в итераторе
	 */
	private function setupIteratorValues(pQL_Driver $driver) {
		$field = $this->valuesField ? $this->valuesField : $this->lastField;
		if ($field) {
			$driver->joinTable($this, $field->getTable());
			$index = $this->builder->getFieldNum($field);
			return new pQL_Query_Iterator_Values_Field($index);
		}
		
		if ($this->valuesHash) {
			$table = $this->valuesHash;
		}
		else {
			$table = $this->valuesObject ? $this->valuesObject : $this->firstTable;
		}
		
		$driver->joinTable($this, $table);
		$keys = $driver->getQueryPropertiesKeys($this, $table);
		if ($this->valuesHash) {
			return new pQL_Query_Iterator_Values_Hash($keys);
		}
		$className = $driver->tableToModel($table->getName());
		return new pQL_Query_Iterator_Values_Object($driver, $className, $keys);
	}
	
	
	private function setupBindFields(pQL_Driver $driver) {
		foreach($this->bindedFields as &$bind) {
			$var = &$bind[0];
			$field = $bind[1];
			
			$driver->joinTable($this, $field->getTable());
			
			$num = $this->builder->getFieldNum($field);
			$this->iterator->bindValueIndex($var, $num);

			unset($field, $var);
		}
	}
	
	
	private function setupBindTables(pQL_Driver $driver) {
		foreach($this->bindedTables as &$bind) {
			$var = &$bind[0];
			$table = $bind[1];

			$driver->joinTable($this, $table);
			$className = $driver->tableToModel($table->getName());

			$keys = $driver->getQueryPropertiesKeys($this, $table);
			$values = new pQL_Query_Iterator_Values_Object($driver, $className, $keys);
			$this->iterator->bindValueObject($var, $values);

			unset($table, $var);
		}
	}
	
	
	private $bindedTables = array();
	function bindTable(&$var, pQL_Query_Builder_Table $table) {
		$this->bindedTables[] = array(&$var, $table);
	}
	
	
	private $bindedFields = array();
	function bindField(&$var, pQL_Query_Builder_Field $field) {
		$this->bindedFields[] = array(&$var, $field);
	}
	
	
	function unbind(&$var) {
		$varValue = $var;
		$var = 'A';
		foreach(array('bindedTables', 'bindedFields') as $type) {
			foreach($this->$type as $i=>&$bind) {
				// определяем, является ли ссылкой на $var
				if ($bind[0] !== $var) continue;

				$bindValue = $bind[0];
				$bind[0] = 'B';
				if ($bind[0] !== $var) {
					$bind[0] = $bindValue;
					continue;
				}

				// удаляем ссылку
				unset($this->{$type}[$i]);
			}
		}
		$var = $varValue;
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
		if ($this->driverIterator) {
			if ($this->driverIterator instanceof SeekableIterator) {
			 	$this->driverIterator->seek(0);
			}
			else {
				// если драйвер не поддерживает несколько итерация одного запроса
				// пересоздаем запрос
				$this->driverIterator = null;
				$this->queryHandler = null;
			}
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