<?php
/**
 * SQLite PDO драйвер для pQL
 * @author Ti
 * @package pQL
 */
class pQL_Driver_PDO_SQLite extends pQL_Driver_PDO {
	protected function getPrimaryKey($table) {
		$result = null;
		foreach($this->dbh()->query("PRAGMA table_info($table)", PDO::FETCH_ASSOC) as $column) {
			$isPK = (bool) $column['pk'];
			if ($isPK or is_nulL($result)) {
				$result = $column['name'];
				if ($isPK) break;
			}
		}
		return $result;
	}
}