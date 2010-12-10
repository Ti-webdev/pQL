<?php
/**
 * Реализация простого объекта pQL в котором имя модели соответствует имени класса
 * @author Ti
 * @package pQL
 */
class pQL_Object_Classname extends pQL_Object {
	function getModel() {
		return get_class($this);
	}


	private $model;
}