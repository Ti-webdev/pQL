<?php
interface pQL_Object_Definer_Interface {
	function getObject(pQL $pQL, $className, $properties);
}