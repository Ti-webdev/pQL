<?php
/**
 * Абстрактный драйвер pQL 
 * @author Ti
 * @package pQL
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
	
	
	private $pQL;
	final function setPql(pQL $pQL) {
		$this->pQL = $pQL;
	}
	
	
	private $definer;
	final function setObjectDefinder(pQL_Object_Definer_Interface $definer) {
		$this->definer = $definer;
	}
	
	
	final function getObjectDefinder() {
		if (!$this->definer) $this->definer = new pQL_Object_Definer_ClassName;
		return $this->definer;
	}


	/**
	 * @return pQL_Translator
	 */
	final protected function getTranslator() {
		return $this->translator;
	}


	abstract function getToStringField($model);


	abstract function findByPk($model, $value);


	private function isPqlObject($value) {
		return is_object($value) and $value instanceof pQL_Object;
	}


	private function getPqlId(pQL_Object $object) {
		$tr = $this->getTranslator();
		$foreignTable = $tr->modelToTable($object->getModel());
		$foreignKey = $this->getTablePrimaryKey($foreignTable);
		return $object->get($tr->fieldToProperty($foreignKey));
	}

	
	final function save($model, $newProperties, $oldProperties) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($model);
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
	
	
	final function delete($model, $newProperties, $properties) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($model);
		$pk = $this->getTablePrimaryKey($table);
		$pkProperty = $tr->fieldToProperty($pk);
		$this->deleteByPk($table, $properties[$pkProperty]);
		$result= array_merge($properties, $newProperties);
		unset($result[$pkProperty]);
		return $result;
	}


	abstract protected function updateByPk($table, $fields, $values, $pkValue);
	abstract protected function insert($table, $fields, $values);
	abstract protected function deleteByPk($table, $value);


	final function getObject($model, $properties = array()) {
		$result = $this->getObjectDefinder()->getObject($this->pQL, $model, $properties);
		if (is_object($result) and $result instanceof pQL_Object) return $result;
		throw new pQL_Object_Definer_Exception('Invalid object type: require pQL_Object instance!');
	}


	abstract function getQueryHandler(pQL_Query_Builder $builder);
	abstract function getQueryIterator(pQL_Query_Mediator $mediator);



	/**
	 * Взращает PRIMARY KEY поле таблицы
	 * @param string $table
	 * @return string
	 */
	abstract protected function getTablePrimaryKey($table);


	/**
	 * Возращает поля таблицы
	 * @param string $table
	 * @return array
	 */
	abstract function getTableFields($table);


	/**
	 * Возращает колличество записей в результате
	 * @return int
	 */
	abstract function getCount(pQL_Query_Mediator $queryMediator);


	abstract function getParam($val);


	function getIsNullExpr($expr) {
		return "$expr ISNULL";
	}


	function getNotNullExpr($expr) {
		return "$expr NOTNULL";
	}
	
	
	function getBetweenExpr($expr, $min, $max) {
		return "$expr BETWEEN $min AND $max";
	}
	
	
	function getLimitExpr($offset, $limit) {
		$result = '';
		if ($offset) {
			$result .= " LIMIT $offset, ";
			$result .= $limit ? $limit : PHP_INT_MAX;
		}
		elseif ($limit) {
			$result .= " LIMIT $limit";
		}
		return $result;
	}


	final function modelToTable($model) {
		return $this->getTranslator()->modelToTable($model);
	}


	final function tableToModel($tableName) {
		return $this->getTranslator()->tableToModel($tableName);
	}


	final function propertyToField($property) {
		return $this->getTranslator()->propertyToField($property);
	}


	final function fieldToProperty($field) {
		return $this->getTranslator()->fieldToProperty($field);
	}
	
	
	/**
	 * Объединяет таблицы в запросе
	 * 
	 * @param pQL_Query_Mediator $mediator
	 * @param pQL_Query_Builder_Table $table
	 */
	function joinTable(pQL_Query_Mediator $mediator, pQL_Query_Builder_Table $table) {
		$builder = $mediator->getBuilder();

		// талбица уже в запросе
		if ($builder->tableExists($table)) return;

		$tables = $builder->getFromTables();

		// нет таблиц в запросе - не нужно объединять
		if (count($tables) < 1) {
			$builder->addTable($table);
			return;
		}


		// Ищем таблицу с которой можно объедениться
		foreach($tables as $joinTable) {
			$joinFields = $this->getJoinTableFields($table->getName(), $joinTable->getName());
			if ($joinFields) {
				// нашли - объединяем
				list($fieldA, $fieldB) = $joinFields;
				$expr = $builder->getTableAlias($table).'.'.$fieldA;
				$expr .= ' = ';
				$expr .= $builder->getTableAlias($joinTable).'.'.$fieldB;
				$builder->addWhere($expr);
				return;
			}
		}
	}


	/**
	 * Возращает два поля по которым возможно объединение таблиц
	 * Если объединение не возможно возращает NULL
	 * 
	 * @param string $tableA
	 * @param string $tableB
	 * @return array | null
	 */
	private function getJoinTableFields($tableA, $tableB) {
		$fieldB = $this->getJoinSecondTableFieldToFirstTable($tableA, $tableB);

		if ($fieldB) return array($this->getTablePrimaryKey($tableA), $fieldB);

		$fieldA = $this->getJoinSecondTableFieldToFirstTable($tableB, $tableA);
		if ($fieldA) return array($fieldA, $this->getTablePrimaryKey($tableB));

		return;
	}


	/**
	 * Опередляет поле второй таблицы объединения с первой
	 * @return string | null
	 */
	private function getJoinSecondTableFieldToFirstTable($tableA, $tableB) {
		$tableNameA = $this->getTranslator()->removeDbQuotes($tableA);
		foreach($this->getTableFields($tableB) as $fieldB) {
			$fieldBSuffix = preg_replace('#^id_|_id$#', '', $this->getTranslator()->removeDbQuotes($fieldB));
			$tableASuffix = substr($tableNameA, strlen($fieldBSuffix));
			if (0 === strcasecmp($fieldBSuffix, $tableASuffix)) return $fieldB;
		}
		return null;
	}
	
	
	final function getQueryPropertiesKeys(pQL_Query_Mediator $mediator, pQL_Query_Builder_Table $table) {
		$result = array();
		$builder = $mediator->getBuilder();
		foreach($this->getTableFields($table->getName()) as $fieldName) {
			$field = $builder->registerField($table, $fieldName);
			$num = $builder->getFieldNum($field);
			$result[$num] = $this->fieldToProperty($field->getName());
		}
		return $result;
	}


	abstract protected function getTables();
	
	
	private function getModelFields($model) {
		$table = $this->modelToTable($model);
		return $this->getTableFields($table);
	}
	
	
	/**
	 * Возращает поле foreign key для переданного свойства
	 * Если не удалось определить foreign key вернет null
	 * 
	 * @param string $model
	 * @param string $property
	 */
	private function getPropertyForeignField($model, $property) {
		$field = $this->propertyToField($property);
		$table = $this->modelToTable($model);
		$fields = $this->getTableFields($table);
		if (in_array($field, $fields)) return null;

		$tr = $this->getTranslator();
		$field = $tr->removeDbQuotes($field);

		$foreignField = $tr->addDbQuotes("{$field}_id");
		if (in_array($foreignField, $fields)) return $foreignField;

		$foreignField = $tr->addDbQuotes("id_$field");
		if (in_array($foreignField, $fields)) return $foreignField;
		
		return null;
	}
	
	
	private function getObjectPropertyModel($model, $property) {
		if (!$this->getPropertyForeignField($model, $property)) return false;

		$tr = $this->getTranslator();
		
		$field = $this->propertyToField($property);
		$field = $tr->removeDbQuotes($field);

		foreach($this->getTables() as $quotedTable) {
			$table = $tr->removeDbQuotes($quotedTable);

			// если таблица заканчивается названием свойства
			// считаем что свойсвто - объект
			if (0 === strcasecmp($field, substr($table, - strlen($field)))) {
				return $this->tableToModel($quotedTable);
			}
		}
		return false;
	}


	/**
	 * Проверяет что свойство является pQL объектом
	 * @param string $model
	 * @param string $property
	 */
	final function isObjectProperty($model, $property) {
		return (bool) $this->getObjectPropertyModel($model, $property);
	}


	/**
	 * Загружает связанный объект
	 * @param pQL_Object $object
	 * @param string $property
	 */
	final function loadObjectProperty(pQL_Object $object, $property) {
		$model = $object->getModel();
		$foreignModel = $this->getObjectPropertyModel($model, $property);
		$field = $this->getPropertyForeignField($model, $property);
		$foreignId = $object->get($this->fieldToProperty($field));
		return $this->findByPk($foreignModel, $foreignId);
	}
}