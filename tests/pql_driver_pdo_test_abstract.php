<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_Driver_PDO_Test_Abstract extends PHPUnit_Framework_TestCase {
	protected $db;
	protected $pql;
	
	
	function __construct() {
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		parent::__construct();
	}
	
	
	function __destruct() {
		$this->db = null;
		$this->pql = null;
	}
	
	
	protected function pql() {
		return $this->pql->creater();
	}
	
	
	
	function setUp() {
		$this->pql = pQL::PDO($this->db);
		$this->pql->tablePrefix('pql_');
	}
	
	
	function tearDown() {
		$this->pql = null;
		$this->db->exec("DROP TABLE IF EXISTS pql_test");
	}
	
	
	function testToString() {
		$val = md5(microtime(true));
		$this->db->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$obj = $this->pql()->test()->set('val', $val)->save();
		$this->assertEquals($val, "$obj");
	}
}