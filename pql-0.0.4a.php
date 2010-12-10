<?php
/**
 * pQL это ORM со следующими возможностями:
 * - ленивая выбока
 * - жадная выборка
 * - цепочки условий
 * - авто-определение foreign key при join
 * - подстановки для IDE
 *
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL {
	static function PDO(PDO $dbh) {
		return new self(pQL_Driver::Factory('PDO', $dbh));
	}


	static function MySQL($resource = null) {
		return new self(pQL_Driver::Factory('MySQL', $resource));
	}


	private $driver;
	private $translator;
	function __construct(pQL_Driver $driver) {
		$this->translator = new pQL_Translator;
		$this->driver = $driver;
		$this->driver->setTranslator($this->translator);
		$this->driver->setPql($this);
	}


	function __destruct() {
		$this->driver = null;
	}


	private $creater;
	/**
	 * Возращает создателя выражений и объектов pQL
	 */
	function creater() {
		if (!$this->creater) $this->creater = new pQL_Creater($this);
		return $this->creater;
	}


	/**
	 * Возращает используемый драйвер pQL
	 */
	function driver() {
		return $this->driver;
	}


	/**
	 * Устанавливает/возращает префикс у таблиц
	 * @param string $newPrefix
	 */
	function tablePrefix($newPrefix = null) {
		if (is_null($newPrefix)) return $this->translator->getTablePrefix();
		$this->translator->setTablePrefix($newPrefix);
		return $this;
	}
	

	/**
	 * Устанавливает правила преобразования имен таблиц и имен классов; имен полей и свойств
	 * @param pQL_Coding_Interface $coding
	 */
	function coding(pQL_Coding_Interface $coding) {
		$this->tableCoding($coding);
		$this->fieldCoding($coding);
		return $this;
	}
	
	
	/**
	 * Устанавливает правила преобразования имен таблиц и имен классов
	 * @param pQL_Coding_Interface $coding
	 */
	function tableCoding(pQL_Coding_Interface $coding) {
		$this->translator->setTableCoding($coding);
		return $this;
	}


	/**
	 * Устанавливает правила преобразования имен полей и свойств
	 * @param pQL_Coding_Interface $coding
	 */
	function fieldCoding(pQL_Coding_Interface $coding) {
		$this->translator->setFieldCoding($coding);
		return $this;
	}
	
	
	function objectDefinder($definder = null) {
		// get
		if (is_null($definder)) return $this->driver()->getObjectDefinder();

		// set
		$this->driver()->setObjectDefinder($definder);
		return $this;
	}


	function className($newClassName = null) {
		// get
		if (is_null($newClassName)) {
			$definer = $this->driver()->getObjectDefinder();
			if ($definer instanceof pQL_Object_Definer_ClassName) return $definer->getClassName();
			return null;
		}

		// set
		$this->objectDefinder(new pQL_Object_Definer_ClassName($newClassName));

		return $this;
	}
}


