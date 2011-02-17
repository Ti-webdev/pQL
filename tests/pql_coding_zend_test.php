<?php
require_once dirname(__FILE__).'/bootstrap.php';


class pQL_Coding_Zend_Test extends PHPUnit_Framework_TestCase {
	function testOneWordFromDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('Test', $coding->fromDb('test'));
	}


	function testOneWordToDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('test', $coding->toDB('Test'));
	}


	function testTwoWordsFromDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('Test_Name', $coding->fromDb('test_name'));
	}


	function testTwoWordsToDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('test_name', $coding->toDB('Test_Name'));
	}


	function test5WordsFromDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('My_Supper_Class_Test_Name', $coding->fromDb('my_supper_class_test_name'));
	}


	function test5WordsToDb() {
		$coding = new pQL_Coding_Zend;
		$this->assertEquals('my_supper_class_test_name', $coding->toDB('My_Supper_Class_Test_Name'));
	}
}