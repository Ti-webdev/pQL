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
	
	
	function testFieldIterator() {
		$this->db->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");


		$expected = array();
		for($i = 0; $i<10; $i++) {
			$object = $this->pql()->test();
			$expected['first'][] = $object->first = rand(0, PHP_INT_SIZE);
			$expected['number'][] = $object->number = md5(microtime(true));
			$expected['last'][] = $object->last = - rand(0, PHP_INT_SIZE);
			$object->save();
		}


		$cnt = 0;
		foreach($this->pql()->test->number as $i=>$number) {
			$this->assertEquals($expected['number'][$i], $number);
			$cnt++;
		}


		foreach($this->pql()->test->first as $i=>$first) {
			$this->assertEquals($expected['first'][$i], $first);
			$cnt++;
		}

		
		foreach($this->pql()->test->last as $i=>$last) {
			$this->assertEquals($expected['last'][$i], $last);
			$cnt++;
		}


		$this->assertEquals(30, $cnt);


		/**
		 * @todo fetch foreign object row
		 */
	}
	
	
	function testKeyIterator() {
		$this->db->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");
	
		$expected = array();
		for($i = 0; $i<10; $i++) {
			$object = $this->pql()->test();
			$expected['first'][] = $object->first = rand(0, PHP_INT_SIZE);
			$expected['number'][] = $object->number = md5(microtime(true));
			$expected['last'][] = $object->last = - rand(0, PHP_INT_SIZE);
			$object->save();
		}
	
		$i = 0;
		foreach($this->pql()->test->last->key()->number as $last=>$number) {
			$this->assertEquals($expected['last'][$i], $last);
			$this->assertEquals($expected['number'][$i], $number);
			$i++;
		}
		$this->assertEquals(count($expected['first']), $i);
	}
}