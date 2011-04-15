<?php
class pQL_Query_Filter_Manager {
	private $list = array();


	function add(pQL_Query_Builder $queryBuilder, $tableName, $filterName = null, $filterMethod) {
		$this->list[$tableName][$filterName] = array( clone $queryBuilder, $filterMethod );
	}


	function apply(pQL_Query $query, $tableName, $filterName = null, $args = array()) {
		list($filterQueryBuilder, $method) = $this->list[$tableName][$filterName];
		$filterQueryBuilder->export($query->qb());
		if ($method) {
			array_unshift($args, $query);
			call_user_func_array($method, $args);
		}
	}


	function exists($tableName, $filterName = null) {
		return isset($this->list[$tableName][$filterName]);
	}
}