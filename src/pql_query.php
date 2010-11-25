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
		$this->cleanResult();
		$this->addArgsExpr(func_get_args(), 'IN', '=', 'OR', 'getIsNullExpr');
		return $this;
	}


	function not($val) {
		$this->cleanResult();
		$this->addArgsExpr(func_get_args(), 'NOT IN', '<>', 'AND', 'getNotNullExpr');
		return $this;
	}
	
	
	
	private function addArgsExpr($args, $in, $equals, $operator, $nullFn) { 
		$field = $this->getWhereField();

		$orNull = false;
		$expression = null;
		$i = 0;
		$vals = new RecursiveIteratorIterator(new RecursiveArrayIterator($args));
		foreach($vals as $val) {
			if (is_null($val)) {
				$orNull = true;
			}
			else {
				if (0 < $i) {
					if (1 == $i) $expression = "$field $in($expression";
					$expression .= ','.$this->driver->getParam($val);
				}
				else {
					$expression = $this->driver->getParam($val);
				}
				$i++;
			}
		}
		
		if (0 < $i) {
			if (1 == $i) $expression = "$field $equals $expression";
			else $expression .= ')';

			if ($orNull) $expression = "($expression $operator ".$this->driver->getIsNullExpr($field).')';
			$this->builder->addWhere($expression);
		} elseif ($orNull) {
			$expression = $this->driver->$nullFn($field);
			$this->builder->addWhere($expression);
		}
	}
	
	
	function between($min, $max) {
		$this->cleanResult();
		$field = $this->getWhereField();
		$qMin = $this->driver->getParam($min);
		$qMax = $this->driver->getParam($max);
		$expression = $this->driver->getBetweenExpr($field, $qMin, $qMax);
		$this->builder->addWhere($expression);
		return $this;
	}


	function limit($limit = null) {
		$this->cleanResult();
		$this->builder->setLimit($limit);
		return $this;
	}
	
	
	function offset($offset = null) {
		$this->cleanResult();
		$this->builder->setOffset($offset);
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
		return (string) $this->builder->getSQL($this->driver);
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


	private function getWhereField() {
		$this->assertPropertyDefined();
		$result = $this->builder->getTableAlias($this->table);
		$result .= '.';
		$result .= $this->field->getName();
		return $result;
	}
}