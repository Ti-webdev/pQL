<?php
/**
 * @author: Ti
 * @date: 21.02.11
 * @time: 14:09
 */
 
class pQL_Query_Expr {
	function __construct($prefix = '', $suffix = '') {
		$this->prefix = $prefix;
		$this->suffix = $suffix;
	}


	private $expr = '';
	private $injections = array();
	private $isPrefix = true;
	private $prefix;
	private $suffix;
	
	
	function pushArray($args) {
		if ($this->isPrefix) {
			$this->expr .= $this->prefix;
			$this->isPrefix = false;
		}
		else {
			$this->expr .= $this->suffix;
		}
		foreach($args as $arg) $this->push($arg);
	}

	
	function push($expr) {
		if (is_object($expr) and $expr instanceof pQL_Query_Builder_Field) {
			$pos = strlen($this->expr);
			$this->injections[$pos] = $expr;
		}
		else {
			$this->expr .= $expr;
		}
	}


	function get(pQL_Query_Builder $qb, $withAlias = true) {
		$result = $this->expr;
		krsort($this->injections);
		foreach($this->injections as $position=>$injection) {
			if (is_object($injection)) { /* @var $injection pQL_Query_Builder_Field */
				$center = $qb->getField($injection, $withAlias);
			}
			else {
				$center = $injection;
			}
			$result = substr($result, 0, $position).$center.substr($result, $position);
		}
		return $result;
	}


	function isEmpty() {
		return '' === $this->expr;
	}
}
