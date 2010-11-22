<?php
abstract class pQL_Driver_Test_Abstract extends PHPUnit_Framework_TestCase {
	function __destruct() {
		$this->pql = null;
	}
	
	
	abstract function setUpPql();
	abstract function exec($sql);
	abstract function quote($val);
	
	
	function setUp() {
		$this->setUpPql();
		$this->pql->tablePrefix('pql_');
		$this->creater = $this->pql->creater();
	}
	
	
	function tearDown() {
		$this->pql = null;
		$this->exec("DROP TABLE IF EXISTS pql_test");
	}
	
	
	protected function pql() {
		return $this->creater;
	}

	
	function testToString() {
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$obj = $this->pql()->test()->set('val', $val)->save();
		$this->assertEquals($val, "$obj");
	}
	
	
	function testFieldIterator() {
		$this->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");


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
		$this->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");
	
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
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
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
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$this->assertEquals(0, count($this->pql()->test));
		
		$this->exec("INSERT INTO pql_test VALUES('".md5(microtime(true))."')");
		$this->assertEquals(1, count($this->pql()->test));
		
		for($i = 0; $i<10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES('".md5(microtime(true))."')");
		}
		$q = $this->pql()->test->val;
		$this->assertEquals(11, count($q));

		$count = 0;
		foreach($q as $val) $count++;
		$this->assertEquals(11, $count);
		
		$i = null;
		foreach($this->pql()->test->val as $i=>$val) ;
		$this->assertEquals(10, $i);
		
		$count = 0;
		foreach($this->pql()->test as $i=>$val) $count++;
		$this->assertEquals(11, $count);

		$this->exec("DELETE FROM pql_test");
		$this->assertEquals(0, count($this->pql()->test));
	}
	
	
	function testQueryTwice() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		$vals = array();
		for($i = 0; $i<10; $i++) {
			$val = md5(microtime(true));
			$this->exec("INSERT INTO pql_test VALUES('".$val."')");
			$vals[] = $val;
		}
		
		$q = $this->pql()->test;
		
		$this->assertEquals(count($vals), count($q));
		
		$valsCopy = $vals;
		foreach($q as $v) {
			$i = array_search($v->val, $valsCopy);
			unset($valsCopy[$i]);
		}
		if ($valsCopy) $this->fail('valsCopy assert empty!');
		
		$this->assertEquals(count($vals), count($q));
		

		// foreach again!
		$valsCopy = $vals;
		foreach($q as $v) {
			$i = array_search($v->val, $valsCopy);
			unset($valsCopy[$i]);
		}
		if ($valsCopy) $this->fail('valsCopy assert empty!');
	}
	
	
	function testIn() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		
		$vals = array('one', 'two', 'three', null, "'quoted string\"");
		foreach($vals as $val) {
			$this->exec("INSERT INTO pql_test VALUES(".$this->quote($val).")");
		}

		foreach($vals as $expected) {
			$q = $this->pql()->test->val->in($expected);
			$this->assertEquals(1, count($q), "invalid count for '$expected'\nSQL: $q");
			$found = 0;
			foreach($q as $object) {
				$this->assertEquals($expected, $object->val);
				$found++;
			}
			if (1 !== $found) $this->fail("'$expected': $found results");
		}
	}
}