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
		return $this->creater;
	}
	
	
	
	function setUp() {
		$this->pql = pQL::PDO($this->db);
		$this->pql->tablePrefix('pql_');
		$this->creater = $this->pql->creater();
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


	function testFetchObject() {
		$this->db->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$val = md5(microtime(true));

		$object = $this->pql()->test();
		$object->val = $val;
		$object->save();

		foreach($this->pql()->test as $test) {
			$this->assertEquals($val, $test->val);
			return;
		}

		$this->fail('object not found');
	}
	
	
	function testCountable() {
		$this->db->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$this->assertEquals(0, count($this->pql()->test));
		
		$this->db->exec("INSERT INTO pql_test VALUES('".md5(microtime(true))."')");
		$this->assertEquals(1, count($this->pql()->test));
		
		for($i = 0; $i<10; $i++) {
			$this->db->exec("INSERT INTO pql_test VALUES('".md5(microtime(true))."')");
		}
		$this->assertEquals(11, count($this->pql()->test->val));

		$count = 0;
		foreach($this->pql()->test->val as $val) $count++;
		$this->assertEquals(11, $count);
		
		$i = null;
		foreach($this->pql()->test->val as $i=>$val) ;
		$this->assertEquals(10, $i);
		
		$count = 0;
		foreach($this->pql()->test as $i=>$val) $count++;
		$this->assertEquals(11, $count);

		$this->db->exec("DELETE FROM pql_test");
		$this->assertEquals(0, count($this->pql()->test));
	}
	
	
	function _testIn() {
		$this->db->exec("CREATE TABLE pql_test(first INT, val VARCHAR(255), last INT)");
		
		$objects = array();
		for($i = 0; $i<10; $i++) {
			$object = $this->pql()->test();
			$object->first = rand(0, PHP_INT_SIZE);
			$object->val = md5(microtime(true));
			$object->last = - rand(0, PHP_INT_SIZE);
			$object->save();
			if (rand(0, 1)) $objects[] = $object;
		}

		$q = $this->pql()->test->val;
		foreach($objects as $object) $q->is($object->val);
		foreach($q as $actualObject) {
			$this->assertEquals($object->val, $actualObject->val);
			return;
		}

		$this->fail('object not found');
	}
}