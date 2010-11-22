<?php
require_once dirname(__FILE__).'/bootstrap.php';

class pQL_Driver_MySQL_Test extends pQL_Driver_Test_Abstract {
	function __construct() {
		$this->db = mysql_connect('localhost', 'test', 'test');
		mysql_select_db('test', $this->db);
		parent::__construct();
		$this->tearDown();
	}


	function setUpPql() {
		$this->pql = pQL::MySQL($this->db);
	}


	function exec($sql) {
		$result = mysql_query($sql, $this->db); 
		if (!$result) throw new Exception(mysql_error($this->db)."\nSQL: $sql");
		return $result;
	}
	
	
	function quote($val) {
		return is_null($val) ? 'NULL' : '"'.mysql_real_escape_string($val, $this->db).'"';
	}


	function testFindByPk() {
		$this->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY)");
		$this->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = mysql_insert_id($this->db);
		$this->assertTrue(ctype_digit("$id"));
		$object = $this->pql()->test($id);
		$this->assertEquals($id, $object->id);
		$this->assertTrue($object instanceof pQL_Object);
		
		// несколько записей
		$this->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = mysql_insert_id($this->db);
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->exec("INSERT INTO pql_test VALUES(NULL)");
		$this->assertEquals($id, $this->pql()->test($id)->id);
		

		// custom pk
		$val = md5(microtime(true));
		$this->exec("DROP TABLE pql_test");
		$this->exec("CREATE TABLE pql_test(first_col INT, val VARCHAR(32) PRIMARY KEY, last_col INT)");
		$this->exec("INSERT INTO pql_test VALUES(null, '$val', null)");
		$this->assertEquals($val, $this->pql()->test($val)->val);

		// any pk
		$this->exec('ALTER TABLE `pql_test` CHANGE `val` `and` VARCHAR( 32 ) NOT NULL');
		$this->assertEquals($val, $this->pql()->test($val)->and);
	}
	
	
	function testTablePrefix() {
		$this->exec("CREATE TABLE IF NOT EXISTS pql_myprefix_test(id INT AUTO_INCREMENT PRIMARY KEY)");
		$this->exec("INSERT INTO pql_myprefix_test VALUES(null)");
		$id = mysql_insert_id($this->db);
		$this->pql->tablePrefix('pql_myprefix_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
		$this->exec("RENAME TABLE pql_myprefix_test TO pql_test");
		$this->exec("INSERT INTO pql_test VALUES(null)");
		$id = mysql_insert_id($this->db);
		$this->pql->tablePrefix('pql_');
		$this->assertEquals($id, $this->pql()->test($id)->id);
	}
	
	
	function testTableNameTranslate() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id INT AUTO_INCREMENT PRIMARY KEY)");
		$id = $this->pql()->testB()->save()->id;
		$this->assertEquals($id, mysql_result($this->exec("SELECT id FROM pql_test_b", $this->db), 0, 0));
		$this->exec("DROP TABLE pql_test_b");
	}


	function testFieldNameTranslate() {
		$this->pql->coding(new pQL_Coding_Typical);
		$val = md5(microtime(true));
		$this->exec("CREATE TABLE pql_test(my_long_property VARCHAR(32) PRIMARY KEY)");
		$obj = $this->pql()->test();
		$obj->myLongProperty = $val;
		$obj->save();
		$this->assertEquals($val, $this->pql()->test($val)->myLongProperty);
	}


	function testCreate() {
		$val = md5(microtime(true));
		
		$this->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY, val TEXT)");
		
		$object = $this->pql()->test();
		$this->assertTrue($object instanceof pQL_Object);
		$this->assertTrue(empty($object->id));
		$object->val = $val;
		$object->save();

		$id = mysql_insert_id($this->db);
		$this->assertEquals($val, mysql_result(mysql_query("SELECT val FROM pql_test WHERE val = '$val'", $this->db), 0, 0));
		$this->assertEquals($id, $object->id);
		$this->assertEquals($val, $this->pql()->test($id)->val);
		
		// custom id field
		$this->exec("DROP TABLE pql_test");
		$this->exec("CREATE TABLE pql_test(val TEXT, my_int INT AUTO_INCREMENT PRIMARY KEY)");
		$this->exec("INSERT INTO pql_test(val) VALUES('first'),('second')");
		$val = md5(microtime(true));
		$object = $this->pql()->test();
		$object->val = $val;
		$object->save();
		$id = mysql_insert_id($this->db);
		$this->exec("INSERT INTO pql_test(val) VALUES('last')");

		$this->assertEquals($val, mysql_result(mysql_query("SELECT val FROM pql_test WHERE val = '$val'", $this->db), 0, 0));
		$this->assertEquals($id, $object->my_int);
		$this->assertEquals($val, $this->pql()->test($id)->val);
	}
	
	
	function testErrorOnSaveWithoutPK() {
		$this->exec("CREATE TABLE pql_test(val VARCHAR(225))");
		$this->setExpectedException('pQL_Exception_PrimaryKeyNotExists');
		$this->pql()->test()->save();
	}
	
	
	function testSaveForeignObject() {
		$this->pql->coding(new pQL_Coding_Typical);
		$this->exec("CREATE TABLE pql_test(id INT AUTO_INCREMENT PRIMARY KEY)");
		$this->exec("DROP TABLE IF EXISTS pql_test_b");
		$this->exec("CREATE TABLE pql_test_b(id INT AUTO_INCREMENT PRIMARY KEY, test INT)");
		
		$object = $this->pql()->test()->save();
		$objectB = $this->pql()->testB();
		$objectB->test = $object;
		$objectB->save();

		$this->assertEquals($object->id, mysql_result(mysql_query("SELECT test FROM pql_test_b", $this->db), 0, 0));

		$this->exec("DROP TABLE pql_test_b");
	}
}
