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
	
	
	private function cache($key) {
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
	abstract protected function getTableFields($table);


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
			$alias1 = $builder->getTableAlias($table1);
			
			$fields2 = next($join);
			$table2 = $builder->registerTable(key($join));
			$alias2 = $builder->getTableAlias($table2);
			
			foreach($fields1 as $i=>$field1) {
				$expr = $alias1.'.'.$field1;
				$expr .= ' = ';
				$expr .= $alias2.'.'.$fields2[$i];
				$builder->addWhere($expr);
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
		foreach($this->getForeignKeys($tableA) as $key) {
			if (0 === strcasecmp($tableB, $key['table'])) return array($key['from'], $key['to']); 
		}
		foreach($this->getForeignKeys($tableB) as $key) {
			if (0 === strcasecmp($tableA, $key['table'])) return array($key['to'], $key['from']);
		}
		
		// using names
		$fieldB = $this->getJoinSecondTableFieldToFirstTable($tableA, $tableB);
		if ($fieldB) return array(array($this->getTablePrimaryKey($tableA)), array($fieldB));

		$fieldA = $this->getJoinSecondTableFieldToFirstTable($tableB, $tableA);
		if ($fieldA) return array(array($fieldA), array($this->getTablePrimaryKey($tableB)));

		return null;
	}
	
	
	abstract protected function getForeignKeys($table);
	
	
	private function getFieldsCached($table) {
		$cache = $this->cache("f:$table");
		if (!$cache->exists()) $cache->set($this->getTableFields($table));
		return $cache->get();	
	}


	/**
	 * Опередляет поле второй таблицы объединения с первой
	 * @return string | null
	 */
	private function getJoinSecondTableFieldToFirstTable($tableA, $tableB) {
		$tableNameA = $this->getTranslator()->removeDbQuotes($tableA);
		foreach($this->getFieldsCached($tableB) as $fieldB) {
			$fieldBSuffix = preg_replace('#^id_|_id$#', '', $this->getTranslator()->removeDbQuotes($fieldB));
			$tableASuffix = substr($tableNameA, -strlen($fieldBSuffix));
			if (0 === strcasecmp($fieldBSuffix, $tableASuffix)) return $fieldB;
		}
		return null;
	}
	
	
	final function getQueryPropertiesKeys(pQL_Query_Mediator $mediator, pQL_Query_Builder_Table $table) {
		$result = array();
		$builder = $mediator->getBuilder();
		foreach($this->getFieldsCached($table->getName()) as $fieldName) {
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
		return $this->getFieldsCached($table);
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
		$fields = $this->getFieldsCached($table);
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

		foreach($this->getTablesCached() as $quotedTable) {
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