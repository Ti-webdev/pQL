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
 * @version 0.1 alpha
 * @package pQL
 */
class pQL {
	static function PDO(PDO $dbh) {
		return new self(pQL_Driver::Factory('PDO', $dbh));
	}


	private $driver;
	private $translator;
	function __construct(pQL_Driver $driver) {
		$this->translator = new pQL_Translator;
		$this->driver = $driver;
		$this->driver->setTranslator($this->translator);
	}
	
	
	function __destruct() {
		$this->driver = null;
	}


	function creater() {
		return new pQL_Creater($this);
	}
	
	
	function driver() {
		return $this->driver;
	}


	function tablePrefix($newPrefix = null) {
		if (is_null($newPrefix)) return $this->translator->getTablePrefix();
		$this->translator->setTablePrefix($newPrefix);
		return $this;
	}
}