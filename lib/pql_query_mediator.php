<?php
/**
 * Посредник между запросом и драйвером
 * 
 * @author Ti
 */
final class pQL_Query_Mediator {
	private $queryHandle;
	private $iterator;
	private $needRefrashResults = false;
	private $selectBuilder;
	private $predicateList;
	
	function setQueryHanlde($queryHanlde) {
		$this->queryHandle = $queryHanlde;
		return $this;
	}
	
	
	function getQueryHandle(pQL_Driver $driver) {
		if (!$this->selectBuilder) {
			$driver->buildSelectQuery($driver);
		}
		return $this->queryHandle;
	}
	
	
	function getQueryIterator() {
		return $this->iterator;
	}
	
	
	function setQueryIterator($iterator) {
		$this->iterator = $iterator;
		return $this;
	}
	
	
	function setSelectBuilder($select) {
		$this->selectBuilder = $select;
		return $this;
	}


	function getSelectBuilder() {
		return $this->selectBuilder;
	}


	function isEmptySelectBuilder() {
		return empty($this->queryHandle);
	}


	function setPredicateList(pQL_Query_Predicate_List $predicateList) {
		$this->predicateList = $predicateList;
		return $this;
	}


	function getPredicateList() {
		return $this->predicateList;
	}
}