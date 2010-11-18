<?php
final class pQL_Coding_Typical implements pQL_Coding_Interface {
	function toDB($string) {
		return preg_replace('#(.)([A-Z])#ue', '"$1"."_".strtolower("$2")', $string);
	}


	function fromDb($string) {
		return preg_replace('#_(\w)#ue', 'strtoupper("$1")', $string);
	}
}