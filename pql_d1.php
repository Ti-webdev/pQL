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
		global $CFG;
		$waPath = $_SERVER['DOCUMENT_ROOT'].'/wedadmin';
		require_once "$waPath/config.inc.php";
		require_once "$waPath/lib/mysql.lib.php";
		db_open();

		// инициализируем pQL
		$pQL = pQL::MySQL();
		$pQL->coding(new pQL_Coding_Typical);
		if (isset($CFG['db_prefix'])) $pQL->tablePrefix($CFG['db_prefix']);
		$creater = $pQL->creater();
	}
	return $creater;
}