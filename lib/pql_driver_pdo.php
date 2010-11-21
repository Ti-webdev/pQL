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


	protected function getSelectQuery(pQL_Select_Builder $builder) {
		return $this->getDbh()->query($builder->getSQL());
	}


	protected function getSelectIterator($sth) {
		$sth->setFetchMode(PDO::FETCH_NUM);
		return new IteratorIterator($sth);
	}


	final function getCount(pQL_Query_Mediator $queryMediator) {
		return $queryMediator->getQueryHandle($this)->rowCount();
	}
}