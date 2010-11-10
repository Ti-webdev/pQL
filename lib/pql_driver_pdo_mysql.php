<?php
/**
 * MySQL PDO драйвер для pQL
 * @author Ti
 * @package pQL
 */
class pQL_Driver_PDO_MySQL extends pQL_Driver_PDO {
	function setTranslator(pQL_Translator $translator) {
		$translator->setDbQuote('`');
		return parent::setTranslator($translator);
	}
	
	
	protected function getPrimaryKey($table) {
		$result = null;
		foreach($this->dbh()->query("SHOW COLUMNS FROM $table", PDO::FETCH_ASSOC) as $column) {
			$isPK = 'PRI' == $column['Key'];
			if ($isPK or is_nulL($result)) {
				$result = $this->getTranslator()->addDbQuotes($column['Field']);
				if ($isPK) break;
			}
		}
		return $result;
	}
}