<?php
final class pQL_Query implements IteratorAggregate, Countable {
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
		$this->builder = new pQL_Query_Builder;
		$this->mediator = new pQL_Query_Mediator($this->builder);
	}


	/**
	 * Переход на уровень таблицы или поля
	 * @param string $oName
	 * @return pQL_Query
	 */
	function __get($name) {
		$this->cleanResult();

		// Если таблица установлена - устанавливаем поле
		if ($this->table) $this->setField($name);
		// Иначе устанавливаем талбицу
		else $this->setTable($name);

		return $this;
	}
	

	/**
	 * Переход на уровень базы
	 * @return pQL_Query
	 */
	function db() {
		$this->field = null;
		$this->table = null;
		return $this;
	}


	/**
	 * Устанавливает значения свойства в качестве ключей выборки
	 * Свойство должно быть скаларным типом
	 * 
	 * @return pQL_Query
	 */
	function key() {
		$this->assertPropertyDefined();
		$this->cleanResult();
		$this->mediator->setKeyField($this->field);
		return $this;
	}


	/**
	 * Устанавливает свойство или тип объекта в качестве значений выборки
	 * 
	 * @return pQL_Query
	 */
	function value() {
		$this->assertClassDefined();
		$this->cleanResult();
		if ($this->field) $this->mediator->setFieldValue($this->field);
		else $this->mediator->setTableValue($this->table);
		return $this;
	}


	function in($val) {
		$this->assertPropertyDefined();
		$this->cleanResult();

		$field = $this->getWhereField();

		$orNull = false;
		$expression = $field.' IN (';
		$first = true;
		$vals = new RecursiveIteratorIterator(new RecursiveArrayIterator(func_get_args()));
		foreach($vals as $val) {
			if (is_null($val)) {
				$orNull = true;
			}
			else {
				if ($first) $first = false;
				else $expression .= ',';
				$expression .= $this->driver->getParam($val);
			}
		}
		$expression .= ')';

		if ($first) {
			if ($orNull) $this->isNull();
		} else {
			if ($orNull) $expression = '('.$expression.' OR '.$this->driver->getIsNull($field).')';
			$this->builder->addWhere($expression);
		}

		return $this;
	}
	
	
	function between($min, $max) {
		$field = $this->getWhereField();
		$qMin = $this->driver->getParam($min);
		$qMax = $this->driver->getParam($max);
		$expression = $this->driver->getBetween($field, $qMin, $qMax);
		$this->builder->addWhere($expression);
		return $this;
	}


	function toArray() {
		return iterator_to_array($this->getIterator());
	}


	/**
	 * @see IteratorAggregate::getIterator()
	 * @return pQL_Query_Iterator
	 */
	function getIterator() {
		$this->assertClassDefined();
		return $this->mediator->getIterator($this->driver);
	}


	function count() {
		$this->assertClassDefined();
		return $this->mediator->getCount($this->driver);
	}
	
	
	function __toString() {
		$this->mediator->setup($this->driver);
		return (string) $this->builder->getSQL();
	}


	/**
	 * @var pQL_Driver
	 */
	private $driver;


	/**
	 * @var pQL_Query_Builder
	 */
	private $builder;


	/**
	 * Последняя запрошенная таблица
	 * @var pQL_Query_Builder_Table
	 */
	private $table;


	/**
	 * Последнее запрошенное поле
	 * @var pQL_Query_Builder_Field
	 */
	private $field;


	/**
	 * @var pQL_Query_Mediator
	 */
	private $mediator;


	/**
	 * При изменении параметров запроса необходимо
	 * очищать результат предыдущей выборки
	 */
	private function cleanResult() {
		$this->mediator->clearResult();
	}


	/**
	 * @param string $className
	 */
	private function setTable($className) {
		$name = $this->driver->classToTable($className);
		$this->field = null;
		$this->table = $this->builder->registerTable($name);
		$this->mediator->setTable($this->table);
	}


	private function setField($propertyName) {
		$name = $this->driver->propertyToField($propertyName);
		$this->field = $this->builder->registerField($this->table, $name);
		$this->mediator->setField($this->field);
	}


	private function assertClassDefined() {
		if (!$this->table) throw new pQL_Exception('Select class first!');
	}


	private function assertPropertyDefined() {
		$this->assertClassDefined();
		if (!$this->field) throw new pQL_Exception('Select property first!');
	}
	
	
	private function isNull() {
		$field = $this->getWhereField();
		$expression = $this->driver->getIsNull($field);
		$this->builder->addWhere($expression);
	}


	private function getWhereField() {
		$result = $this->builder->getTableAlias($this->table);
		$result .= '.';
		$result .= $this->field->getName();
		return $result;
	}
}