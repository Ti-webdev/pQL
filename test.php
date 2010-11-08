<?php
class waka extends ArrayIterator {
	private $bind = array();
	function bind(&$var) {
		$this->bind[] = &$var;
	}
	
	function current() {
		$this->bind[0] = $this->key();
		return $this->bind[1] = parent::current();
	}
}


$a = new waka(array('a', 'b', 'c', 'd', 'e'));
$a->bind($i);
$a->bind($j);
foreach($a as $k=>$v) {
	echo "$k - $i: $v - $j<br />";
}
