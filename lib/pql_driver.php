<?php
/**
 * Абстрактный драйвер pQL 
 * @author Ti
 * @package pQL
 *
 */
abstract class pQL_Driver {
	/**
	 * Фабрика драйверов pQL
	 * @param string $library тип драйвера (PDO)
	 * @param mixed $handle
	 * @throws InvalidArgumentException
	 */
	static function Factory($type, $handle) {
		$class = __CLASS__.'_'.$type;
		switch($type) {
			case 'PDO':
				if (!$handle or !($handle instanceof PDO)) throw new InvalidArgumentException("Invalid PDO handle");
				$driver = $handle->getAttribute(PDO::ATTR_DRIVER_NAME);
				$class .= "_$driver";
				$type .= "_$driver";
				break;
		}
		if (class_exists($class)) return new $class($handle);
		throw new InvalidArgumentException("Invalid driver type: $type");
	}


	private $translator;
	function setTranslator(pQL_Translator $translator) {
		$this->translator = $translator;
	}


	/**
	 * @return pQL_Translator
	 */
	final protected function getTranslator() {
		return $this->translator;
	}
	
	
	abstract function getToStringField($class);


	abstract function findByPk($class, $value);


	private function isPqlObject($value) {
		return is_object($value) and $value instanceof pQL_Object;
	}


	private function getPqlId(pQL_Object $object) {
		$tr = $this->getTranslator();
		$foreignTable = $tr->classToTable($object->getClass());
		$foreignKey = $this->getTablePrimaryKey($foreignTable);
		return $object->get($tr->fieldToProperty($foreignKey));
	}

	
	final function save($class, $newProperties, $oldProperties) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$pkProperty = $tr->fieldToProperty($pk);
		$values = $fields = array();
		$isUpdate = isset($oldProperties[$pkProperty]);
		foreach($newProperties as $key=>$value) {
			$field = $tr->propertyToField($key);
			
			// foreignTable
			if ($this->isPqlObject($value)) $value = $this->getPqlId($value);
			
			$fields[] = $field;
			$values[] = $value;
		}
		if ($isUpdate) {
			if (!$fields) return $newProperties;
			if (!$pk) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
			$this->updateByPk($table, $fields, $values, $oldProperties[$pkProperty]);
		}
		else {
			if (!$pk and !$fields) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined"); 
			$id = $this->insert($table, $fields, $values);
			if ($pk) $newProperties[$pkProperty] = $id;
		}
		return $newProperties;
	}


	abstract protected function updateByPk($table, $fields, $values, $pkValue);
	abstract protected function insert($table, $fields, $values);


	final function getObject($class, $properties = array()) {
		return new pQL_Object_Simple($properties, $class);
	}



	final private function buildSelectQuery(pQL_Query_Mediator $queryMediator, pQL_Query_Predicate_List $list) {
		$tr = $this->getTranslator();
		$select = new pQL_Select_Builder;
		$iterator = new pQL_Query_Iterator($this);
		foreach($list as $predicate) {
			switch ($predicate->getType()) {
				case pQL_Query_Predicate::TYPE_CLASS:
					$table = $select->registerTable($tr->classToTable($predicate->getSubject()));
					break;

				case pQL_Query_Predicate::TYPE_PROPERTY:
					$filed = $select->registerField($table, $tr->propertyToField($predicate->getSubject()));
					break;

				case pQL_Query_Predicate::TYPE_KEY:
					$iterator->setKeyIndex($select->getFieldNum($filed));
					break;

				default:
					throw new pQL_Exception('Invalid predicate type!');
			}
		}

		if (pQL_Query_Predicate::TYPE_PROPERTY === $predicate->getType()) {
			// в значениях поле
			$iterator->setValueIndex($select->getFieldNum($filed));
		}
		else {
			// в значениях - объект
			$fields = array();
			foreach($this->getTableFields($table->getName()) as $fieldName) {
				$key = $select->getFieldNum($select->registerField($table, $fieldName));
				$fields[$key] = $tr->fieldToProperty($fieldName);
			}

			// класс выборки
			$iterator->setValueClass($tr->tableToClass($table->getName()), $fields);
		}

		$queryResults = $this->getSelectQuery($select);
		
		$queryMediator->setQueryHanlde($queryResults);
		$queryMediator->setQueryIterator($iterator);
		$queryMediator->setSelectBuilder($select);
	}
	

	final function getIterator(pQL_Query_Mediator $queryMediator, pQL_Query_Predicate_List $list) {
		if ($queryMediator->isEmptyQueryHandle()) $this->buildSelectQuery($queryMediator, $list);

		$iterator = $queryMediator->getQueryIterator();
		$iterator->setSelectIterator($this->getSelectIterator($queryMediator->getQueryHandle()));
		
		return $iterator;
	}


	
	final function getCount(pQL_Query_Mediator $queryMediator, pQL_Query_Predicate_List $list) {
		if ($queryMediator->isEmptyQueryHandle()) $this->buildSelectQuery($queryMediator, $list);
		return $this->getCountResults($queryMediator->getQueryHandle());
	}


	/**
	 * Возращает поля таблицы
	 * @param string $table
	 * @return array
	 */
	abstract protected function getTableFields($table);


	/**
	 * возращает запрос (в формате конкретного драйвера)
	 * @param pQL_Select_Builder $builder
	 * @return mixed
	 */
	abstract protected function getSelectQuery(pQL_Select_Builder $builder);


	/**
	 * Возращает итератор запроса
	 * @param mixer $queryResult запрос (в формате конкретного драйвера)
	 * @return Iterator
	 */
	abstract protected function getSelectIterator($queryResult);


	/**
	 * Возращает колличество записей в результате
	 * @param mixer $queryResult запрос (в формате конкретного драйвера)
	 * @return int
	 */
	abstract protected function getCountResults($queryResult);
}