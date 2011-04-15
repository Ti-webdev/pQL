<?php
class pQL_Query_Filter {
	/**
	 * @var pQL_Query_Builder
	 */
	public $queryBuilder;
	public $tableName;
	public $fieldName;
	public $callback;


	function apply(pQL_Query $query) {
		$this->queryBuilder->export($query->qb());
	}
	
}