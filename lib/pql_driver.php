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
	abstract function save($class, $newProperties, $oldProperties);


	final function getObject($class, $properties = array()) {
		return new pQL_Object_Simple($properties, $class);
	}
	

	final function getIterator($queryId, pQL_Query_Predicate_List $list) {
		if ($this->queryExists($queryId)) return $this->queryList[$queryId][1];
	
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

		$queryResults = $this->getSelectQuery($select->getSQL());
		$iterator->setSelectIterator($this->getSelectIterator($queryResults));
		
		$this->queryList[$queryId] = array($queryResults, $iterator);

		return $iterator;
	}


	private $queryList = array();


	private function queryExists($queryId) {
		return isset($this->queryList[$queryId]);
	}
	
	
	final function clearQuery($queryId) {
		if ($this->queryExists($queryId)) unset($this->queryList[$queryId]);
		return $this;
	}
	
	
	final function getCount($queryId, pQL_Query_Predicate_List $stack) {
		$this->getIterator($queryId, $stack);
		return $this->getCountResults($this->queryList[$queryId][0]);
	}


	/**
	 * Возращает поля таблицы
	 * @param string $table
	 * @return array
	 */
	abstract protected function getTableFields($table);


	/**
	 * возращает запрос (в формате конкретного драйвера)
	 * @param  $sql
	 * @return mixed
	 */
	abstract protected function getSelectQuery($sql);
	
	
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