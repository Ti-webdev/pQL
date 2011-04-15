<?php
class pQL_Query_Filter_Manager {
	private $list = array();


	function add($filterName, pQL_Query_Filter $filter) {
		$this->list[$filter->tableName][$filterName] = $filter;
	}
	
	
	/**
	 * @param string $tableName
	 * @param string $filterName
	 * @return pQL_Query_Filter
	 */
	function get($tableName, $filterName = null) {
		if (isset($this->list[$tableName][$filterName])) {
			return $this->list[$tableName][$filterName];
		}
		return null;
	}
}