/**
 * Интерфейс для преобразования стандартов кодирования между базой данных и программой
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
interface pQL_Coding_Interface {
	function toDB($string);
	function fromDb($string);
}


/**
 * Пустые правила преобразования
 * (Без преобразований)
 * 
 * @see Null-pattern
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Coding_Null implements pQL_Coding_Interface {
	function toDB($string) {
		return $string;
	}


	function fromDb($string) {
		return $string;
	}
}


/**
 * Типичные правила преобразования
 * В базе слова разделяются через символ подчеркивания "_", в коде CamelCase
 * 
 * @see http://en.wikipedia.org/wiki/CamelCase
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Coding_Typical implements pQL_Coding_Interface {
	function toDB($string) {
		return strtolower(preg_replace('#(.)([A-Z])#ue', '"$1_$2"', $string));
	}


	function fromDb($string) {
		return preg_replace('#_(\w)#ue', 'strtoupper("$1")', $string);
	}
}


/**
 * Создатель pQL выражений и объектов
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Creater {
	function __construct(pQl $pql) {
		$this->pQL = $pql;
	}


	private $pQL;


	function __call($class, $arguments) {
		// find by pk
		if ($arguments and $arguments[0]) {
			return $this->pQL->driver()->findByPk($class, $arguments[0]);
		}

		// new object
		return $this->pQL->driver()->getObject($class);
	}


	function __get($key) {
		$q = new pQL_Query($this->pQL->driver());
		return $q->$key;
	}
}


/**
 * Абстрактный драйвер pQL 
 * @author Ti
 * @package pQL
 * @version 0.0.4a
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


/**
 * MySQL драйвер для pQL
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Driver_MySQL extends pQL_Driver {
	private $db;
	function __construct($db = null) {
		if (!function_exists('mysql_query')) throw new pQL_Exception('MySQL extension not loaded!');
		if (!is_null($db) and !is_resource($db)) throw new InvalidArgumentException('Invalid db connection');
		$this->db = $db;
	}


	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}


	private function query($query) {
		$result = is_null($this->db) ? mysql_query($query) : mysql_query($query, $this->db);
		if (!$result) throw new pQL_Driver_MySQL_QueryException(mysql_error(), mysql_errno(), $query);
		return $result;
	}


	function getToStringField($class) {
		$table = $this->getTranslator()->modelToTable($class);
		$result = null;
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_assoc($Q)) {
			$isString = preg_match('#^(text|char|varchar)#', $column['Type']);
			if ($isString or is_nulL($result)) {
				$result = $column['Field'];
				if ($isString) break;
			}
		}
		if ($result) return $this->getTranslator()->fieldToProperty($result);
		return $result;
	}


	protected function getTablePrimaryKey($table) {
		$result = null;
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_assoc($Q)) {
			$isPK = 'PRI' == $column['Key'];
			if ($isPK) { //  or is_nulL($result)
				$result = $column['Field'];
				if ($isPK) break;
			}
		}
		if ($result) return $this->getTranslator()->addDbQuotes($result);
		return $result;
	}


	function getTableFields($table) {
		$result = array();
		$Q = $this->query("SHOW COLUMNS FROM $table");
		while($column = mysql_fetch_row($Q)) $result[] = $this->getTranslator()->addDbQuotes(reset($column));
		return $result;
	}
	
	
	private function quote($value) {
		if (is_null($value)) return 'NULL';
		if (is_null($this->db)) return '"'.mysql_real_escape_string($value).'"';
		return '"'.mysql_real_escape_string($value, $this->db).'"';
	}

	
	function findByPk($class, $value) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$qValue = $this->quote($value);
		$Q = $this->query("SELECT * FROM $table WHERE $pk = $qValue");
		$R = mysql_fetch_assoc($Q);
		$properties = array();
		foreach($R as $field=>$value) $properties[$tr->fieldToProperty($field)] = $value;
		return $this->getObject($class, $properties);
	}


	protected function updateByPk($table, $fields, $values, $pkValue) {
		$pk = $this->getTablePrimaryKey($table);
		$sql = "UPDATE $table SET ";
		foreach($fields as $i=>$field) {
			if ($i) $sql .= ', ';
			$sql .= "$field = ".$this->quote($values[$i]);
		}
		$qPkValue = $this->quote($pkValue);
		$sql .= " WHERE $pk = $qPkValue LIMIT 1";
		$this->query($sql);
	}


	protected function insert($table, $fields, $values) {
		if (!$fields) {
			$fields = array($this->getTablePrimaryKey($table));
			$values = array(null);
		}

		$sql = "INSERT INTO $table(".implode(',', $fields).") VALUES(";
		foreach($values as $i=>$value) {
			if ($i) $sql .= ', ';
			$sql .= $this->quote($value);
		} 
		$sql .= ")";
		
		$this->query($sql);

		if (is_null($this->db)) return mysql_insert_id();
		return mysql_insert_id($this->db);
	}
	
	
	function deleteByPk($table, $value) {
		$pk = $this->getTablePrimaryKey($table);
		$pkValue = $this->quote($value);
		$this->query("DELETE FROM $table WHERE $pk = $pkValue");
	}


	function getQueryHandler(pQL_Query_Builder $builder) {
		return $this->query($builder->getSQL($this));
	}


	function getQueryIterator(pQL_Query_Mediator $mediator) {
		$handle = $mediator->getQueryHandler($this);
		return new pQL_Driver_MySQL_QueryIterator($handle);
	}


	function getCount(pQL_Query_Mediator $mediator) {
		return count($this->getQueryIterator($mediator));
	}


	function getIsNullExpr($partSql) {
		return "$partSql IS NULL";
	}
	

	function getNotNullExpr($expr) {
		return "$expr IS NOT NULL";
	}



	function getParam($val) {
		return $this->quote($val);
	}
	
	protected function getTables() {
		$query = $this->query("SHOW TABLES");
		$result = array();
		while($row = mysql_fetch_row($query)) $result[] = $this->getTranslator()->addDbQuotes(reset($row));
		return $result;
	}
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
class pQL_Driver_MySQL_QueryException extends pQL_Exception {
	private $query;


	function __construct($message, $code, $query) {
		parent::__construct($message, $code);
		$this->query = $query;
	}


	function getQuery() {
		return $this->query;
	}
}


/**
 * Итератор SELECT запроса для MySQL драйвера
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
class pQL_Driver_MySQL_QueryIterator implements SeekableIterator, Countable {
	private $query;
	private $current;
	private $key;

	function __construct($query) {
		if (!is_resource($query)) throw new InvalidArgumentException('Invalid resource');
		$this->query = $query;
	}


	function current() {
		return $this->current;
	}


	function next() {
		$this->current = mysql_fetch_row($this->query);
		$this->key++;
	}


	function key() {
		return $this->key;
	}


	function valid() {
		return false !== $this->current;
	}


	function rewind() {
		// перематываем на начало
		if (!is_null($this->key)) $this->seek(0);

		$this->key = -1;
		$this->next();
	}


	function seek($position) {
		mysql_data_seek($this->query, $position);
		$this->key = $position;
	}


	function count() {
		return mysql_num_rows($this->query);
	}
}


/**
 * Абстрактный PDO драйвер для pQL
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
abstract class pQL_Driver_PDO extends pQL_Driver {
	private $dbh;
	function __construct(PDO $dbh) {
		$this->dbh = $dbh;
	}


	/**
	 * return PDO
	 */
	protected function getDbh() {
		return $this->dbh;
	}


	function findByPk($class, $value) {
		$tr = $this->getTranslator();
		$table = $tr->modelToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$sth = $this->getDbh()->prepare("SELECT * FROM $table WHERE $pk = :value");
		$sth->bindValue(':value', $value);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute();
		$properties = array();
		foreach($sth->fetch() as $field=>$value) $properties[$tr->fieldToProperty($field)] = $value;
		return $this->getObject($class, $properties);
	}


	final protected function updateByPk($table, $fields, $values, $pkValue) {
		$pk = $this->getTablePrimaryKey($table);
		$sth = $this->getDbh()->prepare("UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE $pk = :pk LIMIT 1");
		foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
		$sth->bindValue(':pk', $pkValue);
		$sth->execute();
	}
	
	
	final protected function insert($table, $fields, $values) {
		if ($fields) {
			$sth = $this->getDbh()->prepare("INSERT INTO $table(".implode(',', $fields).") VALUES(?".str_repeat(', ?', count($fields)-1).")");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->execute();
		}
		else {
			$pk = $this->getTablePrimaryKey($table);
			$this->getDbh()->exec("INSERT INTO $table($pk) VALUES(NULL)");
		}
		return $this->getDbh()->lastInsertId();
	}
	
	
	function deleteByPk($table, $value) {
		$pk = $this->getTablePrimaryKey($table);
		$sth = $this->getDbh()->prepare("DELETE FROM $table WHERE $pk = :pk");
		$sth->bindValue(':pk', $value);
		$sth->execute();
	}


	final function getQueryHandler(pQL_Query_Builder $builder) {
		return $this->getDbh()->query($builder->getSQL($this));
	}


	final function getQueryIterator(pQL_Query_Mediator $mediator) {
		$sth = $mediator->getQueryHandler($this);
		$sth->setFetchMode(PDO::FETCH_NUM);
		return new IteratorIterator($sth);
	}


	final function getParam($val) {
		return $this->getDbh()->quote($val);
	}
}


