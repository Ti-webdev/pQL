<?php
/**
 * Абстрактный PDO драйвер для pQL
 * @author Ti
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


	abstract protected function getTablePrimaryKey($table);


	function findByPk($class, $value) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$sth = $this->getDbh()->prepare("SELECT * FROM $table WHERE $pk = :value");
		$sth->bindValue(':value', $value);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute();
		$properties = array();
		foreach($sth->fetch() as $field=>$value) $properties[$tr->fieldToProperty($field)] = $value;
		return $this->getTranslator()->getObject($class, $properties);
	}
	
	
	private function isPqlObject($value) {
		return is_object($value) and $value instanceof pQL_Object;
	}
	
	
	function getPqlId(pQL_Object $object) {
		$tr = $this->getTranslator();
		$foreignTable = $tr->classToTable($object->getClass());
		$foreignKey = $this->getTablePrimaryKey($foreignTable);
		return $object->get($tr->fieldToProperty($foreignKey));
	}


	function save($class, $newProperties, $oldProperties) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$pkProperty = $tr->fieldToProperty($pk);
		$values = $fields = array();
		$isUpdate = isset($oldProperties[$pkProperty]);
		foreach($newProperties as $key=>$value) {
			$field = $tr->propertyToField($key);
			// foreignTalbe
			if ($this->isPqlObject($value)) {
				$value = $this->getPqlId($value);
			}
			$fields[] = $field;
			$values[] = $value;
		}
		if ($isUpdate) {
			if (!$fields) return $newProperties;
			if (!$pk) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
			$sth = $this->getDbh()->prepare("UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE $pk = :pk LIMIT 1");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->bindValue(':pk', $oldProperties[$pkProperty]);
			$sth->execute();
		}
		else {
			if ($fields) {
				$sth = $this->getDbh()->prepare("INSERT INTO $table(".implode(',', $fields).") VALUES(?".str_repeat(', ?', count($fields)-1).")");
				foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
				$sth->execute();
			}
			else {
				if (!$pk) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
				$this->getDbh()->exec("INSERT INTO $table($pk) VALUES(NULL)");
			}
			if ($pk) $newProperties[$pkProperty] = $this->getDbh()->lastInsertId();
		}
		return $newProperties;
	}
	

	final function getIterator(pQL_Query_Predicate_List $list) {
		$tr = $this->getTranslator();
		$field = null;
		$fields = array();
		$tables = array();
		$iterator = new pQL_Query_Iterator;
		foreach($list as $predicate) {
			switch ($predicate->getType()) {
				case pQL_Query_Predicate::TYPE_CLASS:
					$tables[] = $tr->classToTable($predicate->getSubject()).' AS t'.count($tables);
					break;
				case pQL_Query_Predicate::TYPE_PROPERTY:
					$field = $tr->propertyToField($predicate->getSubject());
					$fields[] = 't'.(count($tables)-1).'.'.$field.' AS f'.count($fields);
					break;
				case pQL_Query_Predicate::TYPE_KEY:
					$iterator->setKeyIndex(count($field)-1);
					break;
				default:
					throw new pQL_Exception('Invalid predicate type!');
			}
		}
		
		if (pQL_Query_Predicate::TYPE_PROPERTY != $predicate->getType()) throw new LogicException('Not implemented!');
		
		$iterator->setValueIndex(count($fields)-1);

		$sql = "SELECT ".implode(',', $fields)." FROM ".implode(',', $tables);
		$sth = $this->getDbh()->prepare($sql);
		$sth->setFetchMode(PDO::FETCH_NUM);
		$sth->execute();

		$iterator->setIterator(new IteratorIterator($sth));
		return $iterator;
	}
}