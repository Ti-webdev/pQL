<?php
/**
 * @author Ti
 * @package pQL
 */
final class pQL_Query implements IteratorAggregate, Countable, ArrayAccess {
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
		else $this->setTableByModel($name);

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
		$this->builder->addWhere($field," BETWEEN $qMin AND $qMax");

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


	function asc() {
		$this->cleanResult();
		$this->builder->addOrder($this->getField());
		return $this;
	}
	
	
	function desc() {
		$this->cleanResult();
		$this->builder->addOrder($this->getField(),' DESC');
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
	
	
	function group() {
		$this->cleanResult();
		$field = $this->getField();
		$this->builder->addGroup($field);
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


	const MAX_RECURSIVE_LEVEL = 10000;
	private function getArgsRecursive($args, $level = 0) {
		$result = array();
		foreach($args as $arg) {
			if (is_array($arg) or (is_object($arg) and !$arg instanceof pQL_Object and $arg instanceof Traversable)) {
				if (self::MAX_RECURSIVE_LEVEL < $level) throw new RuntimeException('Recursive level over limit'); 
				$result = array_merge($result, $this->getArgsRecursive($arg, $level + 1));
			}
			else {
				$result[] = $arg;
			}
		}
		return $result;
	}


	private function addArgsExpr($args, $in, $equals, $operator, $nullFn) {
		$field = $this->getField(); 
		$this->cleanResult();

		// собираем параметры
		$orNull = false;
		$expression = array();
		$vals = $this->getArgsRecursive($args);
		foreach($vals as $val) {
			if (is_null($val)) {
				$orNull = true;
			}
			else {
				if ($expression) $expression[] = ',';
				if (is_object($val) and $val instanceof pQL_Object) {
					$val = $this->driver->getPrimaryKeyValue($val);
				}
				$expression[] = $this->driver->getParam($val);
			}
		}

		if ($expression) {
			// IN или =
			if (1 == count($expression)) {
				array_unshift($expression, ' '.$equals.' ');
			}
			else {
				array_unshift($expression, " $in (");
				array_push($expression, ")");
			}

			// поле
			array_unshift($expression, $field);

			if ($orNull) {
				array_unshift($expression, '(');
				array_push($expression, ' '.$operator.' ', $field, ' '.$this->driver->$nullFn(), ')');
			}
			$this->builder->addWhere($expression);
		} elseif ($orNull) {
			$this->builder->addWhere($field, ' '.$this->driver->$nullFn());
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
	 * @todo if object is foreign key field - do without join 
	 */
	function with(pQL_Object $object) {
		$this->cleanResult();

		$model = $object->getModel();
		$table = $this->driver->modelToTable($model);

		$qTable = $this->builder->registerTable($table);
		$this->driver->joinTable($this->mediator, $qTable);

		foreach($this->driver->getTablePrimaryKey($table) as $field) {
			$property = $this->driver->fieldToProperty($field);
			$value = $object->get($property);
			$rField = $this->builder->registerField($qTable, $field);
			$this->builder->addWhere($rField, is_null($value) ? ' ISNULL ' : ' = '.$this->driver->getParam($value));
		}

		return $this;
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
	private function setTableByModel($model) {
		$tableName = $this->driver->modelToTable($model);
		$this->field = null;
		$this->table = $this->builder->registerTable($tableName);
		$this->mediator->setTable($this->table);
		
		$filter = $this->driver->getQueryFilterManager()->get($tableName);
		if ($filter) $this->applyFilter($filter);
	}
	
	
	function applyFilter(pQL_Query_Filter $filter, $args = array()) {
		$filter->apply($this);
		if ($filter->callback) {
			// сохраняем текущие
			$currentTable = $this->table;
			$currentField = $this->field;

			// ставим из фильтра
			if ($currentTable->getName() !== $filter->tableName) {
				$model = $this->driver->tableToModel($filter->tableName);
				$this->db()->$mode;
			}
			if ($filter->fieldName) {
				$property = $this->driver->fieldToProperty($filter->fieldName);
				$this->$property;
			}
			
			// выполняем
			array_unshift($args, $this);
			call_user_func_array($filter->callback, $args);

			// восстанавливаем текущие
			$this->table = $currentTable;
			$this->field = $currentField;
		}
		$this->cleanResult();
	}


	private function setField($propertyName) {
		$name = $this->driver->propertyToField($propertyName);
		$nameId = $this->driver->getFieldNameId($this->table->getName(), $name);
		$this->field = $this->builder->registerField($this->table, $nameId);
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
		return $this->field;
	}


	private function addWhereSymbol($symbol, $value) {
		$this->cleanResult();
		$field = $this->getField();
		$qValue = $this->driver->getParam($value);
		$this->builder->addWhere($field, " $symbol $qValue");
	}
	
	
	/**
	 * @return pQL_Query_Builder
	 */
	function qb() {
		return $this->builder;
	}


	function delete() {
		return $this->driver->exec($this->builder->getDeleteSQL($this->driver));
	}
	
	
	function set($value) {
		$field = $this->getField();
		$qValue = $this->driver->getParam($value);
		$this->builder->addSet($field,' = ', $qValue);
		return $this;
	}
	

	function offsetExists($offset) {
		if ($this->table) {
			$field = $this->driver->propertyToField($offset);
			try {
				return (bool) $this->driver->getFieldNameId($this->table->getName(), $field);
			}
			catch (InvalidArgumentException $e) {
				return false;
			}
		}
		else {
			return $this->driver->modelExists($offset);
		}
	}


	function offsetGet($offset) {
		return $this->__get($offset);
	}


	function offsetSet($offset, $value) {
		throw new RuntimeException('Not implementd');
	}

	
	function offsetUnset($offset) {
		throw new RuntimeException('Not implementd');
	}
	
	
	function update() {
		return $this->driver->exec($this->builder->getUpdateSQL($this->driver));
	}


	function filter($name = null, $filterFunction = null) {
		$this->assertTableDefined();

		// если аргумент один и это функция
		if (1 === func_num_args() and is_callable($name)) {
			$filterFunction = $name;
			$name = null;
		}
		else {
			if (!is_null($name)) settype($name, 'string');
			if ($filterFunction and !is_callable($filterFunction)) throw new InvalidArgumentException('filterFunction is not callable');
		}
		
		$filter = new pQL_Query_Filter;
		$filter->queryBuilder = $this->builder;
		$filter->tableName = $this->table->getName();
		$filter->callback = $filterFunction;
		if ($this->field) $filter->fieldName = $this->field->getName();
		
		$this->driver->getQueryFilterManager()->add($name, $filter);
		return $this;
	}
	
	
	/**
	 * Магический метод для динамических фильтров
	 * 
	 * @param string $fn название фильтра
	 * @param array $args
	 */
	function __call($filterName, $args) {
		if ($this->table) {
			$filterManager = $this->driver->getQueryFilterManager();
			$tableName = $this->table->getName();
			$filter = $filterManager->get($tableName, $filterName);
			if ($filter) {
				if ($filter) $this->applyFilter($filter, $args);
				return $this;
			}
			throw new ErrorException("Call to undefined method ".__CLASS__."::$filterName or filter ".$this->table->getName().".$filterName");
		}
		throw new ErrorException("Call to undefined method ".__CLASS__."::$filterName");
	}
}