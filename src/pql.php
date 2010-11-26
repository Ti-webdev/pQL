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
}