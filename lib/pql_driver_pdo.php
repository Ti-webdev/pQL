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
		$table = $this->getTranslator()->getTableName($class);
		$pk = $this->getPrimaryKey($table);
		$sth = $this->dbh()->prepare("SELECT * FROM $table WHERE $pk = :value");
		$sth->bindValue(':value', $value);
		$sth->setFetchMode(PDO::FETCH_CLASS, 'pQL_Object');
		$sth->execute();
		return $sth->fetch();
	}
}