<?php
class pQL_Cache_Local implements pQL_Cache_Interface {
	private $data = array();
	function exists($key) {
		return array_key_exists($key, $this->data);
	}


	function get($key) {
		return $this->data[$key];
	}


	function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	
	function remove($key) {
		unset($this->data[$key]);
	}
	
	
	function clear() {
		$this->data = array();
	}
}