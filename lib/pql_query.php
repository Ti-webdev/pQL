<?php
final class pQL_Query implements IteratorAggregate, Countable {
	static private $instance = 0;
	private $pQL;
	private $queryMediator;
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
		$this->queryMediator = null;
		return $this;
	}
	
	
	function key() {
		$this->assertPropertyDefined();
		$this->stack->push(new pQL_Query_Predicate(pQL_Query_Predicate::TYPE_KEY));
		$this->queryMediator = null;
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
	
	
	private function getQueryMediator() {
		if (!$this->queryMediator) $this->queryMediator = new pQL_Query_Mediator;
		return $this->queryMediator;
	}


	/**
	 * @see IteratorAggregate::getIterator()
	 */
	function getIterator() {
		$this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
		return $this->pQL->driver()->getIterator($this->getQueryMediator(), $this->stack);
	}


	function count() {
		$this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
		return $this->pQL->driver()->getCount($this->getQueryMediator(), $this->stack);
	}
}