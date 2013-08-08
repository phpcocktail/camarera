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
 * StoreDriverMySqli with new, recommended mysqli class usage
 * @author t
 * @package Camarera\Store
 * @version 1.01
 */
class StoreDriverMysqli extends \StoreSql {

	/**
	 * @var \Mysqli this will hold the resource of the mysql connection
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
	 * @param StoreDriverMysqliConfig
	 */
	protected function __construct(\StoreDriverMysqliConfig $Config) {

		if (!empty($Config->socket)) {
			$host = null;
		}
		else {
			$host =
				($Config->pConnect ? 'p:' : '') .
				$Config->host;
		}

		// do this first, since parent will mask sensitive data
		$this->_connection = @new \mysqli(
				$host, $Config->username, $Config->password, $Config->database, $Config->port, $Config->socket
		);

		parent::__construct($Config);

		if (!empty($this->_connection->connect_error)) {
			$this->_lastError = array(
					'code' => $this->_connection->connect_errno,
					'message' => $this->_connection->connect_error,
			);
			throw new \RuntimeException($this->_connection->connect_error, $this->_connection->connect_errno);
		}

		// encoding
		if (!empty($Config->encoding)) {
			$this->_connection->set_charset($Config->encoding) or $this->_setError(true);
		}

		\Camarera::log(
			\Camarera::LOG_INFORMATIONAL,
			'StoreDriverMysqli #' . $Config->id . ' connected'
		);

	}

	/**
	 * I set $this->_lastError and throw exception if requested
	 * @param boolean $doThrow if true, an exception will also be trhown
	 * @throws \RuntimeException
	 */
	protected function _setError($doThrow = false) {
		$this->_lastError = array(
				'code' => $this->_connection->errno,
				'message' => $this->_connection->error,
		);
		if ($doThrow) {
			throw new \RuntimeException($this->_lastError['message'], $this->_lastError['code']);
		}
	}

	protected function _query($query) {
		$this->_lastQuery = $query;
		$this->_lastError = null;
		$this->_lastAffectedRows = null;
		$ret = $this->_connection->query($query, MYSQLI_STORE_RESULT);
		if ($ret === false) {
			$this->_setError();
		}
		return $ret;
	}

	protected function _resultNextRow($result) {
		return $result->fetch_assoc();
	}

	/**
	 * @see \StoreSql::_getInsertId()
	 */
	protected function _getInsertId() {
		return $this->_connection->insert_id;
	}

	/**
	 * @see \Store::execute()
	 */
	public function execute($query) {
		$ret = $this->_connection->query($query);
		if (!$ret) {
			$this->_setError();
		}
		return $ret ? true : false;
	}

}
