<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_Driver_PDO_MySQL_Test extends pQL_Driver_PDO_Test_Abstract {
	function __construct() {
		$this->db = new PDO('mysql:host=localhost;dbname=test', 'test', 'test');
		parent::__construct();
		$this->tearDown();
	}


	function testFindByPk() {
		$this->db->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
		$this->assertTrue(ctype_digit("$id"));
		$object = $this->pql()->test($id);
		$this->assertEquals($id, $object->id);
		$this->assertTrue($object instanceof pQL_Object);

		$this->pql->clearCache();

		// несколько записей
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->assertEquals($id, $this->pql()->test($id)->id);

		$this->pql->clearCache();

		// custom pk
		$val = md5(microtime(true));
		$this->db->exec("DROP TABLE pql_test");
		$this->db->exec("CREATE TABLE pql_test(first_col INT, val VARCHAR(32) PRIMARY KEY, last_col INT)");
		$this->db->exec("INSERT INTO pql_test VALUES(null, '$val', null)");
		$this->assertEquals($val, $this->pql()->test($val)->val);

		$this->pql->clearCache();
		
		// any pk
		$this->db->exec('ALTER TABLE `pql_test` CHANGE `val` `and` VARCHAR( 32 ) NOT NULL');
		$this->assertEquals($val, $this->pql()->test($val)->and);
	}
	
	
	function testTablePrefix() {
		$this->db->exec("CREATE TABLE IF NOT EXISTS pql_myprefix_test(id INT AUTO_INCREMENT PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_myprefix_test VALUES(null)");
		$id = $this->db->lastInsertId();
		$this->pql->tablePrefix('pql_myprefix_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->db->exec("RENAME TABLE pql_myprefix_test TO pql_test");
		$this->db->exec("INSERT INTO pql_test VALUES(null)");
		$id = $this->db->lastInsertId();
		$this->pql->tablePrefix('pql_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
	}


	function testFieldNameTranslate() {
		$this->pql->coding(new pQL_Coding_Typical);
		$val = md5(microtime(true));
		$this->db->exec("CREATE TABLE pql_test(my_long_property VARCHAR(32) PRIMARY KEY)");
		$obj = $this->pql()->test();
		$obj->myLongProperty = $val;
		$obj->save();
		$this->assertEquals($val, $this->pql()->test($val)->myLongProperty);
	}
	
	
	protected function getPKExpr() {
		return 'INT AUTO_INCREMENT PRIMARY KEY';
	}

	function testPkForView() {
		$this->db->exec("DROP VIEW IF EXISTS pql_test_view");
		$val = md5(microtime(true));
		$this->db->exec("CREATE TABLE pql_test(val VARCHAR(32) PRIMARY KEY, last_col INT)");
		$this->db->exec("INSERT INTO pql_test VALUES('$val', null)");
		$this->db->exec("CREATE VIEW pql_test_view AS SELECT * FROM pql_test");

		$this->pql->clearCache();

		$this->assertEquals($val, $this->pql()->test_view->one()->val, 'test select');
		$this->assertEquals($val, $this->pql()->test_view($val)->val, 'test pk');

		$this->db->exec("DROP TABLE pql_test");
		$this->db->exec("DROP VIEW pql_test_view");
	}

	function testPkForViewForeign() {
		$this->db->exec("DROP TABLE IF EXISTS pql_test_tbl");
		$this->db->exec("DROP VIEW IF EXISTS pql_test_view");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		// схема базы
		$this->exec("CREATE TABLE pql_test_tbl(id ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(id ".$this->getPKExpr().", val VARCHAR(255), test_view_id INT)");
		$this->exec("CREATE VIEW pql_test_view AS SELECT * FROM pql_test_tbl");
		// записи
		$this->exec("INSERT INTO pql_test_tbl(id, val) VALUES(1, 'first')");
		$this->exec("INSERT INTO pql_test_tbl(id, val) VALUES(2, 'second')");
		$this->exec("INSERT INTO pql_test_tbl(id, val) VALUES(3, 'last')");
		$this->exec("INSERT INTO pql_test_b(test_view_id, val) VALUES(2, 'b_second')");

		$this->pql->coding(new pQL_Coding_Typical);

		$testView = $this->pql()->testView(2);

		$b = $this->pql()->testB->testView->in($testView)->one();

		$this->assertInstanceOf('pQL_Object', $b->testView);
		$this->assertEquals('second', $b->testView->val);

		$this->db->exec("DROP TABLE IF EXISTS pql_test_tbl");
		$this->db->exec("DROP VIEW IF EXISTS pql_test_view");
	}
}
