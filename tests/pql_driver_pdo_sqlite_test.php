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
}
