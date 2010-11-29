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


	final function isSupportRewindQuery() {
		return false;
	}
}