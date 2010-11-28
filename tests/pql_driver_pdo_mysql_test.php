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
}
