<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_PDO_MySQL_Test extends PHPUnit_Framework_TestCase {
	function __construct() {
		parent::__construct();
		$this->db = new PDO('mysql:host=localhost;dbname=test', 'test', 'test');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pql = pQL::PDO($this->db);
		$this->tearDown();
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
		$this->db->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY)");
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
	
	
	function testCreate() {
		$val = md5(microtime(true));
		
		$this->db->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY, val TEXT)");
		
		$object = $this->pql()->test();
		$this->assertTrue($object instanceof pQL_Object);
		$this->assertTrue(empty($object->id));
		$object->val = $val;
		$object->save();

		$this->assertEquals($val, $this->db->query("SELECT val FROM pql_test WHERE val = '$val'")->fetch(PDO::FETCH_OBJ)->val);
		$id = $this->db->lastInsertId();

		$this->assertEquals($id, $object->id);
		$this->assertEquals($val, $this->pql()->test($id)->val);
		
		// custom id field
	}
}
