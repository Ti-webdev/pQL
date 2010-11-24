<?php
final class pQL_Query implements IteratorAggregate, Countable {
	private $driver;


	/**
	 * @var pQL_Query_Builder
	 */
	private $builder;


	/**
	 * Последняя запрошенная таблица
	 * @var pQL_Query_Builder_Table
	 */
	private $bTable;


	/**
	 * Последнее запрошенное поле
	 * @var pQL_Query_Builder_Field
	 */
	private $bField;


	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
		$this->builder = new pQL_Query_Builder;
	}


	/**
	 * @var pQL_Query_Iterator
	 */
	private $iterator;
	
	
	private $select;


	/**
	 * При изменении параметров запроса необходимо
	 * очищать результат предыдущей выборки
	 */
	private function cleanResult() {
		/**
		 * @todo
		 */
	}


	/**
	 * Переход на уровень таблицы или поля
	 * @param string $oName
	 * @return pQL_Query
	 */
	function __get($name) {
		$this->cleanResult();

		// Если таблица установлена - выбираем поле
		if ($this->bTable) $this->setField($name);
		// Иначе устанавливаем талбицу
		else $this->setClass($name);

		return $this;
	}


	private function setTable($className) {
		$name = $this->driver->classToTable($className);
		$this->bTable = $this->builder->registerTable($name);
		$this->bField = null;
	}


	private function setField($propertyName) {
		$name = $this->driver->propertyToField($propertyName);
		$this->bField = $this->builder->registerField($this->bTable, $name);
	}
	

	/**
	 * Переход на уровень базы
	 * @return pQL_Query
	 */
	private function db() {
		$this->bField = null;
		$this->bTable = null;
		return $this;
	}
	
	
	/**
	 * @var pQL_Query_Builder_Field
	 */
	private $bKeyField;


	/**
	 * Устанавливает значения свойства в качестве ключей выборки
	 * @return pQL_Query
	 */
	function key() {
		$this->assertPropertyDefined();

		$this->cleanResult();

		$this->bKeyField = $this->bField;

		return $this;
	}


	private $bValue;
	/**
	 * Устанавливает свойство или тип объекта в качестве значений выборки
	 * 
	 * @return pQL_Query
	 */
	function value() {
		$this->assertClassDefined();
		$this->cleanResult();
		
		if ($this->bField) $this->bValue = $this->bField;
		else $this->bValue = $this->bTable;
		
		return $this;
	}


	private function assertClassDefined() {
		if (!$this->bTable) throw new pQL_Exception('Select class first!');
	}


	private function assertPropertyDefined() {
		$this->assertClassDefined();
		if (!$this->bField) throw new pQL_Exception('Select property first!');
	}


	/**
	 * @see IteratorAggregate::getIterator()
	 */
	function getIterator() {
		return $this->driver->getIterator();
	}


	function count() {
		return $this->driver->getCount();
	}
	
	
	private function isNull() {
		$field = $this->getWhereField();
		$expression = $this->driver->getIsNull();
		$this->builder->addWhere($expression);
	}


	private function getWhereField() {
		$result = $this->builder->getTableAlias($this->bTable);
		$result .= '.';
		$result .= $this->bField->getName();
		return $result;
	}
	

	function in($val) {
		$this->assertPropertyDefined();

		$field = $this->getWhereField();

		$orNull = false;
		$expression .= $field.' IN (';
		$first = true;
		$vals = new RecursiveIteratorIterator(new RecursiveArrayIterator(func_get_args()));
		foreach($vals as $val) {
			if (is_null($val)) {
				$orNull = true;
			}
			else {
				if ($first) $first = false;
				else $expression .= ',';
				$this->driver->quote($val);
			}
		}
		$expression .= ')';

		if ($first) {
			if ($orNull) $expression = '('.$expression.' OR '.$this->driver->getIsNull($field).')';
			$this->builder->addWhere($expression);
		} elseif ($orNull) {
			$this->isNull();
		}

		return $this;
	}
	
	
	function __toString() {
		return (string) $this->builder->getSQL();
	}
}