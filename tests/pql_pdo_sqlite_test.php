<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_PDO_SQLite_Test extends PHPUnit_Framework_TestCase {
	private $sqlite = 'pql_test.sqlite3';
	function __construct() {
		parent::__construct();
		$this->db = new PDO("sqlite:$this->sqlite");
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pql = pQL::PDO($this->db);
		$this->tearDown();
	}
	
	
	function __destruct() {
		$this->db = null;
		$this->pql = null;
		if (file_exists($this->sqlite)) unlink($this->sqlite);
	}
	
	
	function pql() {
		return $this->pql->creater();
	}
	
	
	function setUp() {
		$this->pql->tablePrefix('pql_');
	}
	
	
	function tearDown() {
		$this->db->exec("DROP TABLE IF EXISTS pql_test");
	}
	
	
	function testFindByPk() {
		$this->db->exec("CREATE TABLE pql_test(id INTEGER PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
		$this->assertEquals($id, $this->pql()->test($id)->id);
		
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
}
