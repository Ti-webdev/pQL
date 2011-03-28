<?php
require_once dirname(__FILE__).'/bootstrap.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'pQL_Tests_Suite::main');
}

/**
 * Static test suite.
 */
class pQL_Tests_Suite extends PHPUnit_Framework_TestSuite {
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName(__CLASS__);
		$this->addTestSuite('pQL_Coding_Zend_Test');
		$this->addTestSuite('pQL_Driver_MySQL_Test');
		$this->addTestSuite('pQL_Driver_PDO_MySQL_Test');
		$this->addTestSuite('pQL_Driver_PDO_SQLite_Test');
	}


	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self;
	}


	static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}
}


if (PHPUnit_MAIN_METHOD == 'pQL_Tests_Suite::main') {
	pQL_Tests_Suite::main();
}