/**
 * MySQL PDO драйвер для pQL
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Driver_PDO_MySQL extends pQL_Driver_PDO {
	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}


	function getToStringField($class) {
		$table = $this->modelToTable($class);
		$result = null;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isString = preg_match('#^(text|char|varchar)#', $column['Type']);
			if ($isString or is_nulL($result)) {
				$result = $column['Field'];
				if ($isString) break;
			}
		}
		if ($result) return $this->fieldToProperty($result);
		return $result;
	}


	protected function getTablePrimaryKey($table) {
		$result = null;
		foreach($this->getDbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isPK = 'PRI' == $column['Key'];
			if ($isPK) { //  or is_nulL($result)
				$result = $column['Field'];
				if ($isPK) break;
			}
		}
		if ($result) return $this->getTranslator()->addDbQuotes($result);
		return $result;
	}


	function getTableFields($table) {
		$q = $this->getDbh()->query("SHOW COLUMNS FROM $table");
		$q->setFetchMode(PDO::FETCH_COLUMN, 0);
		$result = array();
		foreach ($q as $field) $result[] = $this->getTranslator()->addDbQuotes($field);
		return $result;
	}


	function getCount(pQL_Query_Mediator $queryMediator) {
		return $queryMediator->getQueryHandler($this)->rowCount();
	}


	function getIsNullExpr($partSql) {
		return "$partSql IS NULL";
	}
	

	function getNotNullExpr($expr) {
		return "$expr IS NOT NULL";
	}
	
	
	protected function getTables() {
		$result = array();
		$sth = $this->getDbh()->query("SHOW TABLES");
		$sth->setFetchMode(PDO::FETCH_COLUMN, 0);
		foreach($sth as $table) $result[] = $this->getTranslator()->addDbQuotes($table);
		return $result;
	}
}


/**
 * SQLite PDO драйвер для pQL
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Driver_PDO_SQLite extends pQL_Driver_PDO {
	function getToStringField($class) {
		$table = $this->getTranslator()->modelToTable($class);
		$result = null;
		foreach($this->getDbh()->query("PRAGMA table_info($table)", PDO::FETCH_ASSOC) as $column) {
			$isString = preg_match('#^(text|char|varchar)#i', $column['type']);
			if ($isString or is_nulL($result)) {
				$result = $column['name'];
				if ($isString) break;
			}
		}
		if ($result) return $this->getTranslator()->fieldToProperty($result);
		return $result;
	}


	protected function getTablePrimaryKey($table) {
		$result = null;
		foreach($this->getDbh()->query("PRAGMA table_info($table)", PDO::FETCH_ASSOC) as $column) {
			$isPK = (bool) $column['pk'];
			if ($isPK) { // or is_nulL($result)
				$result = $column['name'];
				if ($isPK) break;
			}
		}
		return $result;
	}


	function getTableFields($table) {
		$q = $this->getDbh()->query("PRAGMA table_info($table)");
		$q->setFetchMode(PDO::FETCH_COLUMN, 1);
		$result = array();
		foreach ($q as $field) $result[] = $this->getTranslator()->addDbQuotes($field);
		return $result;
	}


	function getCount(pQL_Query_Mediator $mediator) {
		$mediator->setup($this);
		$sql = 'SELECT COUNT(*)'.$mediator->getBuilder()->getSQLSuffix($this);
		return $this->getDbh()->query($sql)->fetchColumn(0);
	}
	
	
	protected function getTables() {
		$result = array();
		$sth = $this->getDbh()->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'");
		$sth->setFetchMode(PDO::FETCH_COLUMN, 0);
		foreach($sth as $table) $result[] = $this->getTranslator()->addDbQuotes($table);
		return $result;
	}
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
class pQL_Exception extends Exception {}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Exception_PrimaryKeyNotExists extends pQL_Exception {}


/**
 * Базовый класс объекта pQL
 * 
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
abstract class pQL_Object {
	function __construct(pQL $pQL, $properties) {
		$this->properties = $properties;
		$this->pQL = $pQL;
	}
	
	
	abstract function getModel();


	protected function getToStringField() {
		return $this->getDriver()->getToStringField($this->getModel());
	}


	function save() {
		$result = $this->getDriver()->save($this->getModel(), $this->newProperties, $this->properties);
		$this->properties = array_merge($this->properties, $result);
		$this->newProperties = array();
		return $this;
	}
	
	
	function delete() {
		$newProperties = $this->getDriver()->delete($this->getModel(), $this->newProperties, $this->properties);
		$this->properties = array();
		$this->newProperties = $newProperties;
		return $this;
	}


	private $pQL;


	final protected function getPQL() {
		return $this->pQL;
	}
	
	
	final protected function getDriver() { 
		return $this->getPQL()->driver();
	}


	private $properties = array();
	private $newProperties = array();
	function set($property, $value) {
		$this->newProperties[$property] = $value;
		return $this;
	}


	function get($property) {
		$found = false;

		// ищем в новых свойствах
		if (array_key_exists($property, $this->newProperties)) {
			$result = $this->newProperties[$property];
			$found = true;
		}
		// и в текущих
		elseif (array_key_exists($property, $this->properties)) {
			$result = $this->properties[$property];
			$found = true;
		}

		// если это связанный объект - получаем его
		if (!($found and is_object($result)) and $this->isPropertyObject($property)) {
			// если найдено свойство, значит id определен
			if ($found) {
				$result = $this->getDriver()->getObjectProperty($this, $property, $result);
			} 
			// иначе нужно его загрузить
			else {
				$result = $this->getDriver()->loadObjectProperty($this, $property);
			}
			$found = is_object($result);
		}

		if (!$found) throw new pQL_Object_Exception_PropertyNotExists("'".$this->getModel().".$property' not found");

		return $result;
	}


	private function isPropertyObject($property) {
		return $this->getDriver()->isObjectProperty($this->getModel(), $property);
	}


	final function __get($property) {
		return $this->get($property);
	}


	final function __set($property, $value) {
		return $this->set($property, $value);
	}
	
	
	function __toString() {
		return (string) $this->get($this->getToStringField());
	}
}


/**
 * Реализация простого объекта pQL в котором имя модели соответствует имени класса
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
class pQL_Object_Classname extends pQL_Object {
	function getModel() {
		return get_class($this);
	}


	private $model;
}


final class pQL_Object_Definer_ClassName implements pQL_Object_Definer_Interface {
	private $className;
	function __construct($className = 'pQL_Object_Model') {
		$this->setClassName($className);
	}
	
	
	function setClassName($className) {
		$this->assertClassName($className);
		$this->className = $className;
		
		
		
	}
	
	
	/**
	 * Метод проверяет наследование классом $className класса pQL_Object_Model
	 */
	private function assertClassName($className) {
		if (!class_exists($className)) throw new pQL_Object_Definer_Exception("Class '$className' not found");

		$validBaseClass = 'pQL_Object_Model';
		if (0 === strcasecmp($className, $validBaseClass)) return true;

		// для PHP 5.0.3 и выше испольуем is_subclass_of
		if (version_compare(PHP_VERSION, '5.0.3', '>=')) {
			if (is_subclass_of($className, $validBaseClass)) return true;
		}
		// для ранних версий PHP
		else {
			// сравниваем каждого родителя класса
			do {
				$className = get_parent_class($className);

				if (0 === strcasecmp($className, $validBaseClass)) {
					// УРА!
					return true;
				}
			}
			while($className);
		}

		// ошибка если класс не наследует pQL_Object_Model
		throw new pQL_Object_Definer_Exception("Class '$className' is not subclass of $validBaseClass");
	}
	
	
	function getClassName() {
		return $this->className;
	}


	public function getObject(pQL $pQL, $className, $properties) {
		return new $this->className($pQL, $properties, $className);
	}
}


