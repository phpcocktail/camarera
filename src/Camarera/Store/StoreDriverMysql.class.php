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
 * StoreDriverMySql with oldschool but most compatible mysql extension functions
 * @author t
 * @package Camarera\Store
 * @version 1.01
 * @deprecated since version 1.0
 */
class StoreDriverMysql extends \StoreSql {

	/**
	 * @var Resource this will hold the resource of the mysql connection
	 */
	protected $_connection = null;

	/**
	 *	this translation array is used for sql column types
	 * @var type
	 */
	public static $fieldColumnTr = array(
			'int' => 'int(32)',
			'float' => 'float',
			'string' => 'varchar(255)',
			'email' => 'varchar(255)',
			'url' => 'text',
			'ip' => 'varchar(255)',
			'text' => 'text',
			'set' => 'set(%s)',
			'enum' => 'enum(%s)',
			'date' => 'date',
			'datim' => 'datetime',
			'tstamp' => 'int(32)',
			'crstamp' => 'int(32)',
			'key' => null,	// we'll have to guess this by related model's idfield
			'version' => 'int(32)',
			'sorting' => 'int(32)',
	);

	/**
	 * @see \StoreSql::escape()
	 */
	public function escape($value, $addQuotes='"') {
		$value = mysql_real_escape_string($value, $this->_connection);
		if ($addQuotes !== false) {
			$value = $addQuotes . $value . $addQuotes;
		}
		return $value;
	}

	/**
	 * constructs object and connects do DB
	 * @param StoreDriverMysql
	 */
	protected function __construct(\StoreDriverMysqlConfig $Config) {

		Camarera::log(
			\Camarera::LOG_NOTICE,
			'Mysql store is deprecated, provided for compatibility reasons only'
		);

		if (!empty($Config->socket)) {
			$host = $Config->socket;
		}
		else {
			$host = $Config->host .
				(empty($Config->port) ? '' : ':' . $Config->port);
		}

		// do this first, since parent will mask sensitive data
		$this->_connection = $Config->pConnect
			? mysql_pconnect($host, $Config->username, $Config->password)
			: mysql_connect($host, $Config->username, $Config->password);

		if (!$this->_connection) {
			$this->_setError(true);
		}

		mysql_select_db($Config->database, $this->_connection)
				OR $this->_setError(true);

		parent::__construct($Config);

		if (isset($Config->encoding)) {
			mysql_set_charset($Config->encoding, $this->_connection)
					OR $this->_setError(true);
		}

		Camarera::log('StoreDriverMysql #' . $Config->id . ' connected');

	}

	/**
	 * I set $this->_lastError and throw exception if requested
	 * @param boolean $doThrow if true, an exception will also be trhown
	 * @throws \RuntimeException
	 */
	protected function _setError($doThrow = false) {
		$this->_lastError = array(
				'code' => mysql_errno($this->_connection),
				'message' => mysql_error($this->_connection),
		);
		if ($doThrow) {
			throw new \RuntimeException($this->_lastError['message'], $this->_lastError['code']);
		}
	}

	protected function _query($query) {
		$this->_lastQuery = $query;
		$this->_lastError = null;
		$this->_lastAffectedRows = null;
		$ret = mysql_query($query, $this->_connection);
		if ($ret === false) {
			$this->_setError();
		}
		return $ret;
	}
	protected function _resultNextRow($result) {
		return mysql_fetch_assoc($result);
	}

	/**
	 * @see \StoreSql::_getInsertId()
	 */
	protected function _getInsertId() {
		return mysql_insert_id($this->_connection);
	}

	/**
	 * @see \Store::execute()
	 */
	public function execute($query) {
		$ret = mysql_query($query, $this->_connection);
		if (!$ret) {
			$this->_setError();
		}
		return $ret ? true : false;
	}

}
