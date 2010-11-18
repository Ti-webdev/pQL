<?php
final class pQL_Query implements IteratorAggregate {
	private $pQL;
	function __construct(pQL $pQL) {
		$this->pQL = $pQL;
	}
	
	const PROPERTY = 1;
	private $query = array();
	function __get($property) {
		$this->query[] = array(self::PROPERTY, $property);
		return $this;
	}


	function getIterator() {
		return $this->pQL->driver()->getIterator();
	} 
}


# PROTOTYPE


class PROTOTYPE_pQL_Query implements IteratorAggregate,Countable {
	private $table;
	/**
	 * (non-PHPdoc)
	 * @see IteratorAggregate::getIterator()
	 */
	function getIterator() {
		return $this->pQL->driver()->getIterator($this);
	}


	/**
	 * @see Countable::count()
	 */
	public function count() {
	}
	
	
	function __toString() {
		return $this->pQL->driver()->sql($this->query);
	}


	function update() {
		
	}


	function delete() {
		
	}


	function sql($sql) {
		$this->queryAdd(self::Q_SQL, $sql);
		return $this;
	}


	const Q_TABLE = 1;
	const Q_FIELD = 2;
	private $query = array();
	private function queryAdd($type, $arguments) {
		$this->query[] = array($type, $arguments);
		return $this;
	}


	/**
	 * Получение таблицы или поля таблицы
	 */
	function __get($name) {
		if ($this->table) {
			$this->field = $name;
			$this->qb()->add(self::Q_FIELD, $name);
		}
		else {
			$this->table = $name;
			$this->qb()->add(self::Q_TABLE, $name);
		}
		return $this;
	}
}