class pQL_Object_Definer_Exception extends pQL_Exception {
	
}


interface pQL_Object_Definer_Interface {
	function getObject(pQL $pQL, $className, $properties);
}


class pQL_Object_Exception_PropertyNotExists extends pQL_Exception {
	
}


/**
 * Реализация простого объекта pQL в котором имя модели хранится в объекте
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
class pQL_Object_Model extends pQL_Object {
	function __construct(pQL $pQL, $properties, $model) {
		$this->model = $model;
		parent::__construct($pQL, $properties);
	}


	final function getModel() {
		return $this->model;
	}


	private $model;
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
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
}


/**
 * Построитель запросов
 * 
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Builder {
	private $registeredTables = array();


	/**
	 * Регистрирует талбицу (но не добавлет в выбоку!)
	 * 
	 * @param pQL_Query_Builder_Table $tableName
	 * @return pQL_Query_Builder_Table зарегистрированна таблица
	 */
	function registerTable($tableName) {
		if (isset($this->registeredTables[$tableName])) {
			$bTable = $this->registeredTables[$tableName];
		}
		else {
			$bTable = new pQL_Query_Builder_Table($tableName);
			$this->registeredTables[$tableName] = $bTable;
		}
		return $bTable;
	}


	private $registeredFields = array();


	/**
	 * Регистрирует поле (но не добавлет в выбоку!)
	 * 
	 * @param pQL_Query_Builder_Table $rTable
	 * @param string $fieldName
	 * @return pQL_Query_Builder_Field зарегистрированное поле
	 */
	function registerField(pQL_Query_Builder_Table $bTable, $fieldName) {
		if (isset($this->registeredFields[$bTable->getName()][$fieldName])) {
			$bField = $this->registeredFields[$bTable->getName()][$fieldName];
		}
		else {
			$bField = $this->registeredFields[$bTable->getName()][$fieldName] = new pQL_Query_Builder_Field($bTable, $fieldName);
		}
		return $bField;
	}


	private $tables = array();


	/**
	 * Возращает номер таблицы в запросе
	 * 
	 * @param pQL_Query_Builder_Table $table
	 * @return int
	 */
	private function getTableNum(pQL_Query_Builder_Table $bTable) {
		$index = array_search($bTable, $this->tables);
		if (false === $index) $index = array_push($this->tables, $bTable) - 1;
		return $index;
	}


	/**
	 * Добавляет таблицу в запрос
	 */
	function addTable(pQL_Query_Builder_Table $bTable) {
		$this->getTableNum($bTable);
	}


	/**
	 * Проверяет есть ли таблица в запросе
	 * 
	 * @param pQL_Query_Builder_Table $bTable
	 * @return bool
	 */
	function tableExists(pQL_Query_Builder_Table $bTable) {
		return false !== array_search($bTable, $this->tables);
	}
	
	
	/**
	 * Возращает список талбиц которые будут добавлены в FROM или JOIN выражения
	 * 
	 * @return array
	 */
	function getFromTables() {
		return $this->tables;
	}


	private $fields = array();


	/**
	 * Возращает номер поля в запросе
	 * @param pQL_Query_Builder_Table $bTable
	 * @param pQL_Query_Builder_Field $bField
	 * @return int
	 */
	function getFieldNum(pQL_Query_Builder_Field $bField) {
		$index = array_search($bField, $this->fields);
		if (false === $index) $index = array_push($this->fields, $bField) - 1;
		return $index;
	}


	private function getSQLFields() {
		$result = '';
		foreach($this->fields as $fieldNum=>$rField) {
			if ($fieldNum) $result .= ', ';
			$result .= $this->getTableAlias($rField->getTable());
			$result .= '.';
			$result .= $rField->getName();
			
			// В именнованных алиасах пока нет необходимости
			#$result .= ' AS ';
			#$result .= $this->getFieldAlias($rField);
		}
		return $result;
	}


	private function getFieldAlias(pQL_Query_Builder_Field $bField) {
		return 'f'.$this->getFieldNum($bField);
	}


	function getTableAlias(pQL_Query_Builder_Table $bTable) {
		return 't'.$this->getTableNum($bTable);
	}


	/**
	 * @return string выражение FROM включая все JOIN
	 */
	private function getSQLFrom() {
		$result = '';
		foreach($this->tables as $tableNum=>$rTable) {
			if ($tableNum) $result .= ', ';
			$result .= $rTable->getName();
			$result .= ' AS ';
			$result .= $this->getTableAlias($rTable);
		}
		return ' FROM '.$result;
	}


	private $where = '';
	function addWhere($expression) {
		if ($this->where) $this->where .= ' AND ';
		else $this->where .= ' WHERE ';

		$this->where .= $expression;

		return $this;
	}


	private $limit = 0;
	function setLimit($limit) {
		$this->limit = (int) $limit;
	}
	
	
	function getLimit() {
		return $this->limit;
	}
	

	private $offset = 0;
	function setOffset($offset) {
		$this->offset = (int) $offset;
	}
	
	
	private $orderBy = '';
	function addOrder($expr) {
		$this->orderBy .= $this->orderBy ? ', ' : ' ORDER BY ';
		$this->orderBy .= $expr;
	}
	
	
	private function getLimitExpr(pQL_Driver $driver) {
		return rtrim(' '.$driver->getLimitExpr($this->offset, $this->limit));
	}


	/**
	 * Возращает часть запроса, начиная с FROM до ORDER BY
	 */
	function getSQLSuffix(pQL_Driver $driver) {
		return $this->getSQLFrom().$this->where.$this->getLimitExpr($driver);
	}


	function getSQL(pQL_Driver $driver) {
		$sql = 'SELECT '.$this->getSQLFields().$this->getSQLSuffix($driver).$this->orderBy;
		return $sql;
	}
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Builder_Field {
	private $table;
	private $name;


	function __construct(pQL_Query_Builder_Table $table, $name) {
		$this->table = $table;
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}


	function getTable() {
		return $this->table;
	}
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Builder_Table {
	private $name;


	function __construct($name) {
		$this->name = $name;
	}
	
	
	function getName() {
		return $this->name;
	}
}


/**
 * Итератор pQL запроса
 * 
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Iterator implements Iterator {
	private $driver;
	function __construct(pQL_Driver $driver) {
		$this->driver = $driver;
	}
	
	
	private $iterator;
	function setSelectIterator(Iterator $iterator) {
		$this->iterator = $iterator;
	}
	
	
	/**
	 * Номер поля выборки, используемое в качестве ключей итератора
	 * @var int
	 */
	private $keyIndex;
	function setKeyIndex($index) {
		$this->keyIndex = $index;
	}
	

	/**
	 * Номер поля в выборке, используемое в качестве значений итератора
	 * @var int
	 */
	private $valueIndex;
	function setValueIndex($index) {
		$this->valueIndex = $index;
		$this->valueClass = null;
	}


	/**
	 * Класс используемый в качестве значений итератора
	 * @var pQL_Query_Iterator_Class
	 */
	private $valueClass;
	function setValueClass($className, $keys) {
		$this->valueClass = new pQL_Query_Iterator_Class($className, $keys);
		$this->valueIndex = null;
	}
	
	
	private $bindedObjectClasses = array();
	function bindValueObject(&$var, $className, $keys) {
		$this->bindedObjectClasses[] = array(&$var, $className, $keys);
	}
	
	
	private $bindedIndexes = array();
	function bindValueIndex(&$var, $index) {
		$this->bindedIndexes[] = array(&$var, $index);
	}
	
	
	private function setBindValues($current) {
		foreach($this->bindedIndexes as &$bind) {
			$bind[0] = $current[$bind[1]];
		}
		foreach($this->bindedObjectClasses as &$bind) {
			$bind[0] = $this->getObject($current, $bind[1], $bind[2]);
		}
	}
	
	
	private function getObject($current, $className, $inds) {
		$properties = array();
		foreach($inds as $i=>$name) $properties[$name] = $current[$i];
		return $this->driver->getObject($className, $properties);
	}


	function current() {
		$current = $this->iterator->current();
		$this->setBindValues($current);
		if (is_null($this->valueIndex)) {
			return $this->getObject($current, $this->valueClass->getName(), $this->valueClass->getIndexes());
		}
		else {
			return $current[$this->valueIndex];
		}
	}


	function next() {
		return $this->iterator->next();
	}


	function key() {
		if (is_null($this->keyIndex)) return $this->iterator->key();
		$current = $this->iterator->current();
		return $current[$this->keyIndex];
	}


	function valid() {
		return $this->iterator->valid();
	}


	function rewind() {
		return $this->iterator->rewind();
	}
}


