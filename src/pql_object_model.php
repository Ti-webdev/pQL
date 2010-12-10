<?php
/**
 * Реализация простого объекта pQL в котором имя модели хранится в объекте
 * @author Ti
 * @package pQL
 */
class pQL_Object_Model extends pQL_Object {
	function __construct(pQL $pQL, $properties, $model) {
		$this->model = $model;
		parent::__construct($pQL, $properties);
	}


	final function getModel() {
		return $this->model;
	}


	private $model;
}