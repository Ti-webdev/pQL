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
	protected function dbh() {
		return $this->dbh;
	}


	abstract protected function getTablePrimaryKey($table);


	function findByPk($class, $value) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getTablePrimaryKey($table);
		$sth = $this->dbh()->prepare("SELECT * FROM $table WHERE $pk = :value");
		$sth->bindValue(':value', $value);
		$sth->setFetchMode(PDO::FETCH_ASSOC);
		$sth->execute();
		$properties = array();
		foreach($sth->fetch() as $field=>$value) $properties[$tr->fieldToProperty($field)] = $value;
		return $this->getTranslator()->getObject($class, $properties);
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
			$fields[] = $field;
			$values[] = $value;
		}
		if ($isUpdate) {
			if (!$fields) return $newProperties;
			if (!$pk) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
			$sth = $this->dbh()->prepare("UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE $pk = :pk LIMIT 1");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->bindValue(':pk', $oldProperties[$pkProperty]);
			$sth->execute();
		}
		else {
			if ($fields) {
				$sth = $this->dbh()->prepare("INSERT INTO $table(".implode(',', $fields).") VALUES(?".str_repeat(', ?', count($fields)-1).")");
				foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
				$sth->execute();
			}
			else {
				if (!$pk) throw new pQL_Exception_PrimaryKeyNotExists("Primary key of $table not defined");
				$this->dbh()->exec("INSERT INTO $table($pk) VALUES(NULL)");
			}
			if ($pk) $newProperties[$pkProperty] = $this->dbh()->lastInsertId();
		}
		return $newProperties;
	}
}