<?php
final class pQL_Query_Iterator_Class {
	/**
	 * Класс итератора выборки
	 * @param string $name имя класса
	 * @param array $indexes хеш: ключи - номера полей из результата выборки, заначения - имена свойств объекта
	 */
	function __construct($name, $indexes) {
		$this->name = $name;
		$this->indexes = $indexes;
	}


	private $name;
	private $indexes;


	function getName() {
		return $this->name;
	}


	function getIndexes() {
		return $this->indexes;
	}
}