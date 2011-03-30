<?php
class pQL_Driver_Test_Object extends pQL_Object_Model {
	
}


abstract class pQL_Driver_Test_Object_Abstract extends pQL_Object {
	function getModel() {
		return substr(get_class($this), strlen('pQL_Driver_Test_'));
	}
}


class pQL_Driver_Test_Object_Definer implements pQL_Object_Definer_Interface {
	function getObject(pQL $pQL, $className, $properties) {
		$resultClass = 'pQL_Driver_Test_'.ucfirst($className);
		if (!class_exists($resultClass)) {
			eval("class $resultClass extends pQL_Driver_Test_Object_Abstract {}");
		}
		return new $resultClass($pQL, $properties, $className);
	}
}


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
	
	
	/**
	 * @var pQL
	 */
	protected $pql;
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
		$q = $this->pql()->test->val;
		$this->assertEquals($val, $q->one(), "SQL: $q");
		$this->assertEquals($val, $this->pql()->test->one()->val);
	}

	
	function testToString() {
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(val VARCHAR(32))");
		$obj = $this->pql()->test()->set('val', $val)->save();
		$this->assertEquals($val, $obj->__toString());
		if (version_compare(PHP_VERSION, '5.2.0', '>=')) $this->assertEquals($val, "$obj");
		$this->assertEquals($val, $this->pql()->test->one()->__toString());
	}


	function testUpdate() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(32))");
		$this->exec("INSERT INTO pql_test(val) VALUES('".md5(microtime(true))."')");
		$id = $this->lastInsertId();

		$object = $this->pql()->test($id);
		$object->val = md5(microtime(true));
		$object->save();

		$this->assertEquals($object->val, $this->pql()->test($id)->val);
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
	

	/**
	 * @todo
	 * @return void
	 */
	function testValueIterator() {
		$this->exec("CREATE TABLE pql_test(first INT, number VARCHAR(255), last INT)");

		$expected = array();
		for($i = 0; $i<10; $i++) {
			$object = $this->pql()->test();
			$expected['first'][] = $object->first = rand(0, PHP_INT_SIZE);
			$expected['number'][] = $object->number = md5(microtime(true));
			$expected['last'][] = $object->last = - rand(0, PHP_INT_SIZE);
			$object->save();
		}

		$it = $this->pql()
			->test
			->number
			->value()
			->db();
		foreach($it as $i=>$number) {
			$this->assertEquals($expected['number'][$i], $number);
		}
		$this->assertEquals(count($expected['first']), $i+1);

		foreach($this->pql()->test->value()->db() as $i=>$object) {
			$this->assertEquals($expected['number'][$i], $object->number);
		}
		$this->assertEquals(count($expected['first']), $i+1);

		$i = 0;
		foreach($this->pql()->test->number->value()->last->key() as $last=>$number) {
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
		
		unset($vals[2]);
		$vals = array_values($vals);
		$this->assertEquals($vals, $this->pql()->test->val->in($vals)->value()->toArray());
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
		$this->assertEquals(range(20, 0), $this->pql()->test->negative->asc()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->val->asc()->val->toArray());
		$this->assertEquals(range(20, 0), $this->pql()->test->val->desc()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->negative->desc()->val->toArray());
		$this->assertEquals(range(0, 20), $this->pql()->test->negative->desc()->val->desc()->val->toArray());
		$this->exec("UPDATE pql_test SET negative = 50");
		$this->assertEquals(range(20, 0), $this->pql()->test->negative->desc()->val->desc()->value()->toArray());
	}



	function testLimitAndOrderQuery() {
		$this->exec("CREATE TABLE pql_test(val INT, negative INT)");
		for($i = 11; $i<=20; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i, -$i)");
		}
		for($i = 0; $i<=10; $i++) {
			$this->exec("INSERT INTO pql_test VALUES($i, -$i)");
		}
		$this->assertEquals(range(20, 6), $this->pql()->test->negative->asc()->limit(15)->val->toArray());
	}


	function testLoadProperties() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val INT)");
		$this->exec("INSERT INTO pql_test(val) VALUES(1)");
		$object = $this->pql()->test->one();
		$this->assertEquals(1, $object->val);
		$this->exec("UPDATE pql_test SET val = 22");
		$this->assertEquals(1, $object->val);
		$object->loadProperties();
		$this->assertEquals(22, $object->val);
	}


	function testGetPropertyOnNewObject() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val INT)");
		
		$this->assertNull($this->pql()->test()->id);
		
		$this->assertNull($this->pql()->test()->set('val', 123)->id);
		
		$object = $this->pql()->test();
		$val = $object->val;
		
		$this->assertNull($object->id);
		
		$this->assertNull($this->pql()->test()->val);
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
	
	
	function testFieldGroup() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$vals = array('one', 'two', 'three');
		for($i = 0; $i < 10; $i++) {
			foreach($vals as $val) $this->exec("INSERT INTO pql_test(val) VALUES(".$this->quote($val).")");
		}
		
		$q = $this->pql()->test->val->group()->value()->id->asc();
		$this->assertEquals($vals, $q->toArray(), "SQL: $q");
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

		$this->pql->clearCache();
		
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
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_id INT)");
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$firstId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('second')");
		$secondId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('last')");
		$lastId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(test_id, val) VALUES($secondId, 'b_second')");

		$this->assertEquals('b_second', $this->pql()->testB->db()->test->val->in('second')->one()->val);

		$this->assertEquals(0, count($this->pql()->testB->db()->test->val->in('frist', 'last')));

		$this->exec("DROP TABLE pql_test_b");
	}
	
	
	/**
	 * @todo
	 */
	function _testJoinUsingNames() {
		$this->exec("
			CREATE TABLE IF NOT EXISTS `type_tovar` (
			  `id_type` ".$this->getPKExpr().",
			  `name` varchar(255) NOT NULL DEFAULT ''
			)");
		$this->exec("
			CREATE TABLE IF NOT EXISTS `tovars` (
			  `id_tovar` ".$this->getPKExpr().",
			  `id_type` int(11) NOT NULL DEFAULT '0',
			  `name` varchar(255) NOT NULL DEFAULT ''
			)");

		$this->exec("INSERT INTO `type_tovar`(id_type, name) VALUES (1, 'first_type')");
		$this->exec("INSERT INTO `type_tovar`(id_type, name) VALUES (3, 'second_type')");
		$this->exec("INSERT INTO `type_tovar`(id_type, name) VALUES (2, 'last_type')");

		$this->exec("INSERT INTO `tovars`(id_tovar, id_type, name) VALUES (10, 1, 'first')");
		$this->exec("INSERT INTO `tovars`(id_tovar, id_type, name) VALUES (20, 3, 'last')");

		$this->pql->tablePrefix('');
		echo $this->pql()->type_tovar
			->id_type->group()
			->db()->tovars->id_tovar->in(10,20);
			echo "\n";
		exit;

		$this->exec("DROP TABLE IF EXISTS `type_tovar`");
		$this->exec("DROP TABLE IF EXISTS `tovars`");
	}


	/**
	 * @todo
	 */
	function _testJoinSelf() {
	}
	
	
	function testMySQLNameQuote() {
		if (false === stripos(get_class($this->pql->driver()), 'mysql')) return;
		
		$expected = md5(microtime(true));
		$this->exec("DROP TABLE IF EXISTS `and`");
		$this->exec("CREATE TABLE `and`(`delete` VARCHAR(255))");
		$this->exec("INSERT INTO `and` VALUES ('$expected')");
		$this->pql->tablePrefix('');
		$this->assertEquals($expected, $this->pql()->and->one()->delete);
		$this->exec("DROP TABLE `and`");
	}
	
	
	function testBind() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_id INT)");
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$firstId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('second')");
		$secondId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('last')");
		$lastId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(test_id, val) VALUES($secondId, 'b_second')");
		
		// property
		$b = $this->pql()->testB->db()->test->id->bind($id1)->one();
		$this->assertEquals('b_second', $b->val);
		$this->assertEquals($secondId, $id1);

		// object
		$b = $this->pql()->testB->db()->test->bind($test2)->one();
		$this->assertEquals('b_second', $b->val);
		$this->assertEquals('second', $test2->val);

		// mixed
		$b = $this->pql()->testB->db()->test->bind($test3)->id->bind($id3)->one();
		$this->assertEquals('b_second', $b->val);
		$this->assertEquals('second', $test3->val);
		$this->assertEquals($secondId, $id3);

		$this->exec("DROP TABLE pql_test_b");
	}
	
	
	function testUnbind() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_id INT)");
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$firstId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('second')");
		$secondId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test(val) VALUES('last')");
		$lastId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(test_id, val) VALUES($secondId, 'b_second')");
		
		$q = $this->pql()->testB;
		$q->db()->test->id->bind($id);
		$q->db()->test->bind($test);
		$q->one();
		$this->assertEquals($secondId, $id);
		$this->assertEquals('second', $test->val);
		$q->unbind($test)->unbind($id);
		$id = $test = $expected = md5(microtime(true));
		$q->one();
		$this->assertEquals($expected, $id);
		$this->assertEquals($expected, $test);
	}
	
	
	function testQueryTable() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$expected = md5(microtime(true));
		$this->exec("INSERT INTO pql_test(val) VALUES('$expected')");
		$q = $this->pql()->test->val->table()->bind($object)->val;
		foreach($q as $val) {
			return $this->assertEquals($expected, $object->val);
		}
		$this->fail('object not found');
	}
	
	
	function testDeleteObject() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().")");
		$object = $this->pql()->test()->save();
		$this->assertEquals(1, $this->queryValue("SELECT COUNT(*) FROM pql_test"));
		$object->delete();
		$this->assertEquals(0, $this->queryValue("SELECT COUNT(*) FROM pql_test"));
	}


	function testDeleteQuery() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val INT)");
		for($i = 0; $i < 10; $i++) $this->exec("INSERT INTO pql_test(val) VALUES($i)");
		$this->pql()->test->val->between(3,5)->delete();
		$this->assertEquals(array_merge(range(0, 2), range(6, 9)), $this->pql()->test->val->toArray());
	}
	
	
	function testUpdateQuery() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val INT)");
		for($i = 0; $i < 10; $i++) $this->exec("INSERT INTO pql_test(val) VALUES($i)");
		$this->pql()->test->val->between(3,5)->set(11)->update();
		$this->assertEquals(array_merge(range(0, 2), array_fill(0, 3, 11), range(6, 9)), $this->pql()->test->val->toArray());
	}


	function testDeleteQueryAll() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val INT)");
		for($i = 0; $i < 10; $i++) $this->exec("INSERT INTO pql_test(val) VALUES($i)");
		$this->pql()->test->val->delete();
		$this->assertEquals(0, $this->pql()->test->count());
	}
	
	
	function testForeignObject() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		// схема базы
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_id INT)");
		// записи
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(test_id, val) VALUES($id, 'b_first')");
		$id = $this->lastInsertId();
		
		$this->pql->coding(new pQL_Coding_Typical);
		$b = $this->pql()->testB->one();
		$this->assertType('pQL_Object', $b->test);
		$this->assertEquals('first', $b->test->val);
	}
	
	
	function testQueryOffsetGet() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		$vals = array('one', 'two', 'three', null);
		foreach($vals as $val) $this->exec("INSERT INTO pql_test(val) VALUES(".$this->quote($val).")");
		
		$q = $this->pql()->test['val'];
		$this->assertEquals($vals, $q->toArray(), "SQL: $q");
	}
	
	
	function testOffsetTableExists() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		$pql = $this->pql();
		$this->assertTrue(isset($pql['test']));
		$this->assertFalse(isset($pql['waka']));
	}
	
	
	function testOffsetFieldExists() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(255))");
		$this->assertTrue(isset($this->pql()->test['val']));
		$this->assertFalse(isset($this->pql()->test['wtf']));
		$pql = $this->pql();
		$this->assertTrue(isset($pql['test']['val']));
		$this->assertFalse(isset($pql['test']['wtf']));
	}


	function testForeignObjectUsingForeingKey() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(id ".$this->getPKExpr().", val VARCHAR(255)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), a INT, FOREIGN KEY(a) REFERENCES pql_test_a(id)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a(val) VALUES('first')");
		$this->exec("INSERT INTO pql_test_a(val) VALUES('middle')");
		$aId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_a(val) VALUES('last')");
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES($aId-1, 'b_first')");
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES($aId,   'b_middle')");
		$bId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES($aId+1, 'b_last')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$b = $this->pql()->testB($bId);
		$this->assertType('pQL_Object', $b->a);
		$this->assertEquals('middle', $b->a->val);

		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
	
	
	function testGetNullForEmptyForeignObjectProperty() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(id ".$this->getPKExpr().", val VARCHAR(255)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), a INT, FOREIGN KEY(a) REFERENCES pql_test_a(id)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a(val) VALUES('first')");
		$aId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(val) VALUES('b_first')");
		$bId = $this->lastInsertId();

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$b = $this->pql()->testB($bId);
		$this->assertNull($b->a);
		
		$b = $this->pql()->testB();
		$b->val = 'b_middle';
		$b->save();
		$this->assertNull($b->a);

		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
	


	function testForeignObjectUsingForeingKeyAndNotPK() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(id ".$this->getPKExpr().", val VARCHAR(255), num INT UNIQUE) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), a INT, FOREIGN KEY(a) REFERENCES pql_test_a(num)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a(val, num) VALUES('first', 5)");
		$this->exec("INSERT INTO pql_test_a(val, num) VALUES('middle', 25)");
		$this->exec("INSERT INTO pql_test_a(val, num) VALUES('last', 86)");
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES(5, 'b_first')");
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES(25,   'b_middle')");
		$bId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(a, val) VALUES(86, 'b_last')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$b = $this->pql()->testB($bId);
		$this->assertType('pQL_Object', $b->a);
		$this->assertEquals('middle', $b->a->val);

		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
	
	
	/**
	 * @TODO
	 */
	function _testForeignObjectUsingCombineForeingKey() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(aa CHAR(16), ab CHAR(16), ac CHAR(16), PRIMARY KEY(ab, ac)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(ba CHAR(16), bb CHAR(16), bc CHAR(16), FOREIGN KEY(bb, bc) REFERENCES pql_test_a(ab, ac)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_last', 'ab_last', 'ac_last')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba1', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba3', 'ab_first', 'ac_first')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$b = $this->pql()->testB->ba->in('ba2')->one();
		$this->assertEquals('aa_second2', $this->pql()->a($b)->aa);

		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}


	/**
	 * @TODO
	 */
	function _testFindObjectByCombinePK() {
		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';
		$this->exec("CREATE TABLE pql_test(aa CHAR(16), ab CHAR(16), ac CHAR(16), PRIMARY KEY(ab, ac)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test VALUES('aa_last', 'ab_last', 'ac_last')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);
		$this->assertEquals('aa_second2', $this->pql()->a('ab_second', 'ac_second2')->aa);
	}


	function testNotFoundByPk() {
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		$this->assertNull($this->pql()->test(6));
	}
	
	
	private function getClassNameTestResultObjects() {
		return array(
			// новый объект
			'test'=>$this->pql()->test(),
			'testB'=>$this->pql()->testB(),
			
			// полученный объект
			'test'=>$this->pql()->test->one(),
			'testB'=>$this->pql()->testB->one(),

			// связанный объект
			'test'=>$this->pql()->testB->one()->test,
		);
	}
	
	
	function testResultClassName() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		// схема базы
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_id INT)");
		// записи
		$this->exec("INSERT INTO pql_test(val) VALUES('first')");
		
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_b(test_id, val) VALUES($id, 'b_first')");
		$id = $this->lastInsertId();
		
		$this->pql->coding(new pQL_Coding_Typical);
		$orgClassName = $this->pql->className();

		foreach($this->getClassNameTestResultObjects() as $object) {
			// проверяем что является pQL_Object
			$this->assertType('pQL_Object', $object);
			// и не является нашим классом
			$this->assertNotType('pQL_Driver_Test_Object', $object);
		}

		// меняем на наш класс
		$this->pql->className('pQL_Driver_Test_Object');
		foreach($this->getClassNameTestResultObjects() as $a) {
			$this->assertType('pQL_Object', $a);
			$this->assertType('pQL_Driver_Test_Object', $a);
		}

		// восстанавливаем
		$this->pql->className($orgClassName);
		foreach($this->getClassNameTestResultObjects() as $object) {
			// проверяем что является pQL_Object
			$this->assertType('pQL_Object', $object);
			// и не является нашим классом
			$this->assertNotType('pQL_Driver_Test_Object', $object);
		}

		// меняем на наш загрузчик классов
		$this->pql->objectDefinder(new pQL_Driver_Test_Object_Definer);
		foreach($this->getClassNameTestResultObjects() as $model=>$object) {
			$className = 'pQL_Driver_Test_'.ucfirst($model);
			$this->assertType($className, $object);
		}
		
		$this->exec("DROP TABLE pql_test_b");
	}
	
	
	function testObjectDefinerWrongClassName() {
		// пробуем неправильный класс
		try {
			$this->pql->className('stdClass');
			$this->assertFalse(true, 'Expected exception pQL_Object_Definer_Exception');
		}
		catch (pQL_Object_Definer_Exception $e) {
			
		}
		
		// ничего не поломалось? 
		$this->pql()->test();
	}
	
	
	function testObjectDefinerInterface() {
		// проверяем работу интерфейса pQL_Object_Definer_Interface
		$definer = $this->getMock('pQL_Object_Definer_Interface', array('getObject'));
		$definer->expects($this->once())
			->method('getObject')
			->with($this->equalTo($this->pql), $this->equalTo('test'), array())
			->will($this->returnValue(new pQL_Object_Model($this->pql, array(), 'test')));
		$this->pql->objectDefinder($definer);
		$this->pql()->test();
	}
	
	
	function testObjectDefinerWrongObject() {
		// а если определитель объектов вернет неправильный класс?
		$definer = $this->getMock('pQL_Object_Definer_Interface', array('getObject'));
		$definer->expects($this->once())
			->method('getObject')
			->with($this->equalTo($this->pql), $this->equalTo('test'), array())
			->will($this->returnValue(new stdClass));
		$this->pql->objectDefinder($definer);
		$this->setExpectedException('pQL_Object_Definer_Exception');
		$this->pql()->test();
	}
	
	
	function testJoinUsingThirdTable() {
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_c");
		$this->exec("DROP TABLE IF EXISTS pql_test_d");
		// схема базы
		$this->exec("CREATE TABLE pql_test_a(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_a_id INT)");
		$this->exec("CREATE TABLE pql_test_c(id ".$this->getPKExpr().", val VARCHAR(255), test_b_id INT)");
		$this->exec("CREATE TABLE pql_test_d(id ".$this->getPKExpr().", val VARCHAR(255), test_c_id INT)");
		// записи
		$this->exec("INSERT INTO pql_test_a(val) VALUES('first')");
		$this->exec("INSERT INTO pql_test_a(val) VALUES('second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_a(val) VALUES('last')");
		
		$this->exec("INSERT INTO pql_test_b(test_a_id, val) VALUES($id, 'b_second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_c(test_b_id, val) VALUES($id, 'c_second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_d(test_c_id, val) VALUES($id, 'd_second')");
		$id = $this->lastInsertId();
		
		$this->pql->coding(new pQL_Coding_Typical);

		$val = $this->pql()->testD->val->in('d_second')->db()->testA->val->one();
		$this->assertEquals('second', $val);

		$this->exec("DROP TABLE IF EXISTS pql_test_a");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_c");
		$this->exec("DROP TABLE IF EXISTS pql_test_d");
	}
	
	
	function testJoinUsingThirdTableWith_S_Postfix() {
		$this->exec("DROP TABLE IF EXISTS pql_test_categories");
		$this->exec("DROP TABLE IF EXISTS pql_test_items");
		// схема базы
		$this->exec("CREATE TABLE pql_test_categories(id ".$this->getPKExpr().", name VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_items(id ".$this->getPKExpr().", name VARCHAR(255), category_id INT)");
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", category INT, item INT)");
		
		// записи
		$this->exec("INSERT INTO pql_test_categories(name) VALUES('cat_first')");
		$this->exec("INSERT INTO pql_test_categories(name) VALUES('cat_second')");
		$catId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_categories(name) VALUES('cat_last')");
		
		$this->exec("INSERT INTO pql_test_items(name, category_id) VALUES('item_first', null)");
		$this->exec("INSERT INTO pql_test_items(name, category_id) VALUES('item_second', $catId)");
		$itemId = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_items(name, category_id) VALUES('item_last', 0)");
		
		$this->exec("INSERT INTO pql_test(category, item) VALUES(null, null)");
		$this->exec("INSERT INTO pql_test(category, item) VALUES($catId, $itemId)");
		$this->exec("INSERT INTO pql_test(category, item) VALUES(0, 0)");

		// assert
		$this->pql->coding(new pQL_Coding_Typical);
		$this->assertEquals(array('cat_second'), $this->pql()->testItems->id->in($itemId)->db()->testCategories->name->toArray());


		$found = false;
		foreach($this->pql()->test->db()->testItems->name->bind($item)->db()->testCategories->name->bind($cat) as $test) {
			if ($found) $this->fail();
			$this->assertEquals('cat_second', $cat);
			$this->assertEquals('item_second', $item);
			$found = true;
		}
		if (!$found) $this->fail();
		
		
		$this->exec("DROP TABLE IF EXISTS pql_test_categories");
		$this->exec("DROP TABLE IF EXISTS pql_test_items");
	}
	
	
	function testLazyLoad() {
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(id ".$this->getPKExpr().", val VARCHAR(32))");
		$this->exec("INSERT INTO pql_test(val) VALUES('$val')");
		$id = $this->lastInsertId();
		
		$object = new pQL_Object_Model($this->pql, array('id'=>$id), 'test');
		$this->assertEquals($val, $object->val);
	}


	function testJoinUsingTwoColumnForeingKey() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
		
		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';
	
		// схема базы
		$this->exec("CREATE TABLE pql_test_a(aa CHAR(16), ab CHAR(16), ac CHAR(16), PRIMARY KEY(ab, ac)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(ba CHAR(16), bb CHAR(16), bc CHAR(16), FOREIGN KEY(bb, bc) REFERENCES pql_test_a(ab, ac)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_last', 'ab_last', 'ac_last')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba1', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba3', 'ab_first', 'ac_first')");
		
		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$val = $this->pql()->testB->ba->in('ba2')->db()->testA->aa->one();
		$this->assertEquals('aa_second2', $val);
		
		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
	
	
	function testExprWithForeignObject() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(val VARCHAR(255), id ".$this->getPKExpr().") $createOpt");
		$this->exec("CREATE TABLE pql_test_b(val VARCHAR(255), id ".$this->getPKExpr().", a INT, FOREIGN KEY(a) REFERENCES pql_test_a(id)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a(id, val) VALUES(1, 'aa_first')");
		$this->exec("INSERT INTO pql_test_a(id, val) VALUES(2, 'aa_second1')");
		$this->exec("INSERT INTO pql_test_a(id, val) VALUES(3, 'aa_second2')");
		$this->exec("INSERT INTO pql_test_a(id, val) VALUES(4, 'aa_last')");
		$this->exec("INSERT INTO pql_test_b(id, a, val) VALUES(1, 1, 'b_first')");
		$this->exec("INSERT INTO pql_test_b(id, a, val) VALUES(2, 2, 'b_second')");
		$this->exec("INSERT INTO pql_test_b(id, a, val) VALUES(3, 1, 'b_last')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		// test
		$a = $this->pql()->testA(2);

		// test IN
		$b = $this->pql()->testB->a->in($a)->one();
		$this->assertEquals('b_second', $b->val);

		// test NOT
		$this->assertEquals(array('b_first', 'b_last'), $this->pql()->testB->a->not($a)->val->toArray());

		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}


	function testQueryByObject() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(aa CHAR(16), ab CHAR(16), ac CHAR(16), PRIMARY KEY(ab, ac)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(ba CHAR(16), bb CHAR(16), bc CHAR(16), FOREIGN KEY(bb, bc) REFERENCES pql_test_a(ab, ac)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_last', 'ab_last', 'ac_last')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba1', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba3', 'ab_first', 'ac_first')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);
		
		// test
		$a = $this->pql()->testA->aa->in('aa_second2')->one();
		$b = $this->pql()->testB->with($a)->one();
		$this->assertEquals('ba2', $b->ba);
		
		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}


	/**
	 * @todo
	 * @return void
	 */
	function _testGetObjectByForeignObject() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");

		$createOpt = stripos(get_class($this), 'mysql') ? ' engine=INNODB' : '';

		// схема базы
		$this->exec("CREATE TABLE pql_test_a(aa CHAR(16), ab CHAR(16), ac CHAR(16), PRIMARY KEY(ab, ac)) $createOpt");
		$this->exec("CREATE TABLE pql_test_b(ba CHAR(16), bb CHAR(16), bc CHAR(16), FOREIGN KEY(bb, bc) REFERENCES pql_test_a(ab, ac)) $createOpt");

		// записи
		$this->exec("INSERT INTO pql_test_a VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_last', 'ab_last', 'ac_last')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba1', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba3', 'ab_first', 'ac_first')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);
		$b = $this->pql()->testB->ba->in('ba2')->one();
		$a = $this->pql()->testA($b);
		$this->assertEquals('aa_second2', $a->aa);
	}
}