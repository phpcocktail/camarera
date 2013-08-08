<?php
/**
 * Copyright © 2013 t
 * This work is free. You can redistribute it and/or modify it under the
 * terms of the Do What The Fuck You Want To Public License, Version 2,
 * as published by Sam Hocevar. See the COPYING file for more details.
 *
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://www.wtfpl.net/ for more details.
 *
 * @author t
 * @since 1.0
 * @license DWTFYWT
 * @version 1.01
 */
namespace Camarera;

/**
 * StoreDriverSqlite3 gives you simple file-based storage. Used by unit tests too.
 * @author t
 * @package Camarera\Store
 * @version 1.01
 */
class StoreDriverSqlite3 extends \StoreSql {

	/**
	 * @var \SQLite3
	 */
	protected $_connection = null;

	/**
	 * I create \Sqlite3 instance
	 * @param \StoreDriverSqlite3Config $Config
	 */
	protected function __construct(\StoreDriverSqlite3Config $Config) {
		$filename = $Config->path . '/' . $Config->database;
		$this->_connection = new \SQLite3($filename, $Config->openFlags, $Config->encryptionKey);
		parent::__construct($Config);
	}

	public function escape($value, $addQuote='"') {
		$ret = \Sqlite3::escapeString($value);
		if (!empty($addQuote)) {
			$ret = $addQuote . $ret . $addQuote;
		}
		return $ret;
	}

	/**
	 * @see \StoreSql::_query()
	 */
	protected function _query($query) {

		$this->_lastQuery = $query;
		$this->_lastQueryError = null;
		$this->_lastAffectedRows = null;

		\Camarera::log(
			\Camarera::LOG_INFORMATIONAL,
			'Executing query: ' . $query
		);

		$ret = $this->_connection->query($query);
		if ($ret === false) {
			$this->_lastQueryError = array(
					'code' => $this->_connection->lastErrorCode(),
					'error' => $this->_connection->lastErrorMsg(),
			);
		}
		return $ret;
	}
	/**
	 * @see \StoreSql::_queryNextRow()
	 */
	protected function _resultNextRow($result) {
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	/**
	 * @see \StoreSql::_getInsertId()
	 */
	protected function _getInsertId() {
		return $this->_connection->lastInsertRowID();
	}


	public function execute($query) {
		return $this->_connection->exec($query);
	}

}
