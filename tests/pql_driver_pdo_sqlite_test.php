<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_Driver_PDO_SQLite_Test extends pQL_Driver_PDO_Test_Abstract {
	private $sqlite = 'pql_test.sqlite3';
	function __construct() {
		$this->db = new PDO("sqlite:$this->sqlite");
		parent::__construct();
	}


	function __destruct() {
		parent::__destruct();
		if (file_exists($this->sqlite)) unlink($this->sqlite);
	}
	
	
	function setUp() {
		parent::setUp();
		$this->db->beginTransaction();
	}
	
	
	function tearDown() {
		$this->db->rollBack();
		parent::tearDown();
	}
	
	
	function testFindByPk() {
		$this->db->exec("CREATE TABLE pql_test(id INTEGER PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
		$this->assertTrue(ctype_digit("$id"));
		$object = $this->pql()->test($id);
		$this->assertEquals($id, $object->id);
		$this->assertTrue($object instanceof pQL_Object);
		
		// несколько записей
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->assertEquals($id, $this->pql()->test($id)->id);

		// custom pk
		$val = md5(microtime(true));
		$this->db->exec("DROP TABLE pql_test");
		$this->db->exec("CREATE TABLE pql_test(first_col INT, val VARCHAR(32) PRIMARY KEY, last_col INT)");
		$this->db->exec("INSERT INTO pql_test VALUES(null, '$val', null)");
		$this->assertEquals($val, $this->pql()->test($val)->val);
	}
	
	
	function testTablePrefix() {
		$this->db->exec("CREATE TABLE IF NOT EXISTS pql_myprefix_test(id INTEGER PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_myprefix_test VALUES(null)");
		$id = $this->db->lastInsertId();
		$this->pql->tablePrefix('pql_myprefix_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->db->exec("CREATE TABLE pql_test(id INTEGER PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_test SELECT * FROM pql_myprefix_test");
		$this->db->exec("DROP TABLE pql_myprefix_test");
		$this->db->exec("INSERT INTO pql_test VALUES(null)");
		$id = $this->db->lastInsertId();
		$this->pql->tablePrefix('pql_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
	}
	
	
	protected function getPKExpr() {
		return 'INTEGER PRIMARY KEY';
	}
	
	
	function testJoinUsingForeingKey() {
		$this->exec("DROP TABLE IF EXISTS pql_test_d");
		$this->exec("DROP TABLE IF EXISTS pql_test_c");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	
		// схема базы
		$this->exec("CREATE TABLE pql_test_a(i ".$this->getPKExpr().", val VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(i ".$this->getPKExpr().", val VARCHAR(255), f INT REFERENCES pql_test_a(i))");
		$this->exec("CREATE TABLE pql_test_c(i ".$this->getPKExpr().", val VARCHAR(255), f INT REFERENCES pql_test_b(i))");
		$this->exec("CREATE TABLE pql_test_d(i ".$this->getPKExpr().", val VARCHAR(255), f INT REFERENCES pql_test_c(i))");
		
		// записи
		$this->exec("INSERT INTO pql_test_a(val) VALUES('first')");
		$this->exec("INSERT INTO pql_test_a(val) VALUES('second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_a(val) VALUES('last')");
		
		$this->exec("INSERT INTO pql_test_b(f, val) VALUES($id, 'b_second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_c(f, val) VALUES($id, 'c_second')");
		$id = $this->lastInsertId();
		$this->exec("INSERT INTO pql_test_d(f, val) VALUES($id, 'd_second')");
		$id = $this->lastInsertId();

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$val = $this->pql()->testD->val->in('d_second')->db()->testA->val->one();
		$this->assertEquals('second', $val);
		
		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_d");
		$this->exec("DROP TABLE IF EXISTS pql_test_c");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
	
	
	function testJoinUsingTwoColumnForeingKey() {
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
		
		// схема базы
		$this->exec("CREATE TABLE pql_test_a(aa VARCHAR(255), ab VARCHAR(255), ac VARCHAR(255))");
		$this->exec("CREATE TABLE pql_test_b(ba VARCHAR(255), bb VARCHAR(255), bc VARCHAR(255), FOREIGN KEY(bb, bc) REFERENCES pql_test_a(ab, ac))");
		
		// записи
		$this->exec("INSERT INTO pql_test_a VALUES('aa_first', 'ab_first', 'ac_first')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second1', 'ab_second', 'ac_second1')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_second2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_a VALUES('aa_last', 'ab_last', 'ac_last')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba1', 'bb1', 'bc1')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba2', 'ab_second', 'ac_second2')");
		$this->exec("INSERT INTO pql_test_b VALUES('ba3', 'bb3', 'bc3')");

		// pql
		$this->pql->coding(new pQL_Coding_Typical);

		$val = $this->pql()->testB->ba->in('ba2')->db()->testA->aa->one();
		$this->assertEquals('aa_second2', $val);
		
		// tearDown
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("DROP TABLE IF EXISTS pql_test_a");
	}
}
