<?php
require_once dirname(__FILE__).'/bootstrap.php';

abstract class pQL_Driver_PDO_Test_Abstract extends pQL_Driver_Test_Abstract {
	protected $db;
	protected $pql;
	
	
	function __construct() {
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		parent::__construct();
	}
	
	
	function __destruct() {
		$this->db = null;
		parent::__destruct();
	}
	
	
	function exec($sql) {
		return $this->db->exec($sql);
	}
	
	
	
	function setUpPql() {
		$this->pql = pQL::PDO($this->db);
	}
}