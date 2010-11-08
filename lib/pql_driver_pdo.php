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


	function save($class, $properties) {
		$tr = $this->getTranslator();
		$table = $tr->classToTable($class);
		$pk = $this->getPrimaryKey($table);
		$values = $fields = array();
		$isUpdate = false;
		foreach($properties as $key=>$value) {
			$field = $tr->propertyToField($key);
			if ($pk == $field) {
				$isUpdate = true;
				$id = $value;
				continue;
			}
			$fields[] = $field;
			$values[] = $value;
		}
		if (!$fields) return;
		if (in_array($pk, $fields)) {
			$sth = $this->dbh()->prepare("UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE $pk = :pk LIMIT 1");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->bindValue(':pk', $id);
			$sth->execute();
		}
		else {
			$sth = $this->dbh()->prepare("INSERT INTO $table(".implode(',', $fields).") VALUES(?".str_repeat(', ?', count($fields)-1).")");
			foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
			$sth->execute();
			return $this->dbh()->lastInsertId();
		}
	}
}