<?php
/**
 * @author: Ti
 * @date: 21.02.11
 * @time: 14:09
 */
 
class pQL_Query_Expr {
	function __construct($expr = null) {
		if (!is_null($expr)) $this->push($expr);
	}


	private $expr = '';
	private $injections = array();


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
