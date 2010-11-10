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


	abstract protected function getPrimaryKey($table);


	function findByPk($class, $value) {
		$table = $this->getTranslator()->classToTable($class);
		$pk = $this->getPrimaryKey($table);
		$sth = $this->dbh()->prepare("SELECT * FROM $table WHERE $pk = :value");
		$sth->bindValue(':value', $value);
		$sth->setFetchMode(PDO::FETCH_INTO, $this->getTranslator()->getObject($class));
		$sth->execute();
		return $sth->fetch();
	}


	function save($class, $newProperties, $oldProperties) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getPrimaryKey($table);
		$pkProperty = $tr->fieldToProperty($pk);
		$values = $fields = array();
		$isUpdate = isset($oldProperties[$pkProperty]);
		foreach($newProperties as $key=>$value) {
			$field = $tr->propertyToField($key);
			$fields[] = $field;
			$values[] = $value;
		}
		if (!$fields) return;
		if ($isUpdate) {
			$sth = $this->dbh()->prepare("UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE $pk = :pk LIMIT 1");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->bindValue(':pk', $oldProperties[$pkProperty]);
			$sth->execute();
		}
		else {
			$sth = $this->dbh()->prepare("INSERT INTO $table(".implode(',', $fields).") VALUES(?".str_repeat(', ?', count($fields)-1).")");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->execute();
			$newProperties[$pkProperty] = $this->dbh()->lastInsertId();
		}
		return $newProperties;
	}
}