<?php
require_once dirname(__FILE__).'/pql.php';

/**
 * @return pQL_Query
 */
function waDb() {
	static $creater;
	if (!$creater) {
		
		// при первом вызове
		// подключаемся к базе
		$waPath = $_SERVER['DOCUMENT_ROOT'].'/wedadmin';
		$CFG = require_once "$waPath/config.inc.php";
		if (!is_array($CFG)) global $CFG;
		require_once "$waPath/lib/mysql.lib.php";
		db_open();

		// инициализируем pQL
		$pQL = pQL::MySQL();
		$pQL->coding(new pQL_Coding_Typical);
		$pQL->objectDefinder(new WA_pQL_Object_Definer);
		if (isset($CFG['db_prefix'])) $pQL->tablePrefix($CFG['db_prefix']);
		elseif (isset($CFG['prefix'])) $pQL->tablePrefix($CFG['prefix']);
		$creater = $pQL->creater();
	}
	return $creater;
}

class WA_pQL_Object_Definer implements  pQL_Object_Definer_Interface {
	function getObject(pQL $pQL, $className, $properties) {
		$d1Class = 'D1_'.preg_replace('#([a-z])([A-Z])#', '$1_$2', $className);
		if (class_exists($d1Class)) {
			return new $d1Class($pQL, $properties, $className);
		}
		else {
			return new pQL_Object_Model($pQL, $properties, $className);
		}
	}
}