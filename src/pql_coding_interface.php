<?php
/**
 * Интерфейс для преобразования стандартов кодирования между базой данных и программой
 * @author Ti
 * @package pQL
 */
interface pQL_Coding_Interface {
	function toDB($string);
	function fromDb($string);
}