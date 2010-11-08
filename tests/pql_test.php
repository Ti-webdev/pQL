<?php
error_reporting(E_ALL|E_STRICT);
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../lib');
spl_autoload_register('spl_autoload');

class pQL_Test extends PHPUnit_Framework_TestCase {
	private $sqlite = 'pql_test.sqlite3';
	private $pk;
	function __construct() {
		parent::__construct();
		
		//$this->db = new PDO('mysql:host=localhost;dbname=test', 'test', 'test');
		//$this->pk = 'INT AUTO_INCREMENT PRIMARY KEY';
		
		$this->db = new PDO("sqlite:$this->sqlite");
		$this->pk = 'INTEGER PRIMARY KEY';
		
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
		$this->db->exec("CREATE TABLE pql_test(id $this->pk)");
		$this->db->exec("INSERT INTO pql_test VALUES(NULL)");
		$id = $this->db->lastInsertId();
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
		
		/**
		 * @todo несколько записей
		 */
	}
	
	
	function _testTablePrefix() {
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
}
