<?php
/**
 * Абстрактный PDO драйвер для pQL
 * @author Ti
 * @package pQL
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


	final protected function update($table, $fields, $values, $where) {
		$sql = "UPDATE $table SET ".implode('= ?, ', $fields)." = ? WHERE ";
		foreach(array_keys($where) as $i=>$field) {
			if ($i) $sql .= ' AND ';
			$sql .= "$field = ?";
		}
		$sql .= ' LIMIT 1';
		$sth = $this->getDbh()->prepare($sql);
		unset($sql);

		foreach($values as $i=>$val) $sth->bindValue($i+1, $val);
		$num = $i+1;
		foreach(array_values($where) as $i=>$val) $sth->bindValue($i+$num, $val);

		$sth->bindValue(':pk', $where);
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
			$fields = implode(',', $pk);
			$values = implode(',', array_fill(0, count($fields), 'NULL'));
			$this->getDbh()->exec("INSERT INTO $table($fields) VALUES($values)");
		}
		return $this->getDbh()->lastInsertId();
	}
	
	
	function delete($table, $where) {
		$sql = "DELETE FROM $table WHERE ";
		foreach(array_keys($where) as $i=>$field) {
			if ($i) $sql .= ' AND ';
			$sql .= "$field = ?";
		}
		$sth = $this->getDbh()->prepare($sql);
		foreach(array_values($where) as $i=>$value) $sth->bindValue($i+1, $value);
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