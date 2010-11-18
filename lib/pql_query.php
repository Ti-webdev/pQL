<?php
final class pQL_Query implements IteratorAggregate {
	private $pQL;
	function __construct(pQL $pQL) {
		$this->pQL = $pQL;
		$this->stack = new pQL_Query_Predicate_List;
	}
	
	
	private $query = array();
	function __get($key) {
		$type = pQL_Query_Predicate::TYPE_CLASS;
		$this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO);
		foreach($this->stack as $predicate) {
			if (pQL_Query_Predicate::TYPE_CLASS === $predicate->getType()) {
				$type = pQL_Query_Predicate::TYPE_PROPERTY;
				break;
			}
		}
		$this->stack->push(new pQL_Query_Predicate($type, $key));
		return $this;
	}
	
	
	function key() {
		$this->assertPropertyDefined();
		$this->stack->push(new pQL_Query_Predicate(pQL_Query_Predicate::TYPE_KEY));
		return $this;
	}


	private function assertClassDefined() {
		foreach($this->stack as $predicate) {
			if (pQL_Query_Predicate::TYPE_CLASS === $predicate->getType()) return;
		}
		throw new pQL_Exception('Select class first!');
	}


	private function assertPropertyDefined() {
		$this->assertClassDefined();
		foreach($this->stack as $predicate) {
			if (pQL_Query_Predicate::TYPE_PROPERTY === $predicate->getType()) return;
		}
		throw new pQL_Exception('Select property first!');
	}


	/**
	 * @see IteratorAggregate::getIterator()
	 */
	function getIterator() {
		$this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
		return $this->pQL->driver()->getIterator($this->stack);
	}
}