/**
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Iterator_Class {
	/**
	 * Класс итератора выборки
	 * @param string $name имя класса
	 * @param array $indexes хеш: ключи - номера полей из результата выборки, заначения - имена свойств объекта
	 */
	function __construct($name, $indexes) {
		$this->name = $name;
		$this->indexes = $indexes;
	}


	private $name;
	private $indexes;


	function getName() {
		return $this->name;
	}


	function getIndexes() {
		return $this->indexes;
	}
}


/**
 * Посредник между pQL_Query и pQL_Driver
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Query_Mediator {
	/**
	 * @var pQL_Query_Builder
	 */
	private $builder;
	function __construct(pQL_Query_Builder $builder) {
		$this->builder = $builder;
	}
	
	
	function __clone() {
		$this->builder = clone $this->builder;
	}


	private $firstTable;
	function setTable(pQL_Query_Builder_Table $table) {
		if (!$this->firstTable) $this->firstTable = $table;
	}


	private $lastField;
	function setField(pQL_Query_Builder_Field $field) {
		$this->lastField = $field;
	}


	function getBuilder() {
		return $this->builder;
	}


	function cleanResult() {
		$this->count = null;
		$this->iterator = null;
		$this->queryHandler = null;
		$this->driverIterator = null;
		$this->lastField = null;
	}


	/**
	 * @var pQL_Query_Builder_Field
	 */
	private $keyField;
	/**
	 * Устанавливает поле в качестве ключей итератора
	 * @param pQL_Query_Builder_Field $field
	 */
	function setKeyField(pQL_Query_Builder_Field $field) {
		$this->keyField = $field;
	}


	private $valueField;
	/**
	 * Устанавливает поле в качестве заначений итератора
	 * @param pQL_Query_Builder_Field $field
	 */
	function setFieldValue(pQL_Query_Builder_Field $field) {
		$this->valueTable = null;
		$this->valueField = $field;
	}
	
	
	private $valueTable;
	/**
	 * Устанавливает талбицу в качестве заначений итератора
	 * @param pQL_Query_Builder_Table $table
	 */
	function setTableValue(pQL_Query_Builder_Table $table) {
		$this->valueField = null;
		$this->valueTable = $table;
	}


	/**
	 * @var pQL_Query_Iterator
	 */
	private $iterator;
	
	
	function setup(pQL_Driver $driver) {
		if (is_null($this->iterator)) {
			$this->iterator = new pQL_Query_Iterator($driver);
			$this->setupIteratorKeyIndex();
			$this->setupValueIterator($driver);
			$this->setupBindTables($driver);
			$this->setupBindFields($driver);
		}
		return $this->iterator;
	}


	function getIterator(pQL_Driver $driver) {
		$this->setup($driver);
		$this->iterator->setSelectIterator($this->getDriverIterator($driver));
		return $this->iterator;
	}


	/**
	 * устанавливаем ключи в итератор
	 */
	private function setupIteratorKeyIndex() {
		if ($this->keyField) {
			$index = $this->builder->getFieldNum($this->keyField);
			$this->iterator->setKeyIndex($index);
		}
	}


	/**
	 * определяем значения в итератор
	 */
	private function setupValueIterator(pQL_Driver $driver) {
		$field = $this->valueField ? $this->valueField : $this->lastField;
		if ($field) {
			$driver->joinTable($this, $field->getTable());
			$index = $this->builder->getFieldNum($field);
			$this->iterator->setValueIndex($index);
			return;
		}

		$table = $this->valueTable ? $this->valueTable : $this->firstTable;
		$driver->joinTable($this, $table);
		$className = $driver->tableToModel($table->getName());
		$keys = $driver->getQueryPropertiesKeys($this, $table);
		$this->iterator->setValueClass($className, $keys);
	}
	
	
	private function setupBindFields(pQL_Driver $driver) {
		foreach($this->bindedFields as &$bind) {
			$var = &$bind[0];
			$field = $bind[1];
			
			$driver->joinTable($this, $field->getTable());
			
			$num = $this->builder->getFieldNum($field);
			$this->iterator->bindValueIndex($var, $num);

			unset($field, $var);
		}
	}
	
	
	private function setupBindTables(pQL_Driver $driver) {
		foreach($this->bindedTables as &$bind) {
			$var = &$bind[0];
			$table = $bind[1];

			$driver->joinTable($this, $table);
			$className = $driver->tableToModel($table->getName());

			$keys = $driver->getQueryPropertiesKeys($this, $table);
			$this->iterator->bindValueObject($var, $className, $keys);

			unset($table, $var);
		}
	}
	
	
	private $bindedTables = array();
	function bindTable(&$var, pQL_Query_Builder_Table $table) {
		$this->bindedTables[] = array(&$var, $table);
	}
	
	
	private $bindedFields = array();
	function bindField(&$var, pQL_Query_Builder_Field $field) {
		$this->bindedFields[] = array(&$var, $field);
	}
	
	
	function unbind(&$var) {
		$varValue = $var;
		$var = 'A';
		foreach(array('bindedTables', 'bindedFields') as $type) {
			foreach($this->$type as $i=>&$bind) {
				// определяем, является ли ссылкой на $var
				if ($bind[0] !== $var) continue;

				$bindValue = $bind[0];
				$bind[0] = 'B';
				if ($bind[0] !== $var) {
					$bind[0] = $bindValue;
					continue;
				}

				// удаляем ссылку
				unset($this->{$type}[$i]);
			}
		}
		$var = $varValue;
	}
	

	/**
	 * Низкоуровневый итератор запроса
	 * @var Iterator
	 */
	private $driverIterator;


	private function setDriverIterator(Iterator $driverIterator) {
		$this->driverIterator = $driverIterator;
	}
	
	
	private $queryHandler;


	function getQueryHandler(pQL_Driver $driver) {
		if (is_null($this->queryHandler)) {
			$this->setup($driver);
			$this->queryHandler = $driver->getQueryHandler($this->getBuilder());
		}
		return $this->queryHandler;
	}


	function getDriverIterator(pQL_Driver $driver) {
		if ($this->driverIterator) {
			if ($this->driverIterator instanceof SeekableIterator) {
			 	$this->driverIterator->seek(0);
			}
			else {
				// если драйвер не поддерживает несколько итерация одного запроса
				// пересоздаем запрос
				$this->driverIterator = null;
				$this->queryHandler = null;
			}
		}

		if (is_null($this->driverIterator)) {
			$driverIterator = $driver->getQueryIterator($this);
			$this->setDriverIterator($driverIterator);
		}
		return $this->driverIterator;
	}


	private $count;
	function getCount(pQL_Driver $driver) {
		if (is_null($this->count)) $this->count = $driver->getCount($this);
		return $this->count;
	}
}


