<?php
final class pQL_Query implements IteratorAggregate, Countable {
	static private $instance = 0;
	private $queryId;
	private $pQL;
	function __construct(pQL $pQL) {
		$this->pQL = $pQL;
		$this->stack = new pQL_Query_Predicate_List;
		$this->queryId = self::$instance++;
	}


	function __destruct() {
		if (!$this->pQL) return;
		$driver = $this->pQL->driver();
		if ($driver) $driver->clearQuery($this->queryId);
		$this->pQL = null;
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
		$this->pQL->driver()->clearQuery($this->queryId);
		return $this;
	}
	
	
	function key() {
		$this->assertPropertyDefined();
		$this->stack->push(new pQL_Query_Predicate(pQL_Query_Predicate::TYPE_KEY));
		$this->pQL->driver()->clearQuery($this->queryId);
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
		return $this->pQL->driver()->getIterator($this->queryId, $this->stack);
	}
	
	
	function count() {
		$this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
		return $this->pQL->driver()->getCount($this->queryId, $this->stack);
	}
}