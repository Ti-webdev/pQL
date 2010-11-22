<?php
/**
 * Посредник между запросом и драйвером
 * 
 * @author Ti
 */
final class pQL_Query_Mediator {
	private $selectHandle;
	private $queryIterator;
	private $selectBuilder;
	private $predicateList;
	private $isDone = false;


	function removeSelectHandle() {
		$this->selectHandle = null;
		return $this;
	}


	function getSelectHandle(pQL_Driver $driver) {
		if (!$this->selectHandle) $this->selectHandle = $driver->getSelectHandle($this->getSelectBuilder($driver));
		return $this->selectHandle;
	}


	function getQueryIterator(pQL_Driver $driver) {
		if (!$this->selectBuilder) $driver->buildSelectQuery($this);
		return $this->queryIterator;
	}


	function setQueryIterator(pQL_Query_Iterator $queryIterator) {
		$this->queryIterator = $queryIterator;
		return $this;
	}


	function setSelectBuilder(pQL_Select_Builder $selectBuilder) {
		$this->selectBuilder = $selectBuilder;
		return $this;
	}


	/**
	 * @param pQL_Driver $driver
	 * @return pQL_Select_Builder
	 */
	function getSelectBuilder(pQL_Driver $driver) {
		if (!$this->selectBuilder) $driver->buildSelectQuery($this);
		return $this->selectBuilder;
	}


	function setPredicateList(pQL_Query_Predicate_List $predicateList) {
		$this->predicateList = $predicateList;
		return $this;
	}


	function getPredicateList() {
		$this->predicateList->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO);
		return $this->predicateList;
	}
	
	
	function getIsDone() {
		return $this->isDone;
	}
	
	
	function setIsDone() {
		$this->isDone = true;
		return $this;
	}
}