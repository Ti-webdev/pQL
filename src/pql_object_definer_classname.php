<?php
final class pQL_Object_Definer_ClassName implements pQL_Object_Definer_Interface {
	private $className;
	function __construct($className = 'pQL_Object_Model') {
		$this->setClassName($className);
	}
	
	
	function setClassName($className) {
		$this->assertClassName($className);
		$this->className = $className;
		
		
		
	}
	
	
	/**
	 * Метод проверяет наследование классом $className класса pQL_Object_Model
	 */
	private function assertClassName($className) {
		if (!class_exists($className)) throw new pQL_Object_Definer_Exception("Class '$className' not found");

		$validBaseClass = 'pQL_Object_Model';
		if (0 === strcasecmp($className, $validBaseClass)) return true;

		// для PHP 5.0.3 и выше испольуем is_subclass_of
		if (version_compare(PHP_VERSION, '5.0.3', '>=')) {
			if (is_subclass_of($className, $validBaseClass)) return true;
		}
		// для ранних версий PHP
		else {
			// сравниваем каждого родителя класса
			do {
				$className = get_parent_class($className);

				if (0 === strcasecmp($className, $validBaseClass)) {
					// УРА!
					return true;
				}
			}
			while($className);
		}

		// ошибка если класс не наследует pQL_Object_Model
		throw new pQL_Object_Definer_Exception("Class '$className' is not subclass of $validBaseClass");
	}
	
	
	function getClassName() {
		return $this->className;
	}


	public function getObject(pQL $pQL, $className, $properties) {
		return new $this->className($pQL, $properties, $className);
	}
}