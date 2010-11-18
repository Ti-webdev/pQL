<?php
final class pQL_Query_Predicate_List extends SplDoublyLinkedList {
	function add($predicate) {
		if ($predicate instanceof pQL_Query_Predicate) return parent::push($predicate);
		throw new InvalidArgumentException;
	}
}
