<?php
/**
 * @author Ti
 * @package pQL
 */
final class pQL_Query implements IteratorAggregate, Countable {
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
		$this->builder = new pQL_Query_Builder;
		$this->mediator = new pQL_Query_Mediator($this->builder);
	}


	/**
	 * Переход на уровень таблицы или поля
	 * 
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
	 * 
	 * @return pQL_Query
	 */
	function db() {
		$this->field = null;
		$this->table = null;
		return $this;
	}
	
	
	/**
	 * Переход на уровень таблицы
	 * 
	 * @return pQL_Query
	 */
	function table() {
		$this->field = null;
		return $this;
	}


	/**
	 * Устанавливает значения свойства в качестве ключей выборки
	 * Свойство должно быть скаларным типом
	 * 
	 * @return pQL_Query
	 */
	function key() {
		$this->assertFieldDefined();
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
		$this->assertTableDefined();
		$this->cleanResult();
		if ($this->field) $this->mediator->setFieldValue($this->field);
		else $this->mediator->setTableValue($this->table);
		return $this;
	}


	/**
	 * @param mixed $val
	 */
	function in($val) {
		$this->addArgsExpr(func_get_args(), 'IN', '=', 'OR', 'getIsNullExpr');
		return $this;
	}


	/**
	 * @param mixed $val
	 */
	function not($val) {
		$this->addArgsExpr(func_get_args(), 'NOT IN', '<>', 'AND', 'getNotNullExpr');
		return $this;
	}


	function between($min, $max) {
		$this->cleanResult();
		$field = $this->getField();
		$qMin = $this->driver->getParam($min);
		$qMax = $this->driver->getParam($max);
		$expression = $this->driver->getBetweenExpr($field, $qMin, $qMax);
		$this->builder->addWhere($expression);
		return $this;
	}
	
	
	function lt($value) {
		$this->addWhereSymbol('<', $value);
		return $this;
	}
	
	
	function lte($value) {
		$this->addWhereSymbol('<=', $value);
		return $this;
	}


	function gt($value) {
		$this->addWhereSymbol('>', $value);
		return $this;
	}
	
	
	function gte($value) {
		$this->addWhereSymbol('>=', $value);
		return $this;
	}
	
	
	function like($expr) {
		$this->addWhereSymbol('LIKE', $expr);
		return $this;
	}
	
	
	function notLike($expr) {
		$this->addWhereSymbol('NOT LIKE', $expr);
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


	function ask() {
		$this->cleanResult();
		$this->builder->addOrder($this->getField());
		return $this;
	}
	
	
	function desc() {
		$this->cleanResult();
		$this->builder->addOrder($this->getField().' DESC');
		return $this;
	}


	function toArray() {
		return iterator_to_array($this->getIterator());
	}


	/**
	 * Возращает первое значение в запросе (с установкой всех привязанных переменных)
	 * @see bind
	 */
	function one() { 
		$limit = $this->builder->getLimit();
		$this->builder->setLimit(1);
		$result = null;
		foreach($this as $value) $result = $value;
		$this->builder->setLimit($limit);
		$this->cleanResult();
		return $result;
	}
	
	
	/**
	 * Привязывает к переменной $var значение поля или объект в запросе
	 * При итерации запроса значение привязанной переменной будедт прнимать соответствующее занчение
	 * Используется для жадной выборки
	 */
	function bind(&$var) {
		$this->cleanResult();
		if ($this->field) $this->bindField($var);
		else $this->bindTable($var);
		return $this;
	}


	/**
	 * Отвязывает переменную $var
	 * @see bind
	 */
	function unbind(&$var) {
		$this->cleanResult();
		$this->mediator->unbind($var);
		return $this;
	}


	private function addArgsExpr($args, $in, $equals, $operator, $nullFn) {
		$field = $this->getField(); 
		$this->cleanResult();

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
	
	
	private function bindField(&$var) {
		$this->assertFieldDefined();
		$this->mediator->bindField($var, $this->field);
	}
	
	
	private function bindTable(&$var) {
		$this->assertTableDefined();
		$this->mediator->bindTable($var, $this->table);
	}


	/**
	 * @see IteratorAggregate::getIterator()
	 * @return pQL_Query_Iterator
	 */
	function getIterator() {
		$this->assertTableDefined();
		return $this->mediator->getIterator($this->driver);
	}


	function count() {
		$this->assertTableDefined();
		return $this->mediator->getCount($this->driver);
	}


	function __toString() {
		$this->mediator->setup($this->driver);
		return (string) $this->builder->getSQL($this->driver);
	}
	
	
	function __clone() {
		$this->mediator = clone $this->mediator;
		$this->builder = $this->mediator->getBuilder();
		#$this->cleanResult();
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
		$this->mediator->cleanResult();
	}


	/**
	 * @param string $className
	 */
	private function setTable($className) {
		$name = $this->driver->modelToTable($className);
		$this->field = null;
		$this->table = $this->builder->registerTable($name);
		$this->mediator->setTable($this->table);
	}


	private function setField($propertyName) {
		$name = $this->driver->propertyToField($propertyName);
		$this->field = $this->builder->registerField($this->table, $name);
		$this->mediator->setField($this->field);
	}


	private function assertTableDefined() {
		if (!$this->table) throw new pQL_Exception('Select class first!');
	}


	private function assertFieldDefined() {
		$this->assertTableDefined();
		if (!$this->field) throw new pQL_Exception('Select property first!');
	}
	
	
	
	private function joinCurrentTable() {
		$this->driver->joinTable($this->mediator, $this->table);
	}


	private function getField() {
		$this->assertFieldDefined();
		$this->joinCurrentTable();

		$result = $this->builder->getTableAlias($this->table);
		$result .= '.';
		$result .= $this->field->getName();
		return $result;
	}


	private function addWhereSymbol($symbol, $value) {
		$this->cleanResult();
		$field = $this->getField();
		$qValue = $this->driver->getParam($value);
		$this->builder->addWhere("$field $symbol $qValue");
	}
	
	
	/**
	 * @return pQL_Query_Builder
	 */
	function qb() {
		return $this->builder;
	}
}