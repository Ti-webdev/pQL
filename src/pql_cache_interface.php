<?php
interface pQL_Cache_Interface {
	function exists($key);
	function get($key);
	function set($key, $value);
	function remove($key);
	function clear();
}