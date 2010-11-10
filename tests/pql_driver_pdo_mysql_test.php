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
	
	
	function testTableNameTranslate() {
		$this->db->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->db->exec("CREATE TABLE pql_test_b(id INT AUTO_INCREMENT PRIMARY KEY)");
		$id = $this->pql()->testB()->save()->id;
		$this->assertEquals($id, $this->db->query("SELECT id FROM pql_test_b")->fetch(PDO::FETCH_OBJ)->id);
		$this->db->exec("DROP TABLE pql_test_b");
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


	function testCreate() {
		$val = md5(microtime(true));
		
		$this->db->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY, val TEXT)");
		
		$object = $this->pql()->test();
		$this->assertTrue($object instanceof pQL_Object);
		$this->assertTrue(empty($object->id));
		$object->val = $val;
		$object->save();

		$id = $this->db->lastInsertId();
		$this->assertEquals($val, $this->db->query("SELECT val FROM pql_test WHERE val = '$val'")->fetch(PDO::FETCH_OBJ)->val);
		$this->assertEquals($id, $object->id);
		$this->assertEquals($val, $this->pql()->test($id)->val);
		
		// custom id field
		$this->db->exec("DROP TABLE pql_test");
		$this->db->exec("CREATE TABLE pql_test(val TEXT, my_int INT AUTO_INCREMENT PRIMARY KEY)");
		$this->db->exec("INSERT INTO pql_test(val) VALUES('first'),('second')");
		$val = md5(microtime(true));
		$object = $this->pql()->test();
		$object->val = $val;
		$object->save();
		$id = $this->db->lastInsertId();
		$this->db->exec("INSERT INTO pql_test(val) VALUES('last')");

		$this->assertEquals($val, $this->db->query("SELECT val FROM pql_test WHERE val = '$val'")->fetch(PDO::FETCH_OBJ)->val);
		$this->assertEquals($id, $object->my_int);
		$this->assertEquals($val, $this->pql()->test($id)->val);
	}
	
	
	function testErrorOnSaveWithoutPK() {
		$this->db->exec("CREATE TABLE pql_test(val VARCHAR(225))");
		$this->setExpectedException('pQL_Exception_PrimaryKeyNotExists');
		$this->pql()->test()->save();
	}
}
