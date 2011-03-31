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
	
	
	protected function cache($key) {
		return new pQL_Cache_Element($this->pQL->cache(), $key);
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


	final function findByPk($model, $value) {
		$table = $this->modelToTable($model);
		$fields = $this->getTablePrimaryKey($table);
		if (is_array($value)) {
			$query = $this->pQL->creater()->$model;
			foreach($fields as $field) {
				$property = $this->fieldToProperty($field);
				if (!isset($value[$property])) throw new InvalidArgumentException("Value $property not received");
				$query->$property->in($value[$property])->one();
			}
			return $query->one();
		}
		if (1 < count($fields)) {
			throw new InvalidArgumentException("Primary key more has ".count($fields)." fields. One received");
		}
		foreach($fields as $field) {
			$property = $this->fieldToProperty($field);
			return $this->findByField($model, $property, $value);
		}
	}


	final function findByForeignObject($model, pQL_Object $object) {
		return $this->pQL->creater()->$model->object($object)->one();
	}


	function findByField($model, $property, $value) {
		if (!is_string($property)) throw new InvalidArgumentException("Assert string of \$property");
		return $this->pQL->creater()->$model->$property->in($value)->one();
	}


	private function isPqlObject($value) {
		return is_object($value) and $value instanceof pQL_Object;
	}


	private function getPqlId(pQL_Object $object) {
		$tr = $this->getTranslator();
		$foreignTable = $tr->modelToTable($object->getModel());
		list($foreignKey) = $this->getTablePrimaryKey($foreignTable);
		return $object->get($tr->fieldToProperty($foreignKey));
	}

	
	final function save($model, $newProperties, $oldProperties) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($model);
		$pkFields = $this->getTablePrimaryKey($table);
		$values = $fields = array();
		$isUpdate = (bool) $pkFields;
		$pkValues = array();
		foreach($pkFields as $pkField) {
			$pkProperty = $tr->fieldToProperty($pkField);
			$isUpdate = $isUpdate && isset($oldProperties[$pkProperty]);
			if ($isUpdate) $pkValues[$pkField] = $oldProperties[$pkProperty];
		}
		foreach($newProperties as $key=>$value) {
			$field = $tr->propertyToField($key);
			
			// foreignTable
			if ($this->isPqlObject($value)) $value = $this->getPqlId($value);
			
			$fields[] = $field;
			$values[] = $value;
		}
		if ($isUpdate) {
			if (!$fields) return $newProperties;
			$this->update($table, $fields, $values, $pkValues);
		}
		else {
			if (!$pkFields and !$fields) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
			$id = $this->insert($table, $fields, $values);
			if (1 == count($pkFields) and $id) {
				$pkProperty = $tr->fieldToProperty(reset($pkFields));
				$newProperties[$pkProperty] = $id;
			}
		}
		return $newProperties;
	}
	

	/**
	 * удаляет модель с свойствами $newProperties и $properties
	 * @param  $model
	 * @param  $newProperties
	 * @param  $properties
	 * @return возращает свойства без PK
	 */
	final function deleteByModel($model, $newProperties, $properties) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($model);
		$pkFields = $this->getTablePrimaryKey($table);
		$pkValues = array();
		$result = array_merge($properties, $newProperties);
		foreach($pkFields as $pkField) {
			$pkProperty = $tr->fieldToProperty($pkField);
			$pkValues[$pkField] = $properties[$pkProperty];
			unset($result[$pkProperty]);
		}
		$this->delete($table, $pkValues);
		return $result;
	}


	abstract protected function update($table, $fields, $values, $where);
	abstract protected function insert($table, $fields, $values);
	abstract protected function delete($table, $where);

	abstract function exec($sql);


	function getProperties(pQL_Object $object) {
		$tr = $this->getTranslator();
		$model = $object->getModel();
		$table = $tr->modelToTable($model);
		$pkFields = $this->getTablePrimaryKey($table);

		$query = $this->pQL->creater()->$model;
		// where
		foreach($pkFields as $pkField) {
			$pkProperty = $tr->fieldToProperty($pkField);
			$query->$pkProperty->in($object->$pkProperty);
		}
		$result = array();
		// bind
		foreach($this->getTableFields($table) as $field) {
			$property = $tr->fieldToProperty($field->getName());
			$query->$property->bind($result[$property]);
		}
		$query->one();
		return $result;
	}


	final function getObject($model, $properties = array()) {
		$result = $this->getObjectDefinder()->getObject($this->pQL, $model, $properties);
		if (is_object($result) and $result instanceof pQL_Object) return $result;
		throw new pQL_Object_Definer_Exception('Invalid object type: require pQL_Object instance!');
	}


	abstract function getQueryHandler(pQL_Query_Builder $builder);
	abstract function getQueryIterator(pQL_Query_Mediator $mediator);



	/**
	 * Взращает PRIMARY KEY поля таблицы
	 * @param string $table
	 * @return string
	 */
	final function getTablePrimaryKey($table) {
		$result = array();
		foreach($this->getFieldsCached($table) as $field) {
			if ($field->isPrimaryKey()) $result[] = $this->getTranslator()->addDbQuotes($field->getName());
		}
		return $result;
	}
	
	
	final function getPrimaryKeyValue(pQL_Object $object) {
		$model = $object->getModel();
		$table = $this->modelToTable($model);
		$pk = $this->getTablePrimaryKey($table);
		if (!$pk) throw new InvalidArgumentException("Primary key of '".$this->translator->removeDbQuotes($table)."' not defined");
		if (1 < count($pk)) throw new InvalidArgumentException("Primary key of '".$this->translator->removeDbQuotes($table)."' is composite");
		$property = $this->fieldToProperty(reset($pk));
		return $object->get($property);
	}


	/**
	 * Возращает поля таблицы
	 * @param string $table
	 * @return array
	 */
	abstract protected function getTableFields($table);


	/**
	 * Возращает колличество записей в результате
	 * @return int
	 */
	abstract function getCount(pQL_Query_Mediator $queryMediator);


	abstract function getParam($val);


	function getIsNullExpr() {
		return 'ISNULL';
	}


	function getNotNullExpr() {
		return 'NOTNULL';
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
	
	
	/**
	 * Возращает первое существующее поле
	 * в таблице $tableName: $fieldName, {$fieldName}_id, id_{$fieldName}
	 * 
	 * @param string $tableName
	 * @param string $fieldName
	 */
	final function getFieldNameId($tableName, $fieldName) {
		$fieldName = $this->getTranslator()->removeDbQuotes($fieldName);
		$idFields = array(
			$fieldName,
			$fieldName.'_id',
			'id_'.$fieldName,
		);

		$allFields = $this->getTableFields($tableName);

		foreach($idFields as $fieldNameId) {
			foreach($allFields as $field) {
				if (0 === strcasecmp($fieldNameId, $field->getName())) return $fieldNameId;
			}
		}
	
		throw new InvalidArgumentException("Field $tableName.$fieldName not found");
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
	final function joinTable(pQL_Query_Mediator $mediator, pQL_Query_Builder_Table $joinTable) {
		$builder = $mediator->getBuilder();

		// талбица уже в запросе
		if ($builder->tableExists($joinTable)) return;

		$queryTables = $builder->getFromTables();

		// нет таблиц в запросе - не нужно объединять
		if (count($queryTables) < 1) {
			$builder->addTable($joinTable);
			return;
		}

		// Получаем цепочку таблиц для объединения
		$tables = array();
		foreach ($queryTables as $table) $tables[] = $table->getName();
		$path = $this->getJoinTablesPath($tables, $joinTable->getName());
		if (!$path) throw new pQL_Query_Exception_Join('Cannot join table: '.$joinTable->getName());

		foreach($path as $join) {
			// объединяем таблицы
			$fields1 = reset($join);
			$table1 = $builder->registerTable(key($join));

			$fields2 = next($join);
			$table2 = $builder->registerTable(key($join));
			
			foreach($fields1 as $i=>$field1) {
				$bField1 = $builder->registerField($table1, $field1);
				$bField2 = $builder->registerField($table2, $fields2[$i]);
				$builder->addWhere($bField1,' = ',$bField2);
			}
		}
	}


	const JOIN_INDIRECT_LIMIT = 100000;
	private function getJoinTablesPath($tables, $joinTable) {
		// Связь A-A
		// каждую таблицу из запроса пробуем соединить с joinTable
		foreach($tables as $table) {
			$joinFields = $this->getJoinTableFields($table, $joinTable);
			if ($joinFields) {
				// возращаем рузультат
				return array(
					array(
						$table=>$joinFields[0],
						$joinTable=>$joinFields[1],
					)
				);
			}
		}

		// Связь A-...-Z
		// Ищем сначала вложенность второго уровня A-B-C
		// потом третьего уровня A-B-C-D
		// и так далее
		//
		// не используется рекурсия что бы уменьшить количество связей в результате
		// рекурсия может пойти по "длинному пути"
		$allTables = $this->getTablesCached();
		$childPathes = array(
			array($tables, array()),
		);
		$level = 0;
		// path (путь) - набор объеденяемых с запросом таблиц
		// пока есть какие-либо связи A-B-* и вложенность меньше числа таблиц
		while($childPathes and $level < count($allTables)) {
			// переходим на вложенность ниже
			$level++;
			$parentPathes = $childPathes; // дети становятся родителями
			$childPathes = array();
			$count = 0;

			// для каждого родителя
			foreach($parentPathes as $parentPath) {
				list($tables, $join) = $parentPath;
				
				// каждую таблицу из базы пробуем соединить с таблицей из пути
				foreach($allTables as $noQueryTable) {
					// если таблица уже в пути - пропускам
					if (in_array($noQueryTable, $tables)) continue;
					
					// нет смысла
					if ($joinTable == $noQueryTable) continue;
					
					// пробуем соединить A-B
					foreach($tables as $queryTable) {
						$joinFields = $this->getJoinTableFields($queryTable, $noQueryTable);
						
						// не соединяются - следуюзщий
						if (!$joinFields) continue;

						// соединяюются
						
						$join[] = array(
							$queryTable=>$joinFields[0],
							$noQueryTable=>$joinFields[1],
						);
						
						// Связь A-B-C
						// пробуем содинить "B" с "C"
						$joinFields = $this->getJoinTableFields($noQueryTable, $joinTable);
						
						// получилось - возращаем путь
						if ($joinFields) {
							$join[] = array(
								$noQueryTable=>$joinFields[0],
								$joinTable=>$joinFields[1],
							);
							return $join;
						}
		
						
						// иначе добавляем таблицу "B" в путь
						$path = $tables;
						$path[] = $noQueryTable;
						
						// если слишком много - перкращаем
						$count += count($path);
						if (self::JOIN_INDIRECT_LIMIT < $count) break(3);

						$childPathes[] = array($path, $join);
					}
				}
			}
		}
		return array();
	}


	/**
	 * Возращает список полей по которым возможно объединение таблиц
	 * Если объединение не возможно возращает NULL
	 * 
	 * @param string $tableA
	 * @param string $tableB
	 * @return array(array tableAFields, array tableBFields) | null
	 */
	private function getJoinTableFields($tableA, $tableB) {
		// using REFERENCES
		foreach($this->getForeignKeysCached($tableA) as $key) {
			if (0 === strcasecmp($tableB, $key['table'])) return array($key['from'], $key['to']); 
		}
		foreach($this->getForeignKeysCached($tableB) as $key) {
			if (0 === strcasecmp($tableA, $key['table'])) return array($key['to'], $key['from']);
		}
		
		// using names
		$fieldB = $this->getJoinSecondTableFieldToFirstTable($tableA, $tableB);
		if ($fieldB) return array($this->getTablePrimaryKey($tableA), array($fieldB));

		$fieldA = $this->getJoinSecondTableFieldToFirstTable($tableB, $tableA);
		if ($fieldA) return array(array($fieldA), $this->getTablePrimaryKey($tableB));

		return null;
	}
	
	
	abstract protected function getForeignKeys($table);


	private function getForeignKeysCached($table) {
		$cache = $this->cache("fk:$table");
		if (!$cache->exists()) $cache->set($this->getForeignKeys($table));
		return $cache->get();
	}


	private function getFieldsCached($table) {
		$cache = $this->cache("f:$table");
		if (!$cache->exists()) $cache->set($this->getTableFields($table));
		return $cache->get();	
	}
	
	
	private function getFieldsCachedNames($table) {
		$result = array();
		foreach($this->getFieldsCached($table) as $field) $result[] = $this->getTranslator()->addDbQuotes($field->getName());
		return $result;
	}


	/**
	 * Опередляет поле второй таблицы объединения с первой
	 * @return string | null
	 */
	private function getJoinSecondTableFieldToFirstTable($tableA, $tableB) {
		$tableNameA = $this->getTranslator()->removeDbQuotes($tableA);
		$tableNameA_s = preg_replace(array('#(?<=\w)ies$#', '#(?<=\w)s$#'), array('y', ''), $tableNameA);
		foreach($this->getFieldsCachedNames($tableB) as $fieldB) {
			$fieldBSuffix = preg_replace('#^id_|_id$#', '', $this->getTranslator()->removeDbQuotes($fieldB));
			$cutPos = -strlen($fieldBSuffix);
			$tableASuffix = substr($tableNameA, $cutPos);
			if (0 === strcasecmp($fieldBSuffix, $tableASuffix)) return $fieldB;
			// если таблица в множественном числе
			$tableASuffix_s = substr($tableNameA_s, $cutPos);
			if (0 === strcasecmp($fieldBSuffix, $tableASuffix_s)) return $fieldB;
		}
		return null;
	}
	
	
	final function getQueryPropertiesKeys(pQL_Query_Mediator $mediator, pQL_Query_Builder_Table $table) {
		$result = array();
		$builder = $mediator->getBuilder();
		foreach($this->getFieldsCachedNames($table->getName()) as $fieldName) {
			$field = $builder->registerField($table, $fieldName);
			$num = $builder->getFieldNum($field);
			$result[$num] = $this->fieldToProperty($field->getName());
		}
		return $result;
	}


	abstract protected function getTables();
	
	
	private function getTablesCached() {
		$cache = $this->cache('tables');
		if (!$cache->exists()) $cache->set($this->getTables());
		return $cache->get();
	}


	private function getModelFields($model) {
		$table = $this->modelToTable($model);
		return $this->getFieldsCachedNames($table);
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
		$fields = $this->getFieldsCachedNames($table);
		if (in_array($field, $fields)) return null;

		$tr = $this->getTranslator();
		$field = $tr->removeDbQuotes($field);

		$foreignField = $tr->addDbQuotes("{$field}_id");
		if (in_array($foreignField, $fields)) return $foreignField;

		$foreignField = $tr->addDbQuotes("id_$field");
		if (in_array($foreignField, $fields)) return $foreignField;
		
		return null;
	}
	

	/**
	 * Возращает модель у связанного свойства
	 *
	 * @param  $model
	 * @param  $property
	 * @return string modelName | false
	 */
	private function getObjectPropertyForeignKey($model, $property) {
		// using REFERENCES
		$table = $this->modelToTable($model);
		$field = $this->propertyToField($property);
		foreach($this->getForeignKeysCached($table) as $key) {
			// ключ не комбинированный
			if (1 === count($key['from'])) {
				// на поле $field
				if (0 === strcasecmp(reset($key['from']), $field)) return $key;
			}
		}

		// using {имя}_id or id_{имя}
		$propertyId = $this->getPropertyForeignField($model, $property);
		if (!$propertyId) return false;

		$tr = $this->getTranslator();
		
		$field = $this->propertyToField($property);
		$unquotedField = $tr->removeDbQuotes($field);

		foreach($this->getTablesCached() as $quotedTable) {
			$table = $tr->removeDbQuotes($quotedTable);

			// если таблица заканчивается названием свойства
			// считаем что свойство - объект
			// @TODO добавить проверку совпадения типов
			if (0 === strcasecmp($unquotedField, substr($table, - strlen($unquotedField)))) {
				return array(
					'table'=>$quotedTable,
					'from'=>array($this->propertyToField($propertyId)),
					'to'=>$this->getTablePrimaryKey($table),
				);
			}
		}
		return false;
	}


	private function getObjectPropertyForeignKeyCached($model, $property) {
		$cache = $this->cache("$model $property");
		if (!$cache->exists()) $cache->set($this->getObjectPropertyForeignKey($model, $property));
		return $cache->get();
	}


	/**
	 * Проверяет что свойство является pQL объектом
	 * @param string $model
	 * @param string $property
	 */
	final function isPropertyObject($model, $property) {
		return (bool) $this->getObjectPropertyForeignKeyCached($model, $property);
	}


	/**
	 * Загружает связанный объект
	 * @param pQL_Object $object
	 * @param string $property
	 */
	final function loadObjectProperty(pQL_Object $object, $property) {
		$key = $this->getObjectPropertyForeignKeyCached($object->getModel(), $property);
		$propertyId = $this->fieldToProperty(reset($key['from']));
		
		if ($property == $propertyId) $id = $object->loadProperty($property);
		else $id = $object->get($propertyId);
		
		if (is_null($id)) return null;
		
		$foreignModel = $this->tableToModel($key['table']);
		$foreignProperty = $this->fieldToProperty(reset($key['to']));
		return $this->findByField($foreignModel, $foreignProperty, $id);
	}


	/**
	 * @param pQL_Object $object
	 * @param  $property
	 * @param  $value
	 * @return pQL_Object
	 */
	final function getObjectProperty(pQL_Object $object, $property, $value) {
		$key = $this->getObjectPropertyForeignKeyCached($object->getModel(), $property);
		$foreignModel = $this->tableToModel($key['table']);
		$foreignProperty = $this->fieldToProperty(reset($key['to']));
		return $this->findByField($foreignModel, $foreignProperty, $value);
	}


	final function propertyExists($model, $property) {
		$table = $this->modelToTable($model);
		$field = $this->getTranslator()->removeDbQuotes($this->propertyToField($property));
		foreach($this->getTableFields($table) as $dbField) {
			if ($field == $dbField->getName()) return true;
		}
		return false;
	}
	
	
	final function modelExists($model) {
		$table = $this->modelToTable($model);
		return in_array($table, $this->getTablesCached());
	}
	
	
	final function fieldExists($table, $field) {
		return in_array($field, $this->getFieldsCachedNames($table));
	}
}