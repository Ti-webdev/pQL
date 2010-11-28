<?php
abstract class pQL_Driver_Test_Abstract extends PHPUnit_Framework_TestCase {
	function __destruct() {
		$this->pql = null;
	}
	
	
	abstract function setUpPql();
	abstract function exec($sql);
	abstract protected function queryValue($sql);
	abstract function quote($val);
	abstract protected function getPKExpr();
	abstract protected function lastInsertId();
	
	
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
	
	
	function testOne() {
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$this->exec("INSERT INTO pql_test VALUES('$val')");
		$this->assertEquals($val, $this->pql()->test->one()->val);
	}

	
	function testToString() {
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$obj = $this->pql()->test()->set('val', $val)->save();
		$this->assertEquals($val, "$obj");
		$this->assertEquals($val, $this->pql()->test->one()->__toString());
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
		foreach($this->pql()->test->last->key() as $last=>$object) {
			$this->assertEquals($expected['last'][$i], $last);
			$this->assertEquals($last, $object->last);
			$this->assertEquals($expected['number'][$i], $object->number);
			$i++;
		}
		$this->assertEquals(count($expected['first']), $i);
	}
	
	
	function getValueIterator() {
		$this->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");

		$expected = array();
		for($i = 0; $i<10; $i++) {
			$object = $this->pql()->test();
			$expected['first'][] = $object->first = rand(0, PHP_INT_SIZE);
			$expected['number'][] = $object->number = md5(microtime(true));
			$expected['last'][] = $object->last = - rand(0, PHP_INT_SIZE);
			$object->save();
		}

		foreach($this->pql()->test->number->value()->db() as $i=>$number) {
			$this->assertEquals($expected['number'][$i], $number);
		}
		$this->assertEquals(count($expected['first']), $i+1);

		foreach($this->pql()->test->value()->db() as $i=>$object) {
			$this->assertEquals($expected['number'][$i], $object->number);
		}
		$this->assertEquals(count($expected['first']), $i+1);

		$i = 0;
		foreach($this->pql()->test->number->value()->last->key() as $last=>$object) {
			$this->assertEquals($expected['last'][$i], $last);
			$this->assertEquals($expected['number'][$i], $object->number);
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
		if ($valsCopy) $this->fail('valsCopy not empty!');
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
	
	
	function testModifyQuery() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		
		$vals = array('one', 'two', 'three', null, "'quoted string\"");
		foreach($vals as $val) {
			$this->exec("INSERT INTO pql_test VALUES(".$this->quote($val).")");
		}

		// count
		$q = $this->pql()->test->val;
		$this->assertEquals(5, count($q));
		$q->in('two', 'three'); // modify
		$this->assertEquals(2, count($q));
		
		// fetch
		$q = $this->pql()->test->val->value();
		foreach($q as $i=>$val) $this->assertEquals($vals[$i], $val);
		
		$actual = array();
		$this->assertEquals(array('two', null), iterator_to_array($q->val->in('bugaaa', 'two', null, 5)));
	}
	
	
	function testBetween() {
		$this->exec("CREATE TABLE pql_test(val INT)");
		for($i = -10; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES('".$i."')");
		}
		$this->exec("INSERT INTO pql_test VALUES(NULL)");

		$this->assertEquals(array(1, 2), $this->pql()->test->val->value()->between(1,2)->toArray());
		$this->assertEquals(range(-1, 2), $this->pql()->test->val->value()->between(-1,2)->toArray());
		$this->assertEquals(array(-10, -9), $this->pql()->test->val->value()->between(-PHP_INT_MAX, -9)->toArray());
		$this->assertEquals(range(8, 10), $this->pql()->test->val->value()->between(8, PHP_INT_MAX)->toArray());
		$this->assertEquals(array(), $this->pql()->test->val->value()->between(11, 12)->toArray());
		$this->assertEquals(array(0), $this->pql()->test->val->value()->between(0, 0)->toArray());
		$this->assertEquals(array(), $this->pql()->test->val->value()->between(2, 1)->toArray());
	}


	function testNot() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");

		$vals = array('one', 'two', 'three', null, "'quoted string\"");
		foreach($vals as $val) $this->exec("INSERT INTO pql_test VALUES(".$this->quote($val).")");

		$q = $this->pql()->test->val->value()->not('two');
		$this->assertEquals(array('one', 'three', "'quoted string\""), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->value()->not(null);
		$this->assertEquals(array('one', 'two', 'three', "'quoted string\""), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->value()->not('three', 'one');
		$this->assertEquals(array('two', "'quoted string\""), $q->toArray(), "SQL: $q");
		
		// as array
		$q = $this->pql()->test->val->value()->not(array('three', 'one'));
		$this->assertEquals(array('two', "'quoted string\""), $q->toArray(), "SQL: $q");
	}
	
	
	function testLimit() {
		$this->exec("CREATE TABLE pql_test(val INT)");
		for($i = 0; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES('".$i."')");
		}
		$this->exec("INSERT INTO pql_test VALUES(NULL)");

		$q = $this->pql()->test->val->value();
		$this->assertEquals(range(0, 4), $q->limit(5)->toArray());
		$this->assertEquals(range(0, 9), $q->limit(10)->toArray());
		
		$this->assertEquals(range(3, 7), $q->offset(3)->limit(5)->toArray());

		$expected = range(6, 10);
		$expected[] = null;
		$this->assertEquals($expected, $this->pql()->test->offset(6)->val->toArray());
	}


	function testOrderBy() {
		$this->exec("CREATE TABLE pql_test(val INT, negative INT)");
		for($i = 11; $i<=20; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i, -$i)");
		}
		for($i = 0; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i, -$i)");
		}
		$this->assertEquals(range(20, 0), $this->pql()->test->negative->ask()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->val->ask()->val->toArray());
		$this->assertEquals(range(20, 0), $this->pql()->test->val->desc()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->negative->desc()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->negative->desc()->val->desc()->val->toArray());
		$this->exec("UPDATE pql_test SET negative = 50");
		$this->assertEquals(range(20, 0), $this->pql()->test->negative->desc()->val->desc()->value()->toArray());
	}
	
	
	function testCloneQuery() {
		$this->exec("CREATE TABLE pql_test(val INT)");
		for($i = -10; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i)");
		}
		$q = $this->pql()->test->val->value()->not(0);
		$this->assertEquals(array_merge(range(-10, -1), range(1, 10)), $q->toArray());
		
