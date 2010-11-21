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
	
	
	function setQueryHanlde($queryHanlde) {
		$this->queryHandle = $queryHanlde;
		return $this;
	}
	
	
	function getQueryHandle() {
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
	
	
	function isEmptyQueryHandle() {
		return empty($this->queryHandle);
	}
}