/**
 * Преобразователь имен PHP <> DB
 * @author Ti
 * @package pQL
 * @version 0.0.4a
 */
final class pQL_Translator {
	private $tablePrefix = '';
	
	
	private $tableCoding;
	private function getTableCoding() {
		if (!$this->tableCoding) $this->tableCoding = new pQL_Coding_Null;
		return $this->tableCoding;
	}
	
	
	function setTableCoding(pQL_Coding_Interface $coding) {
		$this->tableCoding = $coding;
	}
	
	
	private $fieldCoding;
	private function getFieldCoding() {
		if (!$this->fieldCoding) $this->fieldCoding = new pQL_Coding_Null;
		return $this->fieldCoding;
	} 


	function setFieldCoding(pQL_Coding_Interface $coding) {
		$this->fieldCoding = $coding;
	}
	
	
	function getTablePrefix() {
		return $this->tablePrefix;
	}
	
	
	function setTablePrefix($newPrefix) {
		$this->tablePrefix = $newPrefix;
	}
	
	
	function tableToModel($table) {
		$result = $this->removeDbQuotes($table);
		if (0 === strcasecmp(substr($result, 0, strlen($this->tablePrefix)), $this->tablePrefix)) {
			$result = substr($result, strlen($this->tablePrefix));
		}
		return $this->getTableCoding()->fromDb($result);
	}
	 

	function modelToTable($className, $addQuotes = true) {
		$result = $this->getTableCoding()->toDb($className);
		$result = $this->tablePrefix.$result;
		if ($addQuotes) $result = $this->addDbQuotes($result);
		return $result;
	}
	
	
	function propertyToField($property) {
		$result = $this->getFieldCoding()->toDb($property);
		$result = $this->addDbQuotes($result);
		return $result;
	}


	function fieldToProperty($field) {
		$result = $this->removeDbQuotes($field);
		$result = $this->getFieldCoding()->fromDb($result);
		return $result;
	}


	function addDbQuotes($name) {
		return $this->dbQuote.$name.$this->dbQuote;
	}
	
	
	function removeDbQuotes($name) {
		return '' === $this->dbQuote ? $name : trim($name, $this->dbQuote);
	}

	
	private $dbQuote = '';
	function setDbQuote($newQuote)  {
		$this->dbQuote = $newQuote;
	}
}

