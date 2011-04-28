<?php
/**
 * Итератор pQL запроса, в значениях - объект
 * 
 * @author Ti
 * @package pQL
 */
final class pQL_Query_Iterator_Values_Object implements pQL_Query_Iterator_Values_Interface {
	function __construct(pQL_Driver $driver, $className, $keys) {
		$this->driver = $driver;
		$this->className = $className;
		$this->keys = $keys;
	}
	
	
	/**
	 * Класс используемый в качестве значений итератора
	 * @var pQL_Query_Iterator_Class
	 */
	private $className;
	
	/**
	 * хеш-массив, ключи - номер поля в выборке; значения - название свойства объекта
	 * @var array
	 */
	private $keys;
	
	/**
	 * @var pQL_Driver
	 */
	private $driver;


	function getValue($current) {
		$properties = array();
		foreach($this->keys as $i=>$name) {
			$properties[$name] = $current[$i];
		}
		return $this->driver->getObject($this->className, $properties);
	}
}