		$qClone = clone $q;
		$this->assertEquals(array_merge(range(-10, -1), range(1, 10)), $qClone->toArray());
		$this->assertEquals(range(3, 10), $qClone->not(range(-10, 2))->toArray());
		$this->assertEquals(array_merge(range(-10, -1), range(1, 10)), $q->toArray());
	}


	function testLessAndGreater() {
		$this->exec("CREATE TABLE pql_test(val INT)");
		for($i = -10; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i)");
		}

		$q = $this->pql()->test->val->value()->lt(6);
		$this->assertEquals(range(-10, 5), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->value()->lte(6);
		$this->assertEquals(range(-10, 6), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->value()->gt(-1);
		$this->assertEquals(range(0, 10), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->value()->gte(-1);
		$this->assertEquals(range(-1, 10), $q->toArray(), "SQL: $q");
	}


	function testLike() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");

		$vals = array('one', 'two', 'three', null);
		foreach($vals as $val) $this->exec("INSERT INTO pql_test VALUES(".$this->quote($val).")");

		$q = $this->pql()->test->val->like('%o%')->value();
		$this->assertEquals(array('one', 'two'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('%o')->value();
		$this->assertEquals(array('two'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('o%')->value();
		$this->assertEquals(array('one'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('o')->value();
		$this->assertEquals(array(), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('%e')->value();
		$this->assertEquals(array('one', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('%ee')->value();
		$this->assertEquals(array('three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('%waka%')->value();
		$this->assertEquals(array(), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('t')->value();
		$this->assertEquals(array(), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->like('t%')->value();
		$this->assertEquals(array('two', 'three'), $q->toArray(), "SQL: $q");
	}
	
	
	function testNotLike() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");

		$vals = array('one', 'two', 'three', null);
		foreach($vals as $val) $this->exec("INSERT INTO pql_test VALUES(".$this->quote($val).")");

		$q = $this->pql()->test->val->notLike('%o%')->value();
		$this->assertEquals(array('three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('%o')->value();
		$this->assertEquals(array('one', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('o%')->value();
		$this->assertEquals(array('two', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('o')->value();
		$this->assertEquals(array('one', 'two', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('%e')->value();
		$this->assertEquals(array('two'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('%ee')->value();
		$this->assertEquals(array('one', 'two'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('%waka%')->value();
		$this->assertEquals(array('one', 'two', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('t')->value();
		$this->assertEquals(array('one', 'two', 'three'), $q->toArray(), "SQL: $q");

		$q = $this->pql()->test->val->notLike('t%')->value();
		$this->assertEquals(array('one'), $q->toArray(), "SQL: $q");
	}
	
	
	function testSaveForeignObject() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().")");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", test INT)");
		
		$object = $this->pql()->test()->save();
		$objectB = $this->pql()->testB();
		$objectB->test = $object;
		$objectB->save();

		$this->assertEquals($object->id, $this->queryValue("SELECT test FROM pql_test_b"));

		$this->exec("DROP TABLE pql_test_b");
	}
	
	
	final function testErrorOnSaveWithoutPK() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(225))");
		$this->setExpectedException('pQL_Exception_PrimaryKeyNotExists');
		$this->pql()->test()->save();
	}


	final function testCreate() {
		$val = md5(microtime(true));
		
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val TEXT)");
		
		$object = $this->pql()->test();
		$this->assertTrue($object instanceof pQL_Object);
		$this->assertTrue(empty($object->id));
		$object->val = $val;
		$object->save();

		$id = $this->lastInsertId();
		$this->assertEquals($val, $this->queryValue("SELECT val FROM pql_test WHERE val = '$val'"));
		$this->assertEquals($id, $object->id);
		$this->assertEquals($val, $this->pql()->test($id)->val);
		
		// custom id field
		$this->exec("DROP TABLE pql_test");
		$this->exec("CREATE TABLE pql_test(val TEXT, my_int ".$this->getPKExpr().")");
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$this->exec("INSERT INTO pql_test(val) VALUES('second')");
		$val = md5(microtime(true));
		$object = $this->pql()->test();
		$object->val = $val;
		$object->save();
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('last')");

		$this->assertEquals($val, $this->queryValue("SELECT val FROM pql_test WHERE val = '$val'"));
		$this->assertEquals($id, $object->my_int);
		$this->assertEquals($val, $this->pql()->test($id)->val);
	}
	
	
	final function testTableNameTranslate() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().")");
		$id = $this->pql()->testB()->save()->id;
		$this->assertEquals($id, $this->queryValue("SELECT id FROM pql_test_b"));
		$this->exec("DROP TABLE pql_test_b");
	}
	
	
	function testJoin() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
	}
	
	
	function testJoinSelf() {
		/**
		 * @todo
		 */
	}
}