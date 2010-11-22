<?php
final class pQL_Query_Predicate {
	const TYPE_CLASS = 1;
	const TYPE_PROPERTY = 2;
	const TYPE_KEY = 3;
	const TYPE_IN = 4;


	private $type;
	private $subject;

	
	function getType() {
		return $this->type;
	}
	
	
	function getSubject() {
		return $this->subject;
	}
	

	function __construct($type, $subject = null) {
		$this->type = $type;
		$this->subject = $subject;
	}
} 