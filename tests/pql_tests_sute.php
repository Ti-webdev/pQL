<?php
require_once dirname(__FILE__).'/bootstrap.php';
require_once 'PHPUnit/Framework/TestSuite.php';


/**
 * Static test suite.
 */
class pQL_Tests_Sute extends PHPUnit_Framework_TestSuite {
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct() {
		$this->setName ('pQL_Tests_Sute');
		$this->addTestSuite('pQL_PDO_MySQL_Test');
		$this->addTestSuite('pQL_PDO_SQLite_Test');
	}


	/**
	 * Creates the suite.
	 */
	public static function suite() {
		return new self;
	}
}

