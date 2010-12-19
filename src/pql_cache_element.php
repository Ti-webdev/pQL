<?php
final class pQL_Cache_Element {
	private $cache;
	private $key;
	function __construct(pQL_Cache_Interface $cache, $key) {
		$this->cache = $cache;
		$this->key = $key;
	}

	
	function exists() {
		return $this->cache->exists($this->key);
	}


	function get() {
		return $this->cache->get($this->key);
	}
	
	
	function set($value) {
		return $this->cache->set($this->key, $value);
	}


	function delete() {
		return $this->cache->remove($this->key